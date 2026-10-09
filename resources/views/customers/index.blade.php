@extends('layouts.boutique')
@section('title', 'Customers')
@section('content')
@php $me = auth()->user(); $inp = 'rounded-lg border-stone-300 text-sm'; @endphp
<form method="GET" class="mb-4 flex flex-wrap gap-2" x-data>
    <input type="search" name="q" value="{{ request('q') }}" autofocus onfocus="this.setSelectionRange(this.value.length, this.value.length)"
           @input.debounce.500ms="$el.form.submit()" placeholder="Search name, phone or email..." class="{{ $inp }} flex-1 min-w-[220px]">
</form>
@if ($me->can_do('customers.create'))
<details class="mb-4 bg-white border border-stone-200 rounded-xl p-4">
    <summary class="cursor-pointer text-sm font-medium">+ Add customer</summary>
    <form method="POST" action="{{ route('customers.store') }}" class="mt-3 grid sm:grid-cols-2 gap-2">
        @csrf
        <input name="name" required placeholder="Name" class="{{ $inp }}">
        <input name="phone" placeholder="Phone" class="{{ $inp }}">
        <input name="email" type="email" placeholder="Email" class="{{ $inp }}">
        <input name="address" placeholder="Address" class="{{ $inp }}">
        <input name="notes" placeholder="Notes" class="{{ $inp }} sm:col-span-2">
        <button class="rounded-lg bg-stone-900 text-white px-4 py-2 text-sm sm:col-span-2 w-fit">Save customer</button>
    </form>
</details>
@endif
<div class="bg-white border border-stone-200 rounded-xl overflow-x-auto">
<table class="min-w-full text-sm">
    <thead class="bg-stone-100 text-left text-stone-600"><tr><th class="px-4 py-3">Name</th><th class="px-4 py-3">Phone</th><th class="px-4 py-3">Purchases</th><th class="px-4 py-3">Total spent</th><th class="px-4 py-3">Owes</th></tr></thead>
    <tbody class="divide-y divide-stone-100">
    @forelse ($customers as $c)
        <tr>
            <td class="px-4 py-3"><a class="underline font-medium" href="{{ route('customers.show', $c) }}">{{ $c->name }}</a></td>
            <td class="px-4 py-3">{{ $c->phone }}</td>
            <td class="px-4 py-3">{{ $c->sales_count }}</td>
            <td class="px-4 py-3">{{ \App\Support\Money::fmt($c->sales_sum_total ?? 0) }}</td>
            <td class="px-4 py-3 {{ ($c->sales_sum_balance ?? 0) > 0 ? 'text-red-700 font-medium' : '' }}">{{ \App\Support\Money::fmt($c->sales_sum_balance ?? 0) }}</td>
        </tr>
    @empty
        <tr><td colspan="5" class="px-4 py-10 text-center text-stone-500">No customers found.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div class="mt-4">{{ $customers->links() }}</div>
@endsection
