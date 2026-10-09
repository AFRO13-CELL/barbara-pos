@extends('layouts.boutique')
@section('title', 'Products')
@section('content')
@php $me = auth()->user(); $archived = request()->boolean('archived'); @endphp

<form method="GET" class="mb-4 flex flex-wrap gap-2" x-data>
    @if ($archived)<input type="hidden" name="archived" value="1">@endif
    <input type="search" name="q" value="{{ request('q') }}" autofocus
           onfocus="this.setSelectionRange(this.value.length, this.value.length)"
           @input.debounce.500ms="$el.form.submit()"
           placeholder="Search name, SKU, barcode, category, brand, size, color..."
           class="flex-1 min-w-[220px] rounded-lg border-stone-300 text-sm">
    <select name="category" class="rounded-lg border-stone-300 text-sm" @change="$el.form.submit()">
        <option value="">All categories</option>
        @foreach ($categories as $c)<option value="{{ $c->id }}" @selected(request('category') == $c->id)>{{ $c->name }}</option>@endforeach
    </select>
    <a href="{{ route('products.index', $archived ? [] : ['archived' => 1]) }}" class="rounded-lg border border-stone-300 px-3 py-2 text-sm">{{ $archived ? 'Show active' : 'Show archived' }}</a>
    @if ($me->can_do('products.create'))<a href="{{ route('products.create') }}" class="rounded-lg bg-stone-900 text-white px-4 py-2 text-sm">+ Add product</a>@endif
</form>

<div class="bg-white border border-stone-200 rounded-xl overflow-x-auto">
<table class="min-w-full text-sm">
    <thead class="bg-stone-100 text-left text-stone-600">
        <tr><th class="px-4 py-3">Product</th><th class="px-4 py-3">Category</th><th class="px-4 py-3">Variations</th><th class="px-4 py-3">Price</th><th class="px-4 py-3">Stock</th><th class="px-4 py-3"></th></tr>
    </thead>
    <tbody class="divide-y divide-stone-100">
    @forelse ($products as $p)
        <tr>
            <td class="px-4 py-3 font-medium">{{ $p->name }}@if ($p->brand)<div class="text-xs text-stone-500">{{ $p->brand->name }}</div>@endif</td>
            <td class="px-4 py-3">{{ $p->category?->name }}</td>
            <td class="px-4 py-3">{{ $p->variations_count }}</td>
            <td class="px-4 py-3">
                @if ($p->variations_min_selling_price === null) - @elseif ($p->variations_min_selling_price == $p->variations_max_selling_price)
                    {{ \App\Support\Money::fmt($p->variations_min_selling_price) }}
                @else {{ \App\Support\Money::fmt($p->variations_min_selling_price) }} - {{ \App\Support\Money::fmt($p->variations_max_selling_price) }} @endif
            </td>
            <td class="px-4 py-3">{{ (int) $p->variations_sum_stock_qty }}</td>
            <td class="px-4 py-3 text-right whitespace-nowrap">
                @if ($archived)
                    @if ($me->can_do('products.edit'))
                    <form method="POST" action="{{ route('products.restore', $p->id) }}">@csrf<button class="text-emerald-700 hover:underline">Restore</button></form>
                    @endif
                @elseif ($me->can_do('products.edit'))
                    <a href="{{ route('products.edit', $p) }}" class="text-stone-900 underline">Open</a>
                @endif
            </td>
        </tr>
    @empty
        <tr><td colspan="6" class="px-4 py-10 text-center text-stone-500">No products found.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div class="mt-4">{{ $products->links() }}</div>
@endsection
