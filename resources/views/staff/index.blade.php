@extends('layouts.boutique')
@section('title', 'Staff')
@section('content')
@php $me = auth()->user(); $inp = 'rounded-lg border-stone-300 text-sm w-full'; @endphp
<form method="GET" class="mb-4" x-data>
    <input type="search" name="q" value="{{ request('q') }}" autofocus onfocus="this.setSelectionRange(this.value.length, this.value.length)"
           @input.debounce.500ms="$el.form.submit()" placeholder="Search name, email or role..." class="{{ $inp }}">
</form>
@if ($me->can_do('staff.create'))
<details class="mb-4 bg-white border border-stone-200 rounded-xl p-4" @if ($errors->any()) open @endif>
    <summary class="cursor-pointer text-sm font-medium">+ Add staff member</summary>
    <form method="POST" action="{{ route('staff.store') }}" autocomplete="off" class="mt-3 grid sm:grid-cols-2 gap-2">
        @csrf
        <label class="text-xs">Name<input name="name" required value="{{ old('name') }}" class="{{ $inp }}"></label>
        <label class="text-xs">Email (their login)<input name="email" type="email" required value="{{ old('email') }}" class="{{ $inp }}"></label>
        <label class="text-xs">Phone<input name="phone" value="{{ old('phone') }}" class="{{ $inp }}"></label>
        <label class="text-xs">Role
            <select name="role_id" required class="{{ $inp }}">@foreach ($roles as $r)<option value="{{ $r->id }}" @selected(old('role_id') == $r->id)>{{ $r->label }}</option>@endforeach</select></label>
        <label class="text-xs">Password (at least 10 characters)<input name="password" type="password" required minlength="10" autocomplete="new-password" class="{{ $inp }}"></label>
        <label class="text-xs">Most discount they may give (%)<input name="max_discount_percent" type="number" min="0" max="100" step="0.5" value="{{ old('max_discount_percent', 0) }}" class="{{ $inp }}"></label>
        <button class="rounded-lg bg-stone-900 text-white px-4 py-2 text-sm w-fit">Create account</button>
    </form>
</details>
@endif
<div class="space-y-2">
@foreach ($staff as $u)
    <div class="bg-white border border-stone-200 rounded-xl p-3 {{ $u->is_active ? '' : 'opacity-60' }}">
        <div class="flex flex-wrap items-center gap-x-4 gap-y-1">
            <div class="flex-1 min-w-[200px]"><div class="font-medium">{{ $u->name }} @if ($u->id === $me->id)<span class="text-xs text-stone-500">(you)</span>@endif</div>
                <div class="text-xs text-stone-500">{{ $u->email }} {{ $u->phone ? '· ' . $u->phone : '' }}</div></div>
            <span class="rounded-full bg-stone-100 px-2.5 py-1 text-xs">{{ $u->role?->label ?? 'No role' }}</span>
            <span class="text-xs text-stone-500">Discount up to {{ $u->role?->name === 'owner' ? 'unlimited' : rtrim(rtrim($u->max_discount_percent, '0'), '.') . '%' }}</span>
            <span class="rounded-full px-2.5 py-1 text-xs {{ $u->is_active ? 'bg-emerald-100 text-emerald-700' : 'bg-red-100 text-red-700' }}">{{ $u->is_active ? 'Active' : 'Switched off' }}</span>
        </div>
        @if ($me->can_do('staff.edit'))
        <details class="mt-2">
            <summary class="cursor-pointer text-xs underline">Edit</summary>
            <form method="POST" action="{{ route('staff.update', $u) }}" autocomplete="off" class="mt-2 grid sm:grid-cols-2 gap-2">
                @csrf @method('PUT')
                <label class="text-xs">Name<input name="name" required value="{{ $u->name }}" class="{{ $inp }}"></label>
                <label class="text-xs">Email<input name="email" type="email" required value="{{ $u->email }}" class="{{ $inp }}"></label>
                <label class="text-xs">Phone<input name="phone" value="{{ $u->phone }}" class="{{ $inp }}"></label>
                <label class="text-xs">Role<select name="role_id" class="{{ $inp }}">@foreach ($roles as $r)<option value="{{ $r->id }}" @selected($u->role_id == $r->id)>{{ $r->label }}</option>@endforeach</select></label>
                <label class="text-xs">Discount limit (%)<input name="max_discount_percent" type="number" min="0" max="100" step="0.5" value="{{ $u->max_discount_percent }}" class="{{ $inp }}"></label>
                <label class="text-xs">New password (leave empty to keep)<input name="password" type="password" minlength="10" autocomplete="new-password" class="{{ $inp }}"></label>
                <label class="flex items-center gap-2 text-sm"><input type="checkbox" name="is_active" value="1" @checked($u->is_active)> Account is active</label>
                <button class="rounded-lg border border-stone-300 px-3 py-2 text-sm w-fit">Save changes</button>
            </form>
            @if ($me->can_do('staff.delete') && $u->id !== $me->id)
            <form method="POST" action="{{ route('staff.destroy', $u) }}" class="mt-2" onsubmit="return confirm('Remove {{ addslashes($u->name) }}? They will no longer be able to log in. Their past sales are kept.')">
                @csrf @method('DELETE')<button class="text-xs text-red-600 hover:underline">Remove staff member</button></form>
            @endif
        </details>
        @endif
    </div>
@endforeach
</div>
<div class="mt-4">{{ $staff->links() }}</div>
<p class="mt-4 text-xs text-stone-500">What each role can open is set under <a class="underline" href="{{ route('settings.roles') }}">Settings, then Roles and permissions</a>.</p>
@endsection
