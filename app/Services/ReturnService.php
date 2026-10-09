<?php
namespace App\Services;
use App\Models\{AuditLog, Customer, ProductVariation, ReturnItem, Sale, SaleReturn, User};
use App\Support\Money;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ReturnService
{
    /** $in['items'] = [['sale_item_id'=>, 'qty'=>, 'condition'=>'sellable|damaged'], ...] */
    public static function process(User $user, Sale $sale, array $in): SaleReturn
    {
        return DB::transaction(function () use ($user, $sale, $in) {
            $s = Sale::whereKey($sale->id)->lockForUpdate()->with('items')->firstOrFail();
            $lines = []; $value = 0.0;
            foreach ($in['items'] as $row) {
                $qty = (int) $row['qty'];
                if ($qty < 1) continue;
                $item = $s->items->firstWhere('id', (int) $row['sale_item_id']);
                if (!$item) throw new \RuntimeException('That item does not belong to this sale.');
                $already = (int) ReturnItem::where('sale_item_id', $item->id)->sum('quantity');
                $left = $item->quantity - $already;
                if ($qty > $left) throw new \RuntimeException("Only {$left} of {$item->product_name} can still be returned.");
                // refund what the customer actually paid, i.e. after the sale's discount
                $amount = round((float) $item->unit_price * $qty * (1 - (float) $s->discount_percent / 100), 2);
                $value += $amount;
                $lines[] = compact('item', 'qty', 'amount') + ['condition' => $row['condition'] === 'damaged' ? 'damaged' : 'sellable'];
            }
            if (!$lines) throw new \RuntimeException('Choose at least one item to return.');
            $value = round($value, 2);

            $type = $in['type'];
            if (in_array($type, ['exchange', 'store_credit']) && !$s->customer_id) {
                throw new \RuntimeException('Exchanges and store credit need a customer on the original sale.');
            }
            $reduce = round(min((float) $s->balance, $value), 2);      // cancels an unpaid balance first
            $payout = round($value - $reduce, 2);

            $ret = SaleReturn::create([
                'return_number' => 'TMP' . Str::random(12), 'sale_id' => $s->id, 'customer_id' => $s->customer_id,
                'user_id' => $user->id, 'type' => $type, 'value' => $value, 'balance_reduced' => $reduce,
                'refund_amount' => $payout, 'refund_method' => $type === 'refund' ? ($in['refund_method'] ?? 'cash') : null,
                'refund_reference' => $in['refund_reference'] ?? null, 'reason' => $in['reason'], 'notes' => $in['notes'] ?? null,
            ]);
            $ret->update(['return_number' => 'RET-' . str_pad((string) $ret->id, 5, '0', STR_PAD_LEFT)]);

            foreach ($lines as $l) {
                $item = $l['item'];
                ReturnItem::create(['return_id' => $ret->id, 'sale_item_id' => $item->id, 'product_variation_id' => $item->product_variation_id,
                    'product_name' => $item->product_name, 'variation_label' => $item->variation_label, 'quantity' => $l['qty'],
                    'unit_price' => $item->unit_price, 'cost_price' => $item->cost_price, 'amount' => $l['amount'], 'item_condition' => $l['condition']]);
                if ($l['condition'] === 'sellable') {      // damaged goods never go back into sellable stock
                    $v = ProductVariation::withTrashed()->findOrFail($item->product_variation_id);
                    StockService::move($v, $l['qty'], 'return', 'Return ' . $ret->return_number);
                }
            }
            if (in_array($type, ['exchange', 'store_credit']) && $payout > 0) {
                Customer::whereKey($s->customer_id)->increment('store_credit', $payout);
            }
            $newBalance = round((float) $s->balance - $reduce, 2);
            $s->update(['balance' => $newBalance, 'refunded_amount' => round((float) $s->refunded_amount + $value, 2),
                'payment_status' => ($newBalance <= 0 && $s->payment_status !== 'paid') ? 'paid' : $s->payment_status]);

            AuditLog::record('return_created', $ret, "{$user->name} processed {$ret->return_number} ({$type}) for {$s->receipt_number}, " . Money::fmt($value),
                null, ['value' => $value, 'payout' => $payout, 'balance_reduced' => $reduce]);
            return $ret;
        });
    }
}
