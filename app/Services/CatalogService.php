<?php
namespace App\Services;
use App\Models\{Color, Product, ProductVariation, Setting, Size};
use Illuminate\Support\Str;

class CatalogService
{
    public static function createVariation(Product $product, array $d): ProductVariation
    {
        if (!empty($d['size'])) Size::firstOrCreate(['name' => $d['size']]);
        if (!empty($d['color'])) Color::firstOrCreate(['name' => $d['color']]);

        $sku = trim((string) ($d['sku'] ?? '')) ?: self::makeSku($product, $d);
        $v = ProductVariation::create([
            'product_id' => $product->id, 'sku' => $sku, 'barcode' => ($d['barcode'] ?? null) ?: null,
            'size' => $d['size'] ?? null, 'color' => $d['color'] ?? null,
            'material' => $d['material'] ?? null, 'style' => $d['style'] ?? null,
            'gender' => $d['gender'] ?? null, 'item_condition' => $d['item_condition'] ?? null,
            'cost_price' => $d['cost_price'] ?? 0, 'selling_price' => $d['selling_price'] ?? 0,
            'discount_price' => ($d['discount_price'] ?? null) ?: null,
            'stock_qty' => 0,
            'min_stock' => $d['min_stock'] ?? (int) Setting::get('low_stock_threshold', 3),
        ]);
        if (!$v->barcode) {
            $v->barcode = '200' . str_pad((string) $v->id, 9, '0', STR_PAD_LEFT);
            $v->save();
        }
        $qty = (int) ($d['stock_qty'] ?? 0);
        if ($qty > 0) StockService::move($v, $qty, 'initial', 'Opening stock');
        return $v->fresh();
    }

    private static function makeSku(Product $p, array $d): string
    {
        $base = Str::upper(Str::substr(Str::slug($p->name, ''), 0, 4)) ?: 'ITEM';
        $parts = array_filter([
            Str::upper(Str::substr(Str::slug((string) ($d['size'] ?? ''), ''), 0, 3)),
            Str::upper(Str::substr(Str::slug((string) ($d['color'] ?? ''), ''), 0, 3)),
        ]);
        $sku = implode('-', array_merge([$base], $parts));
        $try = $sku; $i = 1;
        while (ProductVariation::withTrashed()->where('sku', $try)->exists()) $try = $sku . '-' . (++$i);
        return $try;
    }
}
