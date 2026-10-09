@extends('layouts.boutique')
@section('title', 'Expenses')
@section('content')
@php $me = auth()->user(); $inp = 'rounded-lg border-stone-300 text-sm'; $label = fn ($m) => ucwords(str_replace('_', ' ', $m)); $methods = ['cash', 'mobile_money', 'card', 'bank_transfer']; @endphp
<div class="grid grid-cols-3 gap-3 mb-4">
    <div class="bg-white border border-stone-200 rounded-xl p-4"><div class="text-xs text-stone-500">Today</div><div class="text-xl font-semibold">{{ \App\Support\Money::fmt($today) }}</div></div>
    <div class="bg-white border border-stone-200 rounded-xl p-4"><div class="text-xs text-stone-500">This month</div><div class="text-xl font-semibold">{{ \App\Support\Money::fmt($month) }}</div></div>
    <div class="bg-white border border-amber-200 rounded-xl p-4"><div class="text-xs text-stone-500">Total of the list below</div><div class="text-xl font-semibold">{{ \App\Support\Money::fmt($filteredTotal) }}</div></div>
</div>
<form method="GET" class="mb-4 flex flex-wrap gap-2" x-data>
    <input type="search" name="q" value="{{ request('q') }}" autofocus onfocus="this.setSelectionRange(this.value.length, this.value.length)"
           @input.debounce.500ms="$el.form.submit()" placeholder="Search description or category..." class="{{ $inp }} flex-1 min-w-[200px]">
    <select name="category" class="{{ $inp }}" @change="$el.form.submit()"><option value="">All categories</option>
        @foreach ($categories as $c)<option @selected(request('category') === $c)>{{ $c }}</option>@endforeach</select>
    <select name="method" class="{{ $inp }}" @change="$el.form.submit()"><option value="">Any method</option>
        @foreach ($methods as $m)<option value="{{ $m }}" @selected(request('method') === $m)>{{ $label($m) }}</option>@endforeach</select>
    <input type="date" name="from" value="{{ request('from') }}" class="{{ $inp }}" @change="$el.form.submit()">
    <input type="date" name="to" value="{{ request('to') }}" class="{{ $inp }}" @change="$el.form.submit()">
</form>
@if ($me->can_do('expenses.create'))
<details class="mb-4 bg-white border border-stone-200 rounded-xl p-4" @if ($errors->any()) open @endif>
    <summary class="cursor-pointer text-sm font-medium">+ Add expense</summary>
    <form method="POST" action="{{ route('expenses.store') }}" enctype="multipart/form-data" class="mt-3 grid sm:grid-cols-2 gap-2">
        @csrf
        <input name="description" required placeholder="Description" value="{{ old('description') }}" class="{{ $inp }} sm:col-span-2">
        <select name="category" required class="{{ $inp }}">@foreach ($categories as $c)<option @selected(old('category') === $c)>{{ $c }}</option>@endforeach</select>
        <input name="amount" type="number" step="0.01" min="0.01" required placeholder="Amount" value="{{ old('amount') }}" class="{{ $inp }}">
        <input name="expense_date" type="date" required value="{{ old('expense_date', now()->format('Y-m-d')) }}" class="{{ $inp }}">
        <select name="payment_method" class="{{ $inp }}">@foreach ($methods as $m)<option value="{{ $m }}">{{ $label($m) }}</option>@endforeach</select>
        <label class="text-xs sm:col-span-2">Receipt (photo or PDF, optional)<input name="attachment" type="file" accept=".jpg,.jpeg,.png,.pdf" class="{{ $inp }} w-full bg-white"></label>
        <button class="rounded-lg bg-stone-900 text-white px-4 py-2 text-sm w-fit">Save expense</button>
    </form>
</details>
@endif
<div class="space-y-2">
@forelse ($expenses as $e)
    <div class="bg-white border border-stone-200 rounded-xl p-3">
        <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
            <div class="flex-1 min-w-[200px]"><div class="font-medium">{{ $e->description }}</div>
                <div class="text-xs text-stone-500">{{ $e->category }} &middot; {{ $e->expense_date->format('d M Y') }} &middot; {{ $label($e->payment_method) }} &middot; {{ $e->user?->name }}</div></div>
            <div class="font-semibold">{{ \App\Support\Money::fmt($e->amount) }}</div>
            @if ($e->attachment_path)<a href="{{ route('expenses.attachment', $e) }}" target="_blank" class="text-xs underline">Receipt</a>@endif
        </div>
        @if ($me->can_do('expenses.edit') || $me->can_do('expenses.delete'))
        <details class="mt-2">
            <summary class="cursor-pointer text-xs underline">Edit / delete</summary>
            @if ($me->can_do('expenses.edit'))
            <form method="POST" action="{{ route('expenses.update', $e) }}" enctype="multipart/form-data" class="mt-2 grid sm:grid-cols-2 gap-2">
                @csrf @method('PUT')
                <input name="description" required value="{{ $e->description }}" class="{{ $inp }} sm:col-span-2">
                <select name="category" class="{{ $inp }}">@foreach ($categories as $c)<option @selected($e->category === $c)>{{ $c }}</option>@endforeach</select>
                <input name="amount" type="number" step="0.01" min="0.01" required value="{{ $e->amount }}" class="{{ $inp }}">
                <input name="expense_date" type="date" required value="{{ $e->expense_date->format('Y-m-d') }}" class="{{ $inp }}">
                <select name="payment_method" class="{{ $inp }}">@foreach ($methods as $m)<option value="{{ $m }}" @selected($e->payment_method === $m)>{{ $label($m) }}</option>@endforeach</select>
                <input name="attachment" type="file" accept=".jpg,.jpeg,.png,.pdf" class="{{ $inp }} sm:col-span-2 bg-white">
                <button class="rounded-lg border border-stone-300 px-3 py-2 text-sm w-fit">Save changes</button>
            </form>
            @endif
            @if ($me->can_do('expenses.delete'))
            <form method="POST" action="{{ route('expenses.destroy', $e) }}" class="mt-2" onsubmit="return confirm('Delete this expense?')">
                @csrf @method('DELETE')<button class="text-xs text-red-600 hover:underline">Delete expense</button></form>
            @endif
        </details>
        @endif
    </div>
@empty
    <div class="bg-white border border-stone-200 rounded-xl p-10 text-center text-stone-500">No expenses found.</div>
@endforelse
</div>
<div class="mt-4">{{ $expenses->links() }}</div>
@endsection
