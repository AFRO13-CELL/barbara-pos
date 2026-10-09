@extends('layouts.boutique')
@section('title', 'Purchases')
@section('content')
@php $inp = 'rounded-lg border-stone-300 text-sm'; @endphp
<form method="GET" class="mb-4 flex flex-wrap gap-2" x-data>
    <input type="search" name="q" value="{{ request('q') }}" autofocus onfocus="this.setSelectionRange(this.value.length, this.value.length)"
           @input.debounce.500ms="$el.form.submit()" placeholder="Purchase no., supplier or product..." class="{{ $inp }} flex-1 min-w-[220px]">
    <select name="status" class="{{ $inp }}" @change="$el.form.submit()">
        <option value="">Any status</option>
        <option value="ordered" @selected(request('status') === 'ordered')>Ordered (not received)</option>
        <option value="received" @selected(request('status') === 'received')>Received</option>
    </select>
    <input type="date" name="from" value="{{ request('from') }}" class="{{ $inp }}" @change="$el.form.submit()">
    <input type="date" name="to" value="{{ request('to') }}" class="{{ $inp }}" @change="$el.form.submit()">
    @if (auth()->user()->can_do('purchases.create'))<a href="{{ route('purchases.create') }}" class="rounded-lg bg-stone-900 text-white px-4 py-2 text-sm">+ New purchase</a>@endif
</form>
<div class="bg-white border border-stone-200 rounded-xl overflow-x-auto">
<table class="min-w-full text-sm">
    <thead class="bg-stone-100 text-left text-stone-600"><tr><th class="px-4 py-3">Purchase</th><th class="px-4 py-3">Date</th><th class="px-4 py-3">Supplier</th><th class="px-4 py-3">Items</th><th class="px-4 py-3">Total</th><th class="px-4 py-3">Owed</th><th class="px-4 py-3">Status</th></tr></thead>
    <tbody class="divide-y divide-stone-100">
    @forelse ($purchases as $p)
        <tr>
            <td class="px-4 py-3"><a class="underline font-medium" href="{{ route('purchases.show', $p) }}">{{ $p->purchase_number }}</a></td>
            <td class="px-4 py-3">{{ $p->purchase_date->format('d M Y') }}</td>
            <td class="px-4 py-3">{{ $p->supplier?->name }}</td>
            <td class="px-4 py-3">{{ $p->items_count }}</td>
            <td class="px-4 py-3">{{ \App\Support\Money::fmt($p->total) }}</td>
            <td class="px-4 py-3 {{ $p->balance > 0 ? 'text-red-700 font-medium' : '' }}">{{ \App\Support\Money::fmt($p->balance) }}</td>
            <td class="px-4 py-3"><span class="rounded-full px-2.5 py-1 text-xs {{ $p->status === 'received' ? 'bg-emerald-100 text-emerald-700' : 'bg-amber-100 text-amber-800' }}">{{ $p->status === 'received' ? 'Received' : 'Ordered' }}</span></td>
        </tr>
    @empty
        <tr><td colspan="7" class="px-4 py-10 text-center text-stone-500">No purchases found.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div class="mt-4">{{ $purchases->links() }}</div>
@endsection
