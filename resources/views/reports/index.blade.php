@extends('layouts.boutique')
@section('title', $data['title'])
@section('content')
@php
    $inp = 'rounded-lg border-stone-300 text-sm';
    $fmt = function ($v, $t) { return match ($t) { 'money' => \App\Support\Money::fmt($v), 'int' => number_format((float) $v), default => $v }; };
    $csvUrl = request()->fullUrlWithQuery(['export' => 'csv']);
@endphp
<style>@media print { aside, header, .no-print { display: none !important; } main { padding: 0 !important; max-width: none !important; } body { background: #fff !important; } }</style>

<div class="no-print mb-4 flex flex-wrap gap-1.5 text-sm">
    @foreach ($types as $k => $l)
        <a href="{{ route('reports.index', array_merge(request()->only(['range', 'from', 'to']), ['type' => $k])) }}"
           class="rounded-full px-4 py-2 {{ $type === $k ? 'bg-stone-900 text-white' : 'bg-white border border-stone-300' }}">{{ $l }}</a>
    @endforeach
</div>

<form method="GET" class="no-print mb-4 bg-white border border-stone-200 rounded-xl p-3 flex flex-wrap items-end gap-2" x-data="{ r: '{{ $key }}' }">
    <input type="hidden" name="type" value="{{ $type }}">
    @if ($data['uses_dates'])
    <label class="text-xs">Period
        <select name="range" x-model="r" class="{{ $inp }} block">
            @foreach (['today' => 'Today', 'week' => 'This week', 'month' => 'This month', 'last_month' => 'Last month', 'year' => 'This year', 'custom' => 'Custom dates'] as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach
        </select></label>
    <template x-if="r==='custom'"><span class="flex gap-2 items-end">
        <label class="text-xs">From<input type="date" name="from" value="{{ $from->format('Y-m-d') }}" class="{{ $inp }} block"></label>
        <label class="text-xs">To<input type="date" name="to" value="{{ $to->format('Y-m-d') }}" class="{{ $inp }} block"></label></span></template>
    @endif
    @if ($type !== 'profit')
    <label class="text-xs flex-1 min-w-[160px]">Search<input type="search" name="q" value="{{ request('q') }}" placeholder="Search this report..." class="{{ $inp }} block w-full"></label>
    @endif
    @foreach ($data['filters'] as $f)
        <label class="text-xs">{{ $f['label'] }}
            <select name="{{ $f['name'] }}" class="{{ $inp }} block">@foreach ($f['options'] as $v => $l)<option value="{{ $v }}" @selected((string) request($f['name'], array_key_first($f['options'])) === (string) $v)>{{ $l }}</option>@endforeach</select></label>
    @endforeach
    <button class="rounded-lg bg-stone-900 text-white px-4 py-2 text-sm">Show report</button>
</form>

<div class="flex flex-wrap items-center justify-between gap-2 mb-3">
    <div><div class="font-semibold">{{ $data['title'] }}</div>@if ($data['uses_dates'])<div class="text-xs text-stone-500">{{ $label }}</div>@endif</div>
    <div class="no-print flex gap-2 text-sm">
        <button onclick="window.print()" class="rounded-lg border border-stone-300 bg-white px-3 py-2">Print / Save as PDF</button>
        <a href="{{ $csvUrl }}" class="rounded-lg border border-stone-300 bg-white px-3 py-2">Export CSV (opens in Excel)</a>
    </div>
</div>

<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-4">
    @foreach ($data['summary'] as $l => [$v, $t])
        <div class="bg-white border border-stone-200 rounded-xl p-3"><div class="text-xs text-stone-500">{{ $l }}</div><div class="text-lg font-semibold">{{ $fmt($v, $t) }}</div></div>
    @endforeach
</div>

<div class="bg-white border border-stone-200 rounded-xl overflow-x-auto">
<table class="min-w-full text-sm">
    <thead class="bg-stone-100 text-left text-stone-600"><tr>
        @foreach ($data['columns'] as $c)<th class="px-4 py-3 {{ in_array($c['type'], ['money', 'int']) ? 'text-right' : '' }}">{{ $c['label'] }}</th>@endforeach</tr></thead>
    <tbody class="divide-y divide-stone-100">
    @forelse ($data['rows'] as $row)
        <tr class="{{ $type === 'profit' && in_array($row['line'], ['GROSS PROFIT', 'NET PROFIT']) ? 'font-semibold bg-stone-50' : '' }}">
            @foreach ($data['columns'] as $c)
                <td class="px-4 py-2.5 {{ in_array($c['type'], ['money', 'int']) ? 'text-right whitespace-nowrap' : '' }}">{{ $fmt($row[$c['key']] ?? '', $c['type']) }}</td>
            @endforeach
        </tr>
    @empty
        <tr><td colspan="{{ count($data['columns']) }}" class="px-4 py-10 text-center text-stone-500">Nothing to show for these filters.</td></tr>
    @endforelse
    </tbody>
</table>
</div>
@if (count($data['rows']) >= 1000)<p class="mt-2 text-xs text-stone-500">Showing the first 1,000 rows. Export to CSV to get everything.</p>@endif
@if ($data['note'])<p class="mt-2 text-xs text-stone-500">{{ $data['note'] }}</p>@endif
@endsection
