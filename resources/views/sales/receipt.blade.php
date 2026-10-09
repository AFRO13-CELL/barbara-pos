<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="utf-8"><meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $sale->receipt_number }}</title>
    @vite(['resources/css/app.css'])
    <style>@media print { .no-print { display: none !important; } body { background: #fff !important; } .sheet { box-shadow: none !important; border: 0 !important; } }</style>
</head>
@php
    $S = fn ($k, $d = null) => \App\Models\Setting::get($k, $d);
    $label = fn ($m) => ucwords(str_replace('_', ' ', $m));
    $me = auth()->user();
@endphp
<body class="bg-stone-100 text-stone-800 antialiased py-6">
<div class="no-print max-w-md mx-auto px-3 mb-3 flex flex-wrap gap-2 text-sm">
    <button onclick="window.print()" class="rounded-lg bg-stone-900 text-white px-4 py-2">Print / Save as PDF</button>
    @if ($me->can_do('pos.create'))<a href="{{ route('pos.index') }}" class="rounded-lg border border-stone-300 bg-white px-4 py-2">New sale</a>@endif
    <a href="{{ route('sales.index') }}" class="rounded-lg border border-stone-300 bg-white px-4 py-2">All sales</a>
    @if ($me->can_do('returns.create'))<a href="{{ route('returns.create', ['sale' => $sale->id]) }}" class="rounded-lg border border-stone-300 bg-white px-4 py-2">Return / refund</a>@endif
</div>

<div class="sheet max-w-md mx-auto bg-white border border-stone-200 rounded-xl shadow-sm p-6 text-sm">
    <div class="text-center">
        @if ($S('business_logo'))<img src="{{ asset('storage/' . $S('business_logo')) }}" alt="" class="mx-auto h-16 mb-1">@endif
        <div class="text-xl font-semibold">{{ $S('business_name', "Barbara's Boutique") }}</div>
        @if ($S('business_address'))<div>{{ $S('business_address') }}</div>@endif
        @if ($S('business_phone'))<div>{{ $S('business_phone') }}</div>@endif
        @if ($S('business_email'))<div>{{ $S('business_email') }}</div>@endif
    </div>
    <hr class="my-3 border-dashed">
    <div class="flex justify-between"><span>Receipt</span><span class="font-medium">{{ $sale->receipt_number }}</span></div>
    <div class="flex justify-between"><span>Date</span><span>{{ $sale->created_at->format('d M Y') }}</span></div>
    <div class="flex justify-between"><span>Time</span><span>{{ $sale->created_at->format('H:i') }}</span></div>
    <div class="flex justify-between"><span>Cashier</span><span>{{ $sale->user?->name }}</span></div>
    <div class="flex justify-between"><span>Customer</span><span>{{ $sale->customer?->name ?? 'Walk-in' }}</span></div>
    <hr class="my-3 border-dashed">
    @foreach ($sale->items as $i)
        <div class="mb-2">
            <div class="font-medium">{{ $i->product_name }}</div>
            <div class="flex justify-between text-stone-600">
                <span>{{ $i->variation_label }} &middot; {{ $i->quantity }} x {{ \App\Support\Money::fmt($i->unit_price) }}</span>
                <span>{{ \App\Support\Money::fmt($i->line_total) }}</span>
            </div>
        </div>
    @endforeach
    <hr class="my-3 border-dashed">
    <div class="flex justify-between"><span>Subtotal</span><span>{{ \App\Support\Money::fmt($sale->subtotal) }}</span></div>
    @if ($sale->discount_amount > 0)<div class="flex justify-between"><span>Discount ({{ rtrim(rtrim($sale->discount_percent, '0'), '.') }}%)</span><span>- {{ \App\Support\Money::fmt($sale->discount_amount) }}</span></div>@endif
    <div class="flex justify-between text-base font-semibold"><span>Total</span><span>{{ \App\Support\Money::fmt($sale->total) }}</span></div>
    <div class="flex justify-between"><span>Amount paid</span><span>{{ \App\Support\Money::fmt($sale->amount_paid) }}</span></div>
    <div class="flex justify-between"><span>Balance</span><span>{{ \App\Support\Money::fmt($sale->balance) }}</span></div>
    @if ($sale->balance > 0 && $sale->due_date)<div class="flex justify-between"><span>Due date</span><span>{{ $sale->due_date->format('d M Y') }}</span></div>@endif
    <hr class="my-3 border-dashed">
    @foreach ($sale->payments as $p)
        <div class="flex justify-between text-stone-600">
            <span>{{ $label($p->method) }}{{ $p->provider ? ' ('.$p->provider.')' : '' }}{{ $p->reference ? ' ref '.$p->reference : '' }}</span>
            <span>{{ \App\Support\Money::fmt($p->amount) }}</span>
        </div>
    @endforeach
    <div class="text-center mt-4">{{ $S('receipt_footer', 'Thank you for shopping with us.') }}</div>
</div>

@if ($sale->balance > 0 && $me->can_do('credit.edit'))
<form method="POST" action="{{ route('sales.payments.store', $sale) }}" class="no-print max-w-md mx-auto mt-4 bg-white border border-stone-200 rounded-xl p-4 text-sm space-y-2">
    @csrf
    <div class="font-semibold">Record a payment (owes {{ \App\Support\Money::fmt($sale->balance) }})</div>
    @if (session('status'))<div class="rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 px-3 py-2">{{ session('status') }}</div>@endif
    @if ($errors->any())<div class="rounded-lg bg-red-50 border border-red-200 text-red-800 px-3 py-2">{{ $errors->first() }}</div>@endif
    <div class="grid grid-cols-2 gap-2">
        <select name="method" class="rounded-lg border-stone-300 text-sm">
            <option value="cash">Cash</option><option value="mobile_money">Mobile Money</option><option value="card">Card</option><option value="bank_transfer">Bank Transfer</option>
        </select>
        <input name="amount" type="number" step="0.01" min="0.01" max="{{ $sale->balance }}" value="{{ $sale->balance }}" class="rounded-lg border-stone-300 text-sm">
        <input name="provider" placeholder="Provider / bank (if applicable)" class="rounded-lg border-stone-300 text-sm">
        <input name="reference" placeholder="Reference (if not cash)" class="rounded-lg border-stone-300 text-sm">
    </div>
    <button class="rounded-lg bg-stone-900 text-white px-4 py-2">Save payment</button>
</form>
@elseif (session('status'))
<div class="no-print max-w-md mx-auto mt-4 rounded-lg bg-emerald-50 border border-emerald-200 text-emerald-800 px-3 py-2 text-sm">{{ session('status') }}</div>
@endif
</body>
</html>
