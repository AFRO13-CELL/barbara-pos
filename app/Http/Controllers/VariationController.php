<?php
namespace App\Http\Controllers;
use App\Models\{Product, ProductVariation};
use App\Services\CatalogService;
use Illuminate\Http\Request;

class VariationController extends Controller
{
    private function rules(bool $canCost, $ignoreId = null): array
    {
        return [
            'size' => 'nullable|string|max:40', 'color' => 'nullable|string|max:40',
            'material' => 'nullable|string|max:60', 'style' => 'nullable|string|max:60',
            'gender' => 'nullable|string|max:30', 'item_condition' => 'nullable|string|max:30',
            'cost_price' => $canCost ? 'required|numeric|min:0' : 'nullable|numeric|min:0',
            'selling_price' => 'required|numeric|min:0',
            'discount_price' => 'nullable|numeric|min:0|lt:selling_price',
            'min_stock' => 'nullable|integer|min:0',
            'sku' => 'nullable|string|max:60|unique:product_variations,sku' . ($ignoreId ? ",{$ignoreId}" : ''),
            'barcode' => 'nullable|string|max:60|unique:product_variations,barcode' . ($ignoreId ? ",{$ignoreId}" : ''),
        ];
    }

    public function store(Request $r, Product $product)
    {
        $canCost = $r->user()->can_do('costs.view');
        $d = $r->validate($this->rules($canCost) + ['stock_qty' => 'nullable|integer|min:0']);
        $d['cost_price'] = $d['cost_price'] ?? 0;
        CatalogService::createVariation($product, $d);
        return back()->with('status', 'Variation added.');
    }

    // Stock is NOT editable here. Use Inventory > Adjust so every change is recorded.
    public function update(Request $r, ProductVariation $variation)
    {
        $canCost = $r->user()->can_do('costs.view');
        $d = $r->validate($this->rules($canCost, $variation->id));
        if (!$canCost) unset($d['cost_price']);
        $d['discount_price'] = ($d['discount_price'] ?? null) ?: null;
        $d['barcode'] = ($d['barcode'] ?? null) ?: $variation->barcode;
        $d['sku'] = ($d['sku'] ?? null) ?: $variation->sku;
        $d['min_stock'] = $d['min_stock'] ?? $variation->min_stock;
        $variation->update($d);
        return back()->with('status', 'Variation saved.');
    }

    public function destroy(ProductVariation $variation)
    {
        $variation->delete();
        return back()->with('status', 'Variation removed. Past sales are not affected.');
    }
}
