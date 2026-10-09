@extends('layouts.boutique')
@section('title', 'Returns')
@section('content')
@php $inp = 'rounded-lg border-stone-300 text-sm'; $typeLabel = ['refund' => 'Refund', 'exchange' => 'Exchange', 'store_credit' => 'Store credit']; @endphp
<form method="GET" class="mb-4 flex flex-wrap gap-2" x-data>
    <input type="search" name="q" value="{{ request('q') }}" autofocus onfocus="this.setSelectionRange(this.value.length, this.value.length)"
           @input.debounce.500ms="$el.form.submit()" placeholder="Return no., receipt no., customer or product..." class="{{ $inp }} flex-1 min-w-[220px]">
    @if (auth()->user()->can_do('returns.create'))<a href="{{ route('returns.create') }}" class="rounded-lg bg-stone-900 text-white px-4 py-2 text-sm">+ New return</a>@endif
</form>
<div class="bg-white border border-stone-200 rounded-xl overflow-x-auto">
<table class="min-w-full text-sm">
    <thead class="bg-stone-100 text-left text-stone-600"><tr><th class="px-4 py-3">Return</th><th class="px-4 py-3">Date</th><th class="px-4 py-3">Receipt</th><th class="px-4 py-3">Customer</th><th class="px-4 py-3">Items</th><th class="px-4 py-3">Type</th><th class="px-4 py-3">Value</th><th class="px-4 py-3">By</th></tr></thead>
    <tbody class="divide-y divide-stone-100">
    @forelse ($returns as $r)
        <tr>
            <td class="px-4 py-3 font-medium">{{ $r->return_number }}<div class="text-xs text-stone-500 font-normal">{{ $r->reason }}</div></td>
            <td class="px-4 py-3 whitespace-nowrap">{{ $r->created_at->format('d M Y, H:i') }}</td>
            <td class="px-4 py-3"><a class="underline" href="{{ route('sales.show', $r->sale_id) }}">{{ $r->sale?->receipt_number }}</a></td>
            <td class="px-4 py-3">{{ $r->customer?->name ?? 'Walk-in' }}</td>
            <td class="px-4 py-3">@foreach ($r->items as $i){{ $i->quantity }} x {{ $i->product_name }} ({{ $i->variation_label }}, {{ $i->item_condition }})@if (!$loop->last)<br>@endif @endforeach</td>
            <td class="px-4 py-3">{{ $typeLabel[$r->type] ?? $r->type }}</td>
            <td class="px-4 py-3">{{ \App\Support\Money::fmt($r->value) }}@if ($r->balance_reduced > 0)<div class="text-xs text-stone-500">{{ \App\Support\Money::fmt($r->balance_reduced) }} off unpaid balance</div>@endif</td>
            <td class="px-4 py-3">{{ $r->user?->name }}</td>
        </tr>
    @empty
        <tr><td colspan="8" class="px-4 py-10 text-center text-stone-500">No returns yet.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div class="mt-4">{{ $returns->links() }}</div>
@endsection
