@extends('layouts.boutique')
@section('title', 'Settings')
@section('content')
@php $inp = 'rounded-lg border-stone-300 text-sm w-full'; $can = auth()->user()->can_do('settings.edit'); $logo = $s('business_logo'); @endphp
<div class="mb-4 flex flex-wrap gap-2 text-sm">
    <a href="{{ route('settings.roles') }}" class="rounded-lg border border-stone-300 bg-white px-3 py-2">Roles and permissions</a>
    <a href="{{ route('backups.index') }}" class="rounded-lg border border-stone-300 bg-white px-3 py-2">Backup schedule</a>
    <a href="{{ route('staff.index') }}" class="rounded-lg border border-stone-300 bg-white px-3 py-2">Staff and discount limits</a>
    <a href="{{ route('audit.index') }}" class="rounded-lg border border-stone-300 bg-white px-3 py-2">Audit log</a>
</div>
<form method="POST" action="{{ route('settings.update') }}" enctype="multipart/form-data" class="space-y-4">
    @csrf
    <section class="bg-white border border-stone-200 rounded-xl p-4 grid sm:grid-cols-2 gap-3">
        <h2 class="font-semibold sm:col-span-2">Business</h2>
        <label class="text-xs">Business name<input name="business_name" required value="{{ old('business_name', $s('business_name', "Barbara's Boutique")) }}" class="{{ $inp }}"></label>
        <label class="text-xs">Phone<input name="business_phone" value="{{ old('business_phone', $s('business_phone')) }}" class="{{ $inp }}"></label>
        <label class="text-xs">Email<input name="business_email" type="email" value="{{ old('business_email', $s('business_email')) }}" class="{{ $inp }}"></label>
        <label class="text-xs">Website<input name="business_website" value="{{ old('business_website', $s('business_website')) }}" class="{{ $inp }}"></label>
        <label class="text-xs sm:col-span-2">Address<input name="business_address" value="{{ old('business_address', $s('business_address')) }}" class="{{ $inp }}"></label>
        <label class="text-xs">Currency code<input name="currency_code" required maxlength="5" value="{{ old('currency_code', $s('currency_code', 'GHS')) }}" class="{{ $inp }}"></label>
        <label class="text-xs">Currency symbol<input name="currency_symbol" required maxlength="5" value="{{ old('currency_symbol', $s('currency_symbol', '₵')) }}" class="{{ $inp }}"></label>
        <div class="sm:col-span-2 text-xs">Logo (shown on receipts)
            @if ($logo)<div class="my-2 flex items-center gap-3"><img src="{{ asset('storage/' . $logo) }}" alt="Logo" class="h-14 rounded border"><label class="flex items-center gap-1"><input type="checkbox" name="remove_logo" value="1"> Remove logo</label></div>@endif
            <input name="logo" type="file" accept="image/png,image/jpeg,image/webp" class="{{ $inp }} bg-white mt-1"></div>
    </section>
    <section class="bg-white border border-stone-200 rounded-xl p-4 grid sm:grid-cols-2 gap-3">
        <h2 class="font-semibold sm:col-span-2">Receipt</h2>
        <label class="text-xs sm:col-span-2">Footer message<input name="receipt_footer" value="{{ old('receipt_footer', $s('receipt_footer', 'Thank you for shopping with us.')) }}" class="{{ $inp }}"></label>
        <p class="text-xs text-stone-500 sm:col-span-2">The receipt also shows the business name, logo, address, phone and email from above.</p>
    </section>
    <section class="bg-white border border-stone-200 rounded-xl p-4 grid sm:grid-cols-2 gap-3">
        <h2 class="font-semibold sm:col-span-2">Inventory</h2>
        <label class="text-xs">Low-stock level for new items<input name="low_stock_threshold" type="number" min="0" required value="{{ old('low_stock_threshold', $s('low_stock_threshold', 3)) }}" class="{{ $inp }}"></label>
        <label class="flex items-center gap-2 text-sm self-end"><input type="checkbox" name="allow_negative_stock" value="1" @checked($s('allow_negative_stock', '0') === '1')> Allow selling more than is in stock (negative stock)</label>
        <p class="text-xs text-stone-500 sm:col-span-2">Who may adjust stock is set under Roles and permissions ("Adjust stock").</p>
    </section>
    <section class="bg-white border border-stone-200 rounded-xl p-4 grid sm:grid-cols-2 gap-3">
        <h2 class="font-semibold sm:col-span-2">Sales</h2>
        <label class="text-xs">Raise a notification when a discount is at least (%)<input name="large_discount_percent" type="number" min="1" max="100" step="0.5" required value="{{ old('large_discount_percent', $s('large_discount_percent', 15)) }}" class="{{ $inp }}"></label>
        <p class="text-xs text-stone-500 self-end">Each person's discount limit is set on the Staff page. Who may sell on credit or process returns is set under Roles and permissions.</p>
    </section>
    @if ($can)<button class="rounded-xl bg-stone-900 text-white px-6 py-3 text-sm">Save settings</button>@endif
</form>
@endsection
