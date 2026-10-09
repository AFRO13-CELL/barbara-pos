<?php
namespace App\Services;
use App\Models\{InventoryMovement, Notification, ProductVariation, Setting};
use Illuminate\Support\Facades\DB;

class StockService
{
    /** Change stock by a signed quantity, safely, and record the movement. */
    public static function move(ProductVariation $variation, int $qty, string $type, ?string $reason = null, ?string $note = null): InventoryMovement
    {
        return DB::transaction(function () use ($variation, $qty, $type, $reason, $note) {
            $v = ProductVariation::withTrashed()->whereKey($variation->id)->lockForUpdate()->firstOrFail();
            $prev = (int) $v->stock_qty;
            $new = $prev + $qty;
            if ($new < 0 && !(int) Setting::get('allow_negative_stock', 0)) {
                throw new \RuntimeException("Not enough stock. Only {$prev} available.");
            }
            $v->stock_qty = $new;
            $v->save();

            $movement = InventoryMovement::create([
                'product_variation_id' => $v->id, 'user_id' => auth()->id(), 'type' => $type,
                'quantity' => $qty, 'previous_stock' => $prev, 'new_stock' => $new,
                'reason' => $reason, 'note' => $note,
            ]);

            // Alert once, when stock crosses into low / out.
            if ($qty < 0) {
                $name = $v->product?->name . ' (' . $v->options_label . ')';
                if ($new <= 0 && $prev > 0) {
                    Notification::create(['type' => 'out_of_stock', 'title' => 'Out of stock', 'message' => $name . ' is out of stock.', 'url' => route('inventory.index', ['q' => $v->sku])]);
                } elseif ($new <= $v->min_stock && $prev > $v->min_stock) {
                    Notification::create(['type' => 'low_stock', 'title' => 'Low stock', 'message' => $name . " is down to {$new}.", 'url' => route('inventory.index', ['q' => $v->sku])]);
                }
            }
            return $movement;
        });
    }
}
