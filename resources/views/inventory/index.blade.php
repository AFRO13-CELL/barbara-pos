@extends('layouts.boutique')
@section('title', 'Inventory')
@section('content')
@php $me = auth()->user(); $canCost = $me->can_do('costs.view'); $canAdjust = $me->can_do('stock.adjust'); $inp = 'rounded-lg border-stone-300 text-sm'; @endphp

<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
    <a href="{{ route('inventory.index') }}" class="bg-white border border-stone-200 rounded-xl p-4"><div class="text-xs text-stone-500">Items tracked</div><div class="text-xl font-semibold">{{ $stats['total'] }}</div></a>
    <a href="{{ route('inventory.index', ['status' => 'low']) }}" class="bg-white border border-amber-200 rounded-xl p-4"><div class="text-xs text-stone-500">Low stock</div><div class="text-xl font-semibold text-amber-700">{{ $stats['low'] }}</div></a>
    <a href="{{ route('inventory.index', ['status' => 'out']) }}" class="bg-white border border-red-200 rounded-xl p-4"><div class="text-xs text-stone-500">Out of stock</div><div class="text-xl font-semibold text-red-700">{{ $stats['out'] }}</div></a>
    @if ($canCost)<div class="bg-white border border-stone-200 rounded-xl p-4"><div class="text-xs text-stone-500">Stock value (cost)</div><div class="text-xl font-semibold">{{ \App\Support\Money::fmt($stats['value']) }}</div></div>@endif
</div>

<form method="GET" class="mb-4 flex flex-wrap gap-2" x-data>
    <input type="search" name="q" value="{{ request('q') }}" autofocus onfocus="this.setSelectionRange(this.value.length, this.value.length)"
           @input.debounce.500ms="$el.form.submit()" placeholder="Search product, SKU, barcode, variation, category..." class="{{ $inp }} flex-1 min-w-[220px]">
    <select name="status" class="{{ $inp }}" @change="$el.form.submit()">
        <option value="">All statuses</option>
        @foreach (['in' => 'In stock', 'low' => 'Low stock', 'out' => 'Out of stock'] as $k => $l)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $l }}</option>@endforeach
    </select>
    <select name="category" class="{{ $inp }}" @change="$el.form.submit()">
        <option value="">All categories</option>
        @foreach ($categories as $c)<option value="{{ $c->id }}" @selected(request('category') == $c->id)>{{ $c->name }}</option>@endforeach
    </select>
</form>

<div class="space-y-2">
@forelse ($variations as $v)
    <div class="bg-white border border-stone-200 rounded-xl p-3">
        <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
            <div class="flex-1 min-w-[200px]">
                <div class="font-medium">{{ $v->product->name }} <span class="text-stone-500 font-normal">- {{ $v->options_label }}</span></div>
                <div class="text-xs text-stone-500">{{ $v->sku }} &middot; {{ $v->barcode }} &middot; {{ $v->product->category?->name }}</div>
            </div>
            <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $v->status === 'out' ? 'bg-red-100 text-red-700' : ($v->status === 'low' ? 'bg-amber-100 text-amber-800' : 'bg-emerald-100 text-emerald-700') }}">
                {{ $v->status === 'out' ? 'Out of stock' : ($v->status === 'low' ? 'Low stock' : 'In stock') }}</span>
            <div class="text-sm"><span class="text-xl font-semibold">{{ $v->stock_qty }}</span> <span class="text-xs text-stone-500">min {{ $v->min_stock }}</span></div>
            @if ($canCost)<div class="text-xs text-stone-500">Value {{ \App\Support\Money::fmt($v->stock_qty * $v->cost_price) }}</div>@endif
        </div>
        @if ($canAdjust)
        <details class="mt-2" @if (request('q') && $variations->count() === 1) open @endif>
            <summary class="cursor-pointer text-xs underline">Adjust stock</summary>
            <form method="POST" action="{{ route('inventory.adjust', $v) }}" class="mt-2 flex flex-wrap gap-2 items-end">
                @csrf
                <label class="text-xs">Change (+ add / - remove)<input name="qty_change" type="number" required class="{{ $inp }} block w-36" placeholder="e.g. 5 or -2"></label>
                <label class="text-xs">Reason
                    <select name="reason" required class="{{ $inp }} block">@foreach ($reasons as $r)<option>{{ $r }}</option>@endforeach</select></label>
                <label class="text-xs flex-1 min-w-[160px]">Note<input name="note" maxlength="500" class="{{ $inp }} block w-full"></label>
                <button class="rounded-lg bg-stone-900 text-white px-4 py-2 text-sm">Apply</button>
            </form>
        </details>
        @endif
    </div>
@empty
    <div class="bg-white border border-stone-200 rounded-xl p-10 text-center text-stone-500">Nothing found.</div>
@endforelse
</div>
<div class="mt-4">{{ $variations->links() }}</div>
@endsection
