@extends('layouts.boutique')
@section('title', 'New Return')
@section('content')
@php $inp = 'rounded-lg border-stone-300 text-sm'; @endphp
@if (!$sale)
<form method="GET" action="{{ route('returns.find') }}" class="bg-white border border-stone-200 rounded-xl p-4 flex flex-wrap gap-2 items-end">
    <label class="text-xs flex-1 min-w-[220px]">Receipt number of the original sale<input name="receipt" required placeholder="REC-00001" class="{{ $inp }} w-full"></label>
    <button class="rounded-lg bg-stone-900 text-white px-4 py-2 text-sm">Find sale</button>
</form>
@else
<div class="bg-white border border-stone-200 rounded-xl p-4 mb-4 text-sm">
    Sale <a class="underline font-medium" href="{{ route('sales.show', $sale) }}">{{ $sale->receipt_number }}</a> &middot; {{ $sale->created_at->format('d M Y') }}
    &middot; {{ $sale->customer?->name ?? 'Walk-in customer' }} &middot; Total {{ \App\Support\Money::fmt($sale->total) }}
    @if ($sale->discount_percent > 0)<div class="text-xs text-stone-500">This sale had a {{ rtrim(rtrim($sale->discount_percent, '0'), '.') }}% discount, so refunds are for the price actually paid.</div>@endif
</div>
<form method="POST" action="{{ route('returns.store') }}" class="space-y-4">
    @csrf
    <input type="hidden" name="sale_id" value="{{ $sale->id }}">
    <div class="bg-white border border-stone-200 rounded-xl p-4 space-y-3">
        <h2 class="font-semibold">Items coming back</h2>
        @foreach ($sale->items as $i)
            @php $left = $i->quantity - (int) ($returned[$i->id] ?? 0); @endphp
            <div class="grid grid-cols-12 gap-2 items-center border-b border-stone-100 pb-2 {{ $left < 1 ? 'opacity-50' : '' }}">
                <div class="col-span-12 sm:col-span-6 text-sm"><span class="font-medium">{{ $i->product_name }}</span> <span class="text-stone-500">({{ $i->variation_label }})</span>
                    <div class="text-xs text-stone-500">Sold {{ $i->quantity }} at {{ \App\Support\Money::fmt($i->unit_price) }} &middot; can still return {{ $left }}</div></div>
                <input type="number" min="0" max="{{ $left }}" name="items[{{ $i->id }}][qty]" value="0" @disabled($left < 1) class="{{ $inp }} col-span-4 sm:col-span-2" aria-label="Quantity to return">
                <select name="items[{{ $i->id }}][condition]" @disabled($left < 1) class="{{ $inp }} col-span-8 sm:col-span-4">
                    <option value="sellable">Good - put back in stock</option><option value="damaged">Damaged - do not restock</option></select>
            </div>
        @endforeach
    </div>
    <div class="bg-white border border-stone-200 rounded-xl p-4 grid sm:grid-cols-2 gap-3" x-data="{ type: 'refund' }">
        <label class="text-xs">What does the customer get?
            <select name="type" x-model="type" class="{{ $inp }} w-full">
                <option value="refund">Refund (money back)</option><option value="exchange">Exchange (store credit for new items)</option><option value="store_credit">Store credit</option></select></label>
        <label class="text-xs">Reason<select name="reason" required class="{{ $inp }} w-full">@foreach ($reasons as $r)<option>{{ $r }}</option>@endforeach</select></label>
        <label class="text-xs" x-show="type==='refund'">Refund paid by<select name="refund_method" class="{{ $inp }} w-full"><option value="cash">Cash</option><option value="mobile_money">Mobile Money</option><option value="card">Card</option><option value="bank_transfer">Bank Transfer</option></select></label>
        <label class="text-xs" x-show="type==='refund'">Reference (if not cash)<input name="refund_reference" class="{{ $inp }} w-full"></label>
        <label class="text-xs sm:col-span-2">Notes<input name="notes" class="{{ $inp }} w-full"></label>
        <p class="text-xs text-stone-500 sm:col-span-2" x-show="type!=='refund'">Exchanges and store credit are added to the customer's store credit. They can use it as a payment method at the POS.</p>
        <p class="text-xs text-stone-500 sm:col-span-2">If the customer still owes money on this sale, the return first reduces what they owe.</p>
    </div>
    <button class="rounded-xl bg-stone-900 text-white px-6 py-3 text-sm" onclick="return confirm('Process this return? Stock and balances will change.')">Process return</button>
</form>
@endif
@endsection
