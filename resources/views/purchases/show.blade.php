@extends('layouts.boutique')
@section('title', $purchase->purchase_number)
@section('content')
@php $me = auth()->user(); $inp = 'rounded-lg border-stone-300 text-sm'; $label = fn ($m) => ucwords(str_replace('_', ' ', $m)); @endphp
<div class="bg-white border border-stone-200 rounded-xl p-4 mb-4 text-sm grid sm:grid-cols-2 gap-1">
    <div>Supplier: <a class="underline" href="{{ route('suppliers.show', $purchase->supplier) }}">{{ $purchase->supplier?->name }}</a></div>
    <div>Date: {{ $purchase->purchase_date->format('d M Y') }}</div>
    <div>Status: <span class="rounded-full px-2.5 py-0.5 text-xs {{ $purchase->status === 'received' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-800' }}">{{ $purchase->status === 'received' ? 'Received' . ($purchase->received_at ? ' ' . $purchase->received_at->format('d M Y') : '') : 'Ordered - stock not added yet' }}</span></div>
    <div>Entered by: {{ $purchase->user?->name }}</div>
    <div>Total: <strong>{{ \App\Support\Money::fmt($purchase->total) }}</strong> &middot; Paid: {{ \App\Support\Money::fmt($purchase->amount_paid) }} &middot; Owed: <strong class="{{ $purchase->balance > 0 ? 'text-red-700' : '' }}">{{ \App\Support\Money::fmt($purchase->balance) }}</strong></div>
</div>
<div class="bg-white border border-stone-200 rounded-xl overflow-x-auto mb-4">
<table class="min-w-full text-sm">
    <thead class="bg-stone-100 text-left text-stone-600"><tr><th class="px-4 py-3">Item</th><th class="px-4 py-3">SKU</th><th class="px-4 py-3">Qty</th><th class="px-4 py-3">Cost</th><th class="px-4 py-3">Line total</th></tr></thead>
    <tbody class="divide-y divide-stone-100">
    @foreach ($purchase->items as $i)
        <tr><td class="px-4 py-3">{{ $i->product_name }} <span class="text-stone-500">({{ $i->variation_label }})</span></td><td class="px-4 py-3">{{ $i->sku }}</td>
            <td class="px-4 py-3">{{ $i->quantity }}</td><td class="px-4 py-3">{{ \App\Support\Money::fmt($i->cost_price) }}</td><td class="px-4 py-3">{{ \App\Support\Money::fmt($i->line_total) }}</td></tr>
    @endforeach
    </tbody>
</table>
</div>

@if ($purchase->status === 'ordered' && $me->can_do('purchases.edit'))
<form method="POST" action="{{ route('purchases.receive', $purchase) }}" class="mb-4 bg-white border border-amber-200 rounded-xl p-4 text-sm space-y-2"
      onsubmit="return confirm('Add these items to inventory now?')">
    @csrf
    <div class="font-semibold">Stock has arrived?</div>
    <label class="flex items-center gap-2"><input type="checkbox" name="update_cost" value="1" checked> Update each product's cost price to this cost</label>
    <button class="rounded-lg bg-stone-900 text-white px-4 py-2">Receive stock</button>
</form>
@endif

<h2 class="font-semibold mb-2">Payments to supplier</h2>
<div class="bg-white border border-stone-200 rounded-xl p-4 text-sm mb-4">
    @forelse ($purchase->payments as $p)
        <div class="flex justify-between py-1"><span>{{ $p->created_at->format('d M Y, H:i') }} &middot; {{ $label($p->method) }}{{ $p->reference ? ' ref ' . $p->reference : '' }} &middot; {{ $p->user?->name }}</span><span>{{ \App\Support\Money::fmt($p->amount) }}</span></div>
    @empty <div class="text-stone-500">No payments yet.</div> @endforelse
</div>
@if ($purchase->balance > 0 && $me->can_do('purchases.edit'))
<form method="POST" action="{{ route('purchases.payments.store', $purchase) }}" class="bg-white border border-stone-200 rounded-xl p-4 text-sm space-y-2">
    @csrf
    <div class="font-semibold">Record a payment (owed {{ \App\Support\Money::fmt($purchase->balance) }})</div>
    <div class="grid sm:grid-cols-3 gap-2">
        <select name="method" class="{{ $inp }}"><option value="cash">Cash</option><option value="mobile_money">Mobile Money</option><option value="card">Card</option><option value="bank_transfer">Bank Transfer</option></select>
        <input name="amount" type="number" step="0.01" min="0.01" max="{{ $purchase->balance }}" value="{{ $purchase->balance }}" class="{{ $inp }}">
        <input name="reference" placeholder="Reference (if not cash)" class="{{ $inp }}">
    </div>
    <button class="rounded-lg bg-stone-900 text-white px-4 py-2">Save payment</button>
</form>
@endif
@endsection
