@extends('layouts.boutique')
@section('title', $customer->name)
@section('content')
@php $me = auth()->user(); $inp = 'rounded-lg border-stone-300 text-sm w-full'; @endphp
<div class="grid grid-cols-2 lg:grid-cols-5 gap-3 mb-4">
    <div class="bg-white border border-stone-200 rounded-xl p-4"><div class="text-xs text-stone-500">Total purchases</div><div class="text-xl font-semibold">{{ $stats['purchases'] }}</div></div>
    <div class="bg-white border border-stone-200 rounded-xl p-4"><div class="text-xs text-stone-500">Total spent</div><div class="text-xl font-semibold">{{ \App\Support\Money::fmt($stats['spent']) }}</div></div>
    <div class="bg-white border border-red-200 rounded-xl p-4"><div class="text-xs text-stone-500">Outstanding balance</div><div class="text-xl font-semibold text-red-700">{{ \App\Support\Money::fmt($stats['balance']) }}</div></div>
    <div class="bg-white border border-emerald-200 rounded-xl p-4"><div class="text-xs text-stone-500">Store credit</div><div class="text-xl font-semibold text-emerald-700">{{ \App\Support\Money::fmt($customer->store_credit) }}</div></div>
    <div class="bg-white border border-stone-200 rounded-xl p-4"><div class="text-xs text-stone-500">Last purchase</div><div class="text-xl font-semibold">{{ $stats['last'] ? \Illuminate\Support\Carbon::parse($stats['last'])->format('d M Y') : '-' }}</div></div>
</div>

<form method="POST" action="{{ route('customers.update', $customer) }}" class="bg-white border border-stone-200 rounded-xl p-4 mb-6 grid sm:grid-cols-2 gap-2">
    @csrf @method('PUT')
    <label class="text-xs">Name<input name="name" required value="{{ old('name', $customer->name) }}" class="{{ $inp }}" @disabled(!$me->can_do('customers.edit'))></label>
    <label class="text-xs">Phone<input name="phone" value="{{ old('phone', $customer->phone) }}" class="{{ $inp }}" @disabled(!$me->can_do('customers.edit'))></label>
    <label class="text-xs">Email<input name="email" type="email" value="{{ old('email', $customer->email) }}" class="{{ $inp }}" @disabled(!$me->can_do('customers.edit'))></label>
    <label class="text-xs">Address<input name="address" value="{{ old('address', $customer->address) }}" class="{{ $inp }}" @disabled(!$me->can_do('customers.edit'))></label>
    <label class="text-xs sm:col-span-2">Notes<textarea name="notes" rows="2" class="{{ $inp }}" @disabled(!$me->can_do('customers.edit'))>{{ old('notes', $customer->notes) }}</textarea></label>
    @if ($me->can_do('customers.edit'))<button class="rounded-lg bg-stone-900 text-white px-4 py-2 text-sm w-fit">Save customer</button>@endif
</form>

<h2 class="font-semibold mb-2">Purchase history</h2>
<div class="bg-white border border-stone-200 rounded-xl overflow-x-auto">
<table class="min-w-full text-sm">
    <thead class="bg-stone-100 text-left text-stone-600"><tr><th class="px-4 py-3">Receipt</th><th class="px-4 py-3">Date</th><th class="px-4 py-3">Total</th><th class="px-4 py-3">Balance</th></tr></thead>
    <tbody class="divide-y divide-stone-100">
    @forelse ($sales as $s)
        <tr>
            <td class="px-4 py-3"><a class="underline" href="{{ route('sales.show', $s) }}">{{ $s->receipt_number }}</a></td>
            <td class="px-4 py-3">{{ $s->created_at->format('d M Y, H:i') }}</td>
            <td class="px-4 py-3">{{ \App\Support\Money::fmt($s->total) }}</td>
            <td class="px-4 py-3 {{ $s->balance > 0 ? 'text-red-700' : '' }}">{{ \App\Support\Money::fmt($s->balance) }}</td>
        </tr>
    @empty
        <tr><td colspan="4" class="px-4 py-8 text-center text-stone-500">No purchases yet.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<div class="mt-4">{{ $sales->links() }}</div>
@endsection
