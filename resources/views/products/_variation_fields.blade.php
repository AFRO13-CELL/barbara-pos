@php
    $inp = 'w-full rounded-lg border-stone-300 text-sm';
    $canCost = auth()->user()->can_do('costs.view');
    $v = $v ?? null;
    $uid = $uid ?? 'new';
@endphp
<datalist id="sizes-{{ $uid }}">@foreach ($sizes as $s)<option value="{{ $s }}">@endforeach</datalist>
<datalist id="colors-{{ $uid }}">@foreach ($colors as $c)<option value="{{ $c }}">@endforeach</datalist>
<div class="grid grid-cols-2 md:grid-cols-4 gap-3">
    <label class="text-xs">Size<input name="size" list="sizes-{{ $uid }}" value="{{ old('size', $v?->size) }}" class="{{ $inp }}" placeholder="M, 32, ..."></label>
    <label class="text-xs">Color<input name="color" list="colors-{{ $uid }}" value="{{ old('color', $v?->color) }}" class="{{ $inp }}" placeholder="Black..."></label>
    <label class="text-xs">SKU<input name="sku" value="{{ old('sku', $v?->sku) }}" class="{{ $inp }}" placeholder="Auto if empty"></label>
    <label class="text-xs">Barcode<input name="barcode" value="{{ old('barcode', $v?->barcode) }}" class="{{ $inp }}" placeholder="Auto if empty"></label>
    @if ($canCost)
    <label class="text-xs">Cost price<input name="cost_price" type="number" step="0.01" min="0" required value="{{ old('cost_price', $v?->cost_price) }}" class="{{ $inp }}"></label>
    @endif
    <label class="text-xs">Selling price<input name="selling_price" type="number" step="0.01" min="0" required value="{{ old('selling_price', $v?->selling_price) }}" class="{{ $inp }}"></label>
    <label class="text-xs">Discount price<input name="discount_price" type="number" step="0.01" min="0" value="{{ old('discount_price', $v?->discount_price) }}" class="{{ $inp }}"></label>
    <label class="text-xs">Minimum stock<input name="min_stock" type="number" min="0" value="{{ old('min_stock', $v?->min_stock) }}" class="{{ $inp }}" placeholder="{{ \App\Models\Setting::get('low_stock_threshold', 3) }}"></label>
    @if (!$v)
    <label class="text-xs">Opening stock<input name="stock_qty" type="number" min="0" value="{{ old('stock_qty', 0) }}" class="{{ $inp }}"></label>
    @endif
    <label class="text-xs">Material<input name="material" value="{{ old('material', $v?->material) }}" class="{{ $inp }}"></label>
    <label class="text-xs">Style<input name="style" value="{{ old('style', $v?->style) }}" class="{{ $inp }}"></label>
    <label class="text-xs">Gender<input name="gender" value="{{ old('gender', $v?->gender) }}" class="{{ $inp }}" placeholder="Women, Men, Kids"></label>
    <label class="text-xs">Condition<input name="item_condition" value="{{ old('item_condition', $v?->item_condition) }}" class="{{ $inp }}" placeholder="New, Used"></label>
</div>
