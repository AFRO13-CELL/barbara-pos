@extends('layouts.boutique')
@section('title', $supplier->name)
@section('content')
@php $me = auth()->user(); $inp = 'rounded-lg border-stone-300 text-sm w-full'; $edit = $me->can_do('suppliers.edit'); @endphp
<div class="grid grid-cols-3 gap-3 mb-4">
    <div class="bg-white border border-stone-200 rounded-xl p-4"><div class="text-xs text-stone-500">Purchases</div><div class="text-xl font-semibold">{{ $stats['count'] }}</div></div>
    <div class="bg-white border border-stone-200 rounded-xl p-4"><div class="text-xs text-stone-500">Total bought</div><div class="text-xl font-semibold">{{ \App\Support\Money::fmt($stats['total']) }}</div></div>
    <div class="bg-white border border-red-200 rounded-xl p-4"><div class="text-xs text-stone-500">We owe</div><div class="text-xl font-semibold text-red-700">{{ \App\Support\Money::fmt($stats['balance']) }}</div></div>
</div>
<form method="POST" action="{{ route('suppliers.update', $supplier) }}" class="bg-white border border-stone-200 rounded-xl p-4 mb-4 grid sm:grid-cols-2 gap-2">
    @csrf @method('PUT')
    <label class="text-xs">Name<input name="name" required value="{{ old('name', $supplier->name) }}" class="{{ $inp }}" @disabled(!$edit)></label>
    <label class="text-xs">Contact person<input name="contact_person" value="{{ old('contact_person', $supplier->contact_person) }}" class="{{ $inp }}" @disabled(!$edit)></label>
    <label class="text-xs">Phone<input name="phone" value="{{ old('phone', $supplier->phone) }}" class="{{ $inp }}" @disabled(!$edit)></label>
    <label class="text-xs">Email<input name="email" type="email" value="{{ old('email', $supplier->email) }}" class="{{ $inp }}" @disabled(!$edit)></label>
    <label class="text-xs sm:col-span-2">Address<input name="address" value="{{ old('address', $supplier->address) }}" class="{{ $inp }}" @disabled(!$edit)></label>
    <label class="text-xs sm:col-span-2">Notes<textarea name="notes" rows="2" class="{{ $inp }}" @disabled(!$edit)>{{ old('notes', $supplier->notes) }}</textarea></label>
    @if ($edit)<button class="rounded-lg bg-stone-900 text-white px-4 py-2 text-sm w-fit">Save supplier</button>@endif
</form>
<div class="mb-4 text-sm"><span class="font-semibold">Products supplied:</span>
    {{ $products->isEmpty() ? 'None recorded yet.' : $products->implode(', ') }}</div>
<div class="flex items-center justify-between mb-2"><h2 class="font-semibold">Purchase history</h2>
    @if ($me->can_do('purchases.create'))<a href="{{ route('purchases.create', ['supplier' => $supplier->id]) }}" class="rounded-lg bg-stone-900 text-white px-3 py-2 text-sm">+ New purchase</a>@endif</div>
<div class="bg-white border border-stone-200 rounded-xl overflow-x-auto">
<table class="min-w-full text-sm">
    <thead class="bg-stone-100 text-left text-stone-600"><tr><th class="px-4 py-3">Purchase</th><th class="px-4 py-3">Date</th><th class="px-4 py-3">Status</th><th class="px-4 py-3">Total</th><th class="px-4 py-3">Owed</th></tr></thead>
    <tbody class="divide-y divide-stone-100">
    @forelse ($purchases as $p)
        <tr><td class="px-4 py-3"><a class="underline" href="{{ route('purchases.show', $p) }}">{{ $p->purchase_number }}</a></td>
            <td class="px-4 py-3">{{ $p->purchase_date->format('d M Y') }}</td><td class="px-4 py-3">{{ ucfirst($p->status) }}</td>
            <td class="px-4 py-3">{{ \App\Support\Money::fmt($p->total) }}</td>
            <td class="px-4 py-3 {{ $p->balance > 0 ? 'text-red-700' : '' }}">{{ \App\Support\Money::fmt($p->balance) }}</td></tr>
    @empty
        <tr><td colspan="5" class="px-4 py-8 text-center text-stone-500">No purchases yet.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div class="mt-4">{{ $purchases->links() }}</div>
@endsection
