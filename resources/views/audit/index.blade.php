@extends('layouts.boutique')
@section('title', 'Audit log')
@section('content')
@php $inp = 'rounded-lg border-stone-300 text-sm'; @endphp
<form method="GET" class="mb-4 flex flex-wrap gap-2" x-data>
    <input type="search" name="q" value="{{ request('q') }}" autofocus onfocus="this.setSelectionRange(this.value.length, this.value.length)"
           @input.debounce.500ms="$el.form.submit()" placeholder="Search action, description or person..." class="{{ $inp }} flex-1 min-w-[220px]">
    <input type="date" name="from" value="{{ request('from') }}" class="{{ $inp }}" @change="$el.form.submit()">
    <input type="date" name="to" value="{{ request('to') }}" class="{{ $inp }}" @change="$el.form.submit()">
</form>
<div class="bg-white border border-stone-200 rounded-xl overflow-x-auto">
<table class="min-w-full text-sm">
    <thead class="bg-stone-100 text-left text-stone-600"><tr><th class="px-4 py-3">When</th><th class="px-4 py-3">Who</th><th class="px-4 py-3">What happened</th></tr></thead>
    <tbody class="divide-y divide-stone-100">
    @forelse ($logs as $l)
        <tr>
            <td class="px-4 py-2.5 whitespace-nowrap align-top">{{ $l->created_at->format('d M Y, H:i:s') }}</td>
            <td class="px-4 py-2.5 align-top">{{ $l->user?->name ?? 'System' }}</td>
            <td class="px-4 py-2.5">
                <span class="text-xs rounded bg-stone-100 px-1.5 py-0.5">{{ $l->action }}</span> {{ $l->description }}
                @if ($l->old_values || $l->new_values)
                <details class="mt-1"><summary class="cursor-pointer text-xs underline">Details</summary>
                    <div class="mt-1 grid sm:grid-cols-2 gap-2 text-xs">
                        <pre class="bg-stone-50 rounded p-2 overflow-x-auto">Before: {{ json_encode($l->old_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre>
                        <pre class="bg-stone-50 rounded p-2 overflow-x-auto">After: {{ json_encode($l->new_values, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) }}</pre></div></details>
                @endif
            </td>
        </tr>
    @empty
        <tr><td colspan="3" class="px-4 py-10 text-center text-stone-500">Nothing logged yet.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
<p class="mt-2 text-xs text-stone-500">The audit log is read-only. Nobody can edit or delete entries from the system.</p>
<div class="mt-4">{{ $logs->links() }}</div>
@endsection
