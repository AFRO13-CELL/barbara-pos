@extends('layouts.boutique')
@section('title', 'Stock Movements')
@section('content')
@php $inp = 'rounded-lg border-stone-300 text-sm'; @endphp
<form method="GET" class="mb-4 flex flex-wrap gap-2" x-data>
    <input type="search" name="q" value="{{ request('q') }}" autofocus onfocus="this.setSelectionRange(this.value.length, this.value.length)"
           @input.debounce.500ms="$el.form.submit()" placeholder="Search product, SKU, barcode..." class="{{ $inp }} flex-1 min-w-[220px]">
    <select name="type" class="{{ $inp }}" @change="$el.form.submit()">
        <option value="">All types</option>
        @foreach (['initial','purchase','sale','return','damage','adjustment'] as $t)<option value="{{ $t }}" @selected(request('type') === $t)>{{ ucfirst($t) }}</option>@endforeach
    </select>
</form>
<div class="bg-white border border-stone-200 rounded-xl overflow-x-auto">
<table class="min-w-full text-sm">
    <thead class="bg-stone-100 text-left text-stone-600"><tr>
        <th class="px-4 py-3">When</th><th class="px-4 py-3">Item</th><th class="px-4 py-3">Type</th><th class="px-4 py-3">Change</th>
        <th class="px-4 py-3">Before &rarr; After</th><th class="px-4 py-3">Reason</th><th class="px-4 py-3">By</th></tr></thead>
    <tbody class="divide-y divide-stone-100">
    @forelse ($movements as $m)
        <tr>
            <td class="px-4 py-3 whitespace-nowrap">{{ $m->created_at->format('d M Y, H:i') }}</td>
            <td class="px-4 py-3">{{ $m->variation?->product?->name }} <span class="text-stone-500">({{ $m->variation?->options_label }})</span></td>
            <td class="px-4 py-3">{{ ucfirst($m->type) }}</td>
            <td class="px-4 py-3 font-medium {{ $m->quantity > 0 ? 'text-emerald-700' : 'text-red-700' }}">{{ $m->quantity > 0 ? '+' : '' }}{{ $m->quantity }}</td>
            <td class="px-4 py-3">{{ $m->previous_stock }} &rarr; {{ $m->new_stock }}</td>
            <td class="px-4 py-3">{{ $m->reason }}@if ($m->note)<div class="text-xs text-stone-500">{{ $m->note }}</div>@endif</td>
            <td class="px-4 py-3">{{ $m->user?->name ?? 'System' }}</td>
        </tr>
    @empty
        <tr><td colspan="7" class="px-4 py-10 text-center text-stone-500">No movements yet.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div class="mt-4">{{ $movements->links() }}</div>
@endsection
