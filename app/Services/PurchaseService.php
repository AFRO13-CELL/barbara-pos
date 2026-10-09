<?php
namespace App\Services;
use App\Models\{AuditLog, Notification, ProductVariation, Purchase, PurchaseItem, PurchasePayment, User};
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class PurchaseService
{
    public static function create(User $user, array $in): Purchase
    {
        return DB::transaction(function () use ($user, $in) {
            $qtys = []; $costs = [];
            foreach ($in['items'] as $it) {
                $id = (int) $it['variation_id'];
                $qtys[$id] = ($qtys[$id] ?? 0) + (int) $it['qty'];
                $costs[$id] = round((float) $it['cost'], 2);
            }
            $vars = ProductVariation::with('product')->whereIn('id', array_keys($qtys))->get()->keyBy('id');
            $total = 0.0; $lines = [];
            foreach ($qtys as $id => $qty) {
                $v = $vars[$id] ?? null;
                if (!$v || !$v->product) throw new \RuntimeException('An item on this purchase is no longer available.');
                $line = round($qty * $costs[$id], 2);
                $total += $line;
                $lines[] = compact('v', 'qty', 'line') + ['cost' => $costs[$id]];
            }
            $total = round($total, 2);
            $paid = round((float) ($in['pay_amount'] ?? 0), 2);
            if ($paid > $total) throw new \RuntimeException('The payment cannot be more than the purchase total.');
            if ($paid > 0 && ($in['pay_method'] ?? 'cash') !== 'cash' && empty($in['pay_reference'])) {
                throw new \RuntimeException('Enter the payment reference for non-cash payments.');
            }

            $p = Purchase::create([
                'purchase_number' => 'TMP' . Str::random(12), 'supplier_id' => $in['supplier_id'], 'user_id' => $user->id,
                'purchase_date' => $in['purchase_date'], 'status' => 'ordered', 'total' => $total,
                'amount_paid' => $paid, 'balance' => round($total - $paid, 2), 'notes' => $in['notes'] ?? null,
            ]);
            $p->update(['purchase_number' => 'PUR-' . str_pad((string) $p->id, 5, '0', STR_PAD_LEFT)]);
            foreach ($lines as $l) {
                PurchaseItem::create(['purchase_id' => $p->id, 'product_variation_id' => $l['v']->id,
                    'product_name' => $l['v']->product->name, 'variation_label' => $l['v']->options_label, 'sku' => $l['v']->sku,
                    'quantity' => $l['qty'], 'cost_price' => $l['cost'], 'line_total' => $l['line']]);
            }
            if ($paid > 0) {
                PurchasePayment::create(['purchase_id' => $p->id, 'user_id' => $user->id, 'method' => $in['pay_method'] ?? 'cash',
                    'amount' => $paid, 'reference' => $in['pay_reference'] ?? null]);
            }
            AuditLog::record('purchase_created', $p, "{$user->name} created {$p->purchase_number}, total " . Money::fmt($total), null, ['total' => $total, 'paid' => $paid]);
            if ($p->balance > 0) {
                Notification::create(['type' => 'supplier_payment', 'title' => 'Supplier payment pending',
                    'message' => "{$p->purchase_number}: " . Money::fmt($p->balance) . ' still owed.', 'url' => route('purchases.show', $p)]);
            }
            if (!empty($in['receive_now'])) self::receiveLocked($user, $p, !empty($in['update_cost']));
            return $p->fresh();
        });
    }

    public static function receive(User $user, Purchase $purchase, bool $updateCost): Purchase
    {
        return DB::transaction(fn () => self::receiveLocked($user, $purchase, $updateCost));
    }

    private static function receiveLocked(User $user, Purchase $purchase, bool $updateCost): Purchase
    {
        $p = Purchase::whereKey($purchase->id)->lockForUpdate()->with('items')->firstOrFail();
        if ($p->status === 'received') throw new \RuntimeException('This purchase has already been received.');
        foreach ($p->items as $item) {
            $v = ProductVariation::withTrashed()->findOrFail($item->product_variation_id);
            StockService::move($v, (int) $item->quantity, 'purchase', 'Purchase ' . $p->purchase_number);
            if ($updateCost) $v->update(['cost_price' => $item->cost_price]);
        }
        $p->update(['status' => 'received', 'received_at' => now()]);
        AuditLog::record('purchase_received', $p, "{$user->name} received stock for {$p->purchase_number}");
        return $p;
    }

    public static function addPayment(User $user, Purchase $purchase, array $d): Purchase
    {
        return DB::transaction(function () use ($user, $purchase, $d) {
            $p = Purchase::whereKey($purchase->id)->lockForUpdate()->firstOrFail();
            $amount = round((float) $d['amount'], 2);
            if ($amount <= 0 || $amount > (float) $p->balance) {
                throw new \RuntimeException('Amount must be more than 0 and not more than the balance of ' . Money::fmt($p->balance) . '.');
            }
            PurchasePayment::create(['purchase_id' => $p->id, 'user_id' => $user->id, 'method' => $d['method'],
                'amount' => $amount, 'reference' => $d['reference'] ?? null]);
            $old = $p->balance;
            $p->update(['amount_paid' => round((float) $p->amount_paid + $amount, 2), 'balance' => round((float) $p->balance - $amount, 2)]);
            AuditLog::record('purchase_payment', $p, "{$user->name} paid " . Money::fmt($amount) . " on {$p->purchase_number}", ['balance' => $old], ['balance' => $p->balance]);
            return $p;
        });
    }
}
