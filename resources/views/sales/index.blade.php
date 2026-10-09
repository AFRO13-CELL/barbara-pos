@extends('layouts.boutique')
@section('title', 'Sales')
@section('content')
@php $canProfit = auth()->user()->can_do('profit.view'); $inp = 'rounded-lg border-stone-300 text-sm'; @endphp
<form method="GET" class="mb-4 flex flex-wrap gap-2" x-data>
    <input type="search" name="q" value="{{ request('q') }}" autofocus onfocus="this.setSelectionRange(this.value.length, this.value.length)"
           @input.debounce.500ms="$el.form.submit()" placeholder="Receipt no., customer, product, staff..." class="{{ $inp }} flex-1 min-w-[220px]">
    <select name="method" class="{{ $inp }}" @change="$el.form.submit()">
        <option value="">Any payment method</option>
        @foreach (['cash' => 'Cash', 'mobile_money' => 'Mobile Money', 'card' => 'Card', 'bank_transfer' => 'Bank Transfer'] as $k => $l)<option value="{{ $k }}" @selected(request('method') === $k)>{{ $l }}</option>@endforeach
    </select>
    <select name="status" class="{{ $inp }}" @change="$el.form.submit()">
        <option value="">Any status</option>
        @foreach (['paid' => 'Paid', 'partial' => 'Part paid', 'credit' => 'Credit'] as $k => $l)<option value="{{ $k }}" @selected(request('status') === $k)>{{ $l }}</option>@endforeach
    </select>
    <input type="date" name="from" value="{{ request('from') }}" class="{{ $inp }}" @change="$el.form.submit()" title="From">
    <input type="date" name="to" value="{{ request('to') }}" class="{{ $inp }}" @change="$el.form.submit()" title="To">
    @if (auth()->user()->can_do('pos.create'))<a href="{{ route('pos.index') }}" class="rounded-lg bg-stone-900 text-white px-4 py-2 text-sm">+ New sale</a>@endif
</form>
<div class="bg-white border border-stone-200 rounded-xl overflow-x-auto">
<table class="min-w-full text-sm">
    <thead class="bg-stone-100 text-left text-stone-600"><tr>
        <th class="px-4 py-3">Receipt</th><th class="px-4 py-3">Date</th><th class="px-4 py-3">Customer</th><th class="px-4 py-3">Staff</th>
        <th class="px-4 py-3">Items</th><th class="px-4 py-3">Total</th><th class="px-4 py-3">Status</th>@if ($canProfit)<th class="px-4 py-3">Profit</th>@endif</tr></thead>
    <tbody class="divide-y divide-stone-100">
    @forelse ($sales as $s)
        <tr>
            <td class="px-4 py-3"><a class="underline font-medium" href="{{ route('sales.show', $s) }}">{{ $s->receipt_number }}</a></td>
            <td class="px-4 py-3 whitespace-nowrap">{{ $s->created_at->format('d M Y, H:i') }}</td>
            <td class="px-4 py-3">{{ $s->customer?->name ?? 'Walk-in' }}</td>
            <td class="px-4 py-3">{{ $s->user?->name }}</td>
            <td class="px-4 py-3">{{ $s->items_count }}</td>
            <td class="px-4 py-3 font-medium">{{ \App\Support\Money::fmt($s->total) }}</td>
            <td class="px-4 py-3">
                <span class="rounded-full px-2.5 py-1 text-xs {{ $s->payment_status === 'paid' ? 'bg-emerald-100 text-emerald-700' : ($s->payment_status === 'partial' ? 'bg-amber-100 text-amber-800' : 'bg-red-100 text-red-700') }}">
                    {{ $s->payment_status === 'paid' ? 'Paid' : ($s->payment_status === 'partial' ? 'Part paid' : 'Credit') }}</span>
                @if ($s->balance > 0)<div class="text-xs text-stone-500">Owes {{ \App\Support\Money::fmt($s->balance) }}</div>@endif
            </td>
            @if ($canProfit)<td class="px-4 py-3">{{ \App\Support\Money::fmt($s->gross_profit) }}</td>@endif
        </tr>
    @empty
        <tr><td colspan="8" class="px-4 py-10 text-center text-stone-500">No sales found.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div class="mt-4">{{ $sales->links() }}</div>
@endsection
