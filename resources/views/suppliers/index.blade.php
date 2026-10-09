@extends('layouts.boutique')
@section('title', 'Suppliers')
@section('content')
@php $me = auth()->user(); $inp = 'rounded-lg border-stone-300 text-sm'; @endphp
<form method="GET" class="mb-4" x-data>
    <input type="search" name="q" value="{{ request('q') }}" autofocus onfocus="this.setSelectionRange(this.value.length, this.value.length)"
           @input.debounce.500ms="$el.form.submit()" placeholder="Search supplier name, phone or product supplied..." class="{{ $inp }} w-full">
</form>
@if ($me->can_do('suppliers.create'))
<details class="mb-4 bg-white border border-stone-200 rounded-xl p-4">
    <summary class="cursor-pointer text-sm font-medium">+ Add supplier</summary>
    <form method="POST" action="{{ route('suppliers.store') }}" class="mt-3 grid sm:grid-cols-2 gap-2">
        @csrf
        <input name="name" required placeholder="Supplier name" class="{{ $inp }}">
        <input name="contact_person" placeholder="Contact person" class="{{ $inp }}">
        <input name="phone" placeholder="Phone" class="{{ $inp }}">
        <input name="email" type="email" placeholder="Email" class="{{ $inp }}">
        <input name="address" placeholder="Address" class="{{ $inp }} sm:col-span-2">
        <input name="notes" placeholder="Notes" class="{{ $inp }} sm:col-span-2">
        <button class="rounded-lg bg-stone-900 text-white px-4 py-2 text-sm w-fit">Save supplier</button>
    </form>
</details>
@endif
<div class="bg-white border border-stone-200 rounded-xl overflow-x-auto">
<table class="min-w-full text-sm">
    <thead class="bg-stone-100 text-left text-stone-600"><tr><th class="px-4 py-3">Supplier</th><th class="px-4 py-3">Phone</th><th class="px-4 py-3">Purchases</th><th class="px-4 py-3">Total bought</th><th class="px-4 py-3">We owe</th></tr></thead>
    <tbody class="divide-y divide-stone-100">
    @forelse ($suppliers as $s)
        <tr>
            <td class="px-4 py-3"><a class="underline font-medium" href="{{ route('suppliers.show', $s) }}">{{ $s->name }}</a>@if ($s->contact_person)<div class="text-xs text-stone-500">{{ $s->contact_person }}</div>@endif</td>
            <td class="px-4 py-3">{{ $s->phone }}</td>
            <td class="px-4 py-3">{{ $s->purchases_count }}</td>
            <td class="px-4 py-3">{{ \App\Support\Money::fmt($s->purchases_sum_total ?? 0) }}</td>
            <td class="px-4 py-3 {{ ($s->purchases_sum_balance ?? 0) > 0 ? 'text-red-700 font-medium' : '' }}">{{ \App\Support\Money::fmt($s->purchases_sum_balance ?? 0) }}</td>
        </tr>
    @empty
        <tr><td colspan="5" class="px-4 py-10 text-center text-stone-500">No suppliers found.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div class="mt-4">{{ $suppliers->links() }}</div>
@endsection
