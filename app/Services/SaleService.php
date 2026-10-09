<?php
namespace App\Services;
use App\Models\{AuditLog, Customer, Notification, Payment, ProductVariation, Sale, SaleItem, User};
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class SaleService
{
    /**
     * Complete a sale. Everything happens in ONE database transaction:
     * if any step fails (e.g. not enough stock), nothing is saved.
     * Prices and costs always come from the database, never from the browser.
     */
    public static function checkout(User $user, array $in): Sale
    {
        return DB::transaction(function () use ($user, $in) {
            $customerId = $in['customer_id'] ?? null;
            if (!$customerId && !empty($in['new_customer']['name'])) {
                $customerId = Customer::create([
                    'name' => trim($in['new_customer']['name']),
                    'phone' => $in['new_customer']['phone'] ?? null,
                ])->id;
            }

            // Merge duplicate lines
            $qtys = [];
            foreach ($in['items'] as $it) {
                $qtys[$it['variation_id']] = ($qtys[$it['variation_id']] ?? 0) + (int) $it['qty'];
            }
            $vars = ProductVariation::with('product')->whereIn('id', array_keys($qtys))->get()->keyBy('id');

            $lines = []; $subtotal = 0.0; $profit = 0.0;
            foreach ($qtys as $id => $qty) {
                $v = $vars[$id] ?? null;
                if (!$v || !$v->product || $qty < 1) throw new \RuntimeException('An item in the cart is no longer available.');
                $price = (float) ($v->discount_price ?: $v->selling_price);
                $cost = (float) $v->cost_price;
                $lineTotal = round($price * $qty, 2);
                $lineProfit = round($lineTotal - $cost * $qty, 2);
                $subtotal += $lineTotal; $profit += $lineProfit;
                $lines[] = compact('v', 'qty', 'price', 'cost', 'lineTotal', 'lineProfit');
            }
            $subtotal = round($subtotal, 2);

            // Discount, limited by what this user is allowed to give
            $type = $in['discount_type'] ?? 'none';
            $val = (float) ($in['discount_value'] ?? 0);
            $discount = match ($type) {
                'percent' => round($subtotal * min($val, 100) / 100, 2),
                'fixed' => round(min($val, $subtotal), 2),
                default => 0.0,
            };
            $pct = $subtotal > 0 ? round($discount / $subtotal * 100, 2) : 0.0;
            if ($discount > 0 && $user->role?->name !== 'owner' && $pct > (float) $user->max_discount_percent) {
                throw new \RuntimeException("You may give discounts up to {$user->max_discount_percent}%. This sale has {$pct}%.");
            }
            $total = round($subtotal - $discount, 2);
            $profit = round($profit - $discount, 2);

            // Payments (change is only possible on cash)
            $pays = array_values($in['payments'] ?? []);
            $paid = round(array_sum(array_map(fn ($p) => (float) $p['amount'], $pays)), 2);
            if ($paid > $total) {
                $excess = round($paid - $total, 2);
                $idx = null;
                foreach ($pays as $i => $p) if ($p['method'] === 'cash') $idx = $i;
                if ($idx === null || (float) $pays[$idx]['amount'] < $excess) {
                    throw new \RuntimeException('Payments are more than the total. Only cash can give change.');
                }
                $pays[$idx]['amount'] = round((float) $pays[$idx]['amount'] - $excess, 2);
                if ($pays[$idx]['amount'] <= 0) unset($pays[$idx]);
                $paid = $total;
            }
            $balance = round($total - $paid, 2);
            if ($balance > 0) {
                if (!$customerId) throw new \RuntimeException('Choose or add a customer when the sale is not fully paid.');
                if (!$user->can_do('credit.create')) throw new \RuntimeException('You are not allowed to sell on credit.');
                if (empty($in['due_date'])) throw new \RuntimeException('Set a due date for the unpaid balance.');
            }

            $credit = round(array_sum(array_map(fn ($p) => $p['method'] === 'store_credit' ? (float) $p['amount'] : 0, $pays)), 2);
            if ($credit > 0) {
                if (!$customerId) throw new \RuntimeException('Choose the customer whose store credit is being used.');
                $cust = Customer::whereKey($customerId)->lockForUpdate()->firstOrFail();
                if ($credit > (float) $cust->store_credit) throw new \RuntimeException('This customer only has ' . Money::fmt($cust->store_credit) . ' store credit.');
                $cust->decrement('store_credit', $credit);
            }

            $sale = Sale::create([
                'receipt_number' => 'TMP' . Str::random(12),
                'customer_id' => $customerId, 'user_id' => $user->id,
                'subtotal' => $subtotal, 'discount_percent' => $pct, 'discount_amount' => $discount,
                'total' => $total, 'amount_paid' => $paid, 'balance' => $balance, 'gross_profit' => $profit,
                'due_date' => $balance > 0 ? $in['due_date'] : null,
                'payment_status' => $balance <= 0 ? 'paid' : ($paid > 0 ? 'partial' : 'credit'),
                'notes' => $in['notes'] ?? null,
            ]);
            $sale->update(['receipt_number' => 'REC-' . str_pad((string) $sale->id, 5, '0', STR_PAD_LEFT)]);

            foreach ($lines as $l) {
                $v = $l['v'];
                SaleItem::create([
                    'sale_id' => $sale->id, 'product_variation_id' => $v->id,
                    'product_name' => $v->product->name, 'variation_label' => $v->options_label, 'sku' => $v->sku,
                    'quantity' => $l['qty'], 'cost_price' => $l['cost'], 'unit_price' => $l['price'],
                    'line_total' => $l['lineTotal'], 'line_profit' => $l['lineProfit'],
                ]);
                StockService::move($v, -$l['qty'], 'sale', 'Sale ' . $sale->receipt_number);
            }
            foreach ($pays as $p) {
                Payment::create([
                    'sale_id' => $sale->id, 'user_id' => $user->id, 'method' => $p['method'],
                    'amount' => $p['amount'], 'provider' => $p['provider'] ?? null, 'reference' => $p['reference'] ?? null,
                ]);
            }

            AuditLog::record('sale_created', $sale,
                "{$user->name} sold " . array_sum($qtys) . " item(s), {$sale->receipt_number}, total " . Money::fmt($total),
                null, ['total' => $total, 'discount' => $discount, 'paid' => $paid, 'balance' => $balance]);
            if ($pct >= (float) \App\Models\Setting::get('large_discount_percent', 15)) {
                Notification::create(['type' => 'large_discount', 'title' => 'Large discount',
                    'message' => "{$sale->receipt_number}: {$pct}% discount by {$user->name}.", 'url' => route('sales.show', $sale)]);
            }
            return $sale;
        });
    }

    /** Record a later payment against an unpaid balance. */
    public static function addPayment(User $user, Sale $sale, array $d): Sale
    {
        return DB::transaction(function () use ($user, $sale, $d) {
            $s = Sale::whereKey($sale->id)->lockForUpdate()->firstOrFail();
            $amount = round((float) $d['amount'], 2);
            if ($amount <= 0 || $amount > (float) $s->balance) {
                throw new \RuntimeException('Amount must be more than 0 and not more than the balance of ' . Money::fmt($s->balance) . '.');
            }
            Payment::create(['sale_id' => $s->id, 'user_id' => $user->id, 'method' => $d['method'],
                'amount' => $amount, 'provider' => $d['provider'] ?? null, 'reference' => $d['reference'] ?? null]);
            $paid = round((float) $s->amount_paid + $amount, 2);
            $balance = round((float) $s->total - $paid, 2);
            $s->update(['amount_paid' => $paid, 'balance' => $balance,
                'payment_status' => $balance <= 0 ? 'paid' : 'partial', 'due_date' => $balance <= 0 ? null : $s->due_date]);
            AuditLog::record('payment_added', $s, "{$user->name} recorded " . Money::fmt($amount) . " on {$s->receipt_number}",
                ['balance' => $sale->balance], ['balance' => $balance]);
            return $s;
        });
    }
}
