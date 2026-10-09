@extends('layouts.boutique')
@section('title', 'Dashboard')
@section('content')
@php
    $me = auth()->user();
    $d = \App\Services\DashboardService::data(request(), $me);
    $can = $d['can'];
    $M = fn ($n) => \App\Support\Money::fmt($n);
    $sym = \App\Models\Setting::get('currency_symbol', '₵');
    $inp = 'rounded-lg border-stone-300 text-sm';
    $card = 'bg-white border border-stone-200 rounded-xl p-4 block';
    $profitBars = $can['profit'] && isset($d['period']) ? [$d['period']['net_sales'], $d['period']['cogs'], $d['period']['gross_profit'], $d['period']['expenses'], $d['period']['net_profit']] : [];
    $bk = $me->can_do('backups.view') ? \App\Services\BackupService::health() : null;
@endphp

{{-- Quick actions --}}
<div class="mb-5">
    <h2 class="text-sm font-semibold text-stone-500 uppercase tracking-wide mb-2">Quick actions</h2>
    <div class="flex flex-wrap gap-2 text-sm">
        @foreach ([
            ['New Sale', 'pos.index', 'pos.view'], ['Add Product', 'products.create', 'products.create'], ['Add Customer', 'customers.index', 'customers.create'],
            ['Add Stock', 'inventory.index', 'stock.adjust'], ['Add Expense', 'expenses.index', 'expenses.create'], ['Add Supplier', 'suppliers.index', 'suppliers.create'],
            ['New Purchase', 'purchases.create', 'purchases.create'], ['View Reports', 'reports.index', 'reports.view']] as [$l, $r, $p])
            @if ($me->can_do($p))<a href="{{ route($r) }}" class="rounded-lg bg-stone-900 text-white px-3.5 py-2.5">{{ $l }}</a>@endif
        @endforeach
        @if ($me->can_do('backups.create'))<a href="{{ route('backups.index') }}" class="rounded-lg bg-amber-600 text-white px-3.5 py-2.5">Backup Now</a>@endif
    </div>
</div>

{{-- Things that need attention --}}
@if ($can['inventory'] && ($d['out'] > 0 || $d['low'] > 0) || $d['overdue'] > 0)
<div class="mb-5 rounded-xl bg-amber-50 border border-amber-200 px-4 py-3 text-sm text-amber-900 space-y-1">
    <div class="font-semibold">Needs attention</div>
    @if ($can['inventory'] && $d['out'] > 0)<div><a class="underline" href="{{ route('inventory.index', ['status' => 'out']) }}">{{ $d['out'] }} item(s) out of stock</a></div>@endif
    @if ($can['inventory'] && $d['low'] > 0)<div><a class="underline" href="{{ route('inventory.index', ['status' => 'low']) }}">{{ $d['low'] }} item(s) running low</a></div>@endif
    @if ($d['overdue'] > 0 && $can['sales'])<div><a class="underline" href="{{ route('sales.index', ['status' => 'partial']) }}">{{ $d['overdue'] }} credit sale(s) overdue</a></div>@endif
</div>
@endif
@if ($bk)
@if ($bk['warn'])<div class="mb-5 rounded-xl border px-4 py-3 text-sm {{ $bk['level'] === 'bad' ? 'bg-red-50 border-red-300 text-red-900' : 'bg-amber-50 border-amber-300 text-amber-900' }}"><strong>Backup warning:</strong> {{ $bk['warn'] }} <a class="underline" href="{{ route('backups.index') }}">Open Backups</a></div>
@else<div class="mb-5 rounded-xl bg-emerald-50 border border-emerald-200 px-4 py-3 text-sm text-emerald-900">Last successful backup: {{ $bk['last']->completed_at->format('d M Y, H:i') }} ({{ $bk['last']->completed_at->diffForHumans() }}).</div>@endif
@endif

{{-- Figures --}}
<div class="grid grid-cols-2 lg:grid-cols-4 gap-3 mb-6">
    @if ($can['sales'])
    <div class="{{ $card }}"><div class="text-xs text-stone-500">Today's sales</div><div class="text-xl font-semibold">{{ $M($d['today']['net_sales']) }}</div><div class="text-xs text-stone-500">{{ $d['today']['count'] }} sale(s)</div></div>
    @if ($can['profit'])<div class="{{ $card }}"><div class="text-xs text-stone-500">Today's profit</div><div class="text-xl font-semibold">{{ $M($d['today']['gross_profit']) }}</div><div class="text-xs text-stone-500">gross</div></div>@endif
    @if ($can['reports'])<div class="{{ $card }}"><div class="text-xs text-stone-500">Sales this month</div><div class="text-xl font-semibold">{{ $M($d['month']['net_sales']) }}</div><div class="text-xs text-stone-500">{{ $d['month']['count'] }} sale(s)</div></div>@endif
    @if ($can['profit'])<div class="{{ $card }}"><div class="text-xs text-stone-500">Profit this month</div><div class="text-xl font-semibold">{{ $M($d['month']['net_profit']) }}</div><div class="text-xs text-stone-500">net &middot; gross {{ $M($d['month']['gross_profit']) }}</div></div>@endif
    @endif
    @if ($can['inventory'])
    <a href="{{ route('products.index') }}" class="{{ $card }}"><div class="text-xs text-stone-500">Active products</div><div class="text-xl font-semibold">{{ $d['products'] }}</div></a>
    @if ($can['costs'])<div class="{{ $card }}"><div class="text-xs text-stone-500">Stock value (cost)</div><div class="text-xl font-semibold">{{ $M($d['stock_value']) }}</div></div>@endif
    <a href="{{ route('inventory.index', ['status' => 'low']) }}" class="{{ $card }} border-amber-200"><div class="text-xs text-stone-500">Low stock</div><div class="text-xl font-semibold text-amber-700">{{ $d['low'] }}</div></a>
    <a href="{{ route('inventory.index', ['status' => 'out']) }}" class="{{ $card }} border-red-200"><div class="text-xs text-stone-500">Out of stock</div><div class="text-xl font-semibold text-red-700">{{ $d['out'] }}</div></a>
    @endif
    @if ($can['customers'])
    <a href="{{ route('customers.index') }}" class="{{ $card }}"><div class="text-xs text-stone-500">Customers</div><div class="text-xl font-semibold">{{ $d['customers'] }}</div></a>
    @endif
    @if ($can['sales'])
    <div class="{{ $card }} border-red-200"><div class="text-xs text-stone-500">Customers owe</div><div class="text-xl font-semibold text-red-700">{{ $M($d['credit']) }}</div>@if ($d['overdue'] > 0)<div class="text-xs text-red-700">{{ $d['overdue'] }} overdue</div>@endif</div>
    @endif
    @if ($can['expenses'])
    <a href="{{ route('expenses.index') }}" class="{{ $card }}"><div class="text-xs text-stone-500">Expenses today / this month</div><div class="text-lg font-semibold">{{ $M($d['today']['expenses']) }} / {{ $M($d['month']['expenses']) }}</div></a>
    @endif
</div>

@if ($can['inventory'] && $d['lowList']->isNotEmpty())
<div class="bg-white border border-stone-200 rounded-xl p-4 mb-6">
    <h2 class="font-semibold mb-2">Low stock products</h2>
    <div class="divide-y divide-stone-100 text-sm">
        @foreach ($d['lowList'] as $v)
            <a href="{{ route('inventory.index', ['q' => $v->sku]) }}" class="flex justify-between py-2 hover:bg-stone-50">
                <span>{{ $v->product->name }} <span class="text-stone-500">({{ $v->options_label }})</span></span>
                <span class="{{ $v->stock_qty <= 0 ? 'text-red-700' : 'text-amber-700' }} font-medium">{{ $v->stock_qty <= 0 ? 'Out of stock' : $v->stock_qty . ' left' }}</span>
            </a>
        @endforeach
    </div>
</div>
@endif

{{-- Charts --}}
@if ($can['reports'])
@php $R = $d['range']; @endphp
<form method="GET" x-data="{ r: '{{ $R['key'] }}' }" class="mb-3 flex flex-wrap items-center gap-2">
    <h2 class="font-semibold mr-2">Charts: {{ $R['label'] }}</h2>
    <select name="range" x-model="r" class="{{ $inp }}" @change="if (r !== 'custom') $el.form.submit()">
        @foreach (['today' => 'Today', 'week' => 'This week', 'month' => 'This month', 'last_month' => 'Last month', 'year' => 'This year', 'custom' => 'Custom range'] as $k => $l)<option value="{{ $k }}">{{ $l }}</option>@endforeach
    </select>
    <template x-if="r==='custom'"><span class="flex gap-2 items-center">
        <input type="date" name="from" value="{{ $R['from']->format('Y-m-d') }}" class="{{ $inp }}"><input type="date" name="to" value="{{ $R['to']->format('Y-m-d') }}" class="{{ $inp }}">
        <button class="rounded-lg bg-stone-900 text-white px-3 py-2 text-sm">Apply</button></span></template>
</form>
<p id="chart-note" class="hidden mb-3 rounded-lg bg-stone-100 px-3 py-2 text-sm text-stone-600">The charts could not load (they need an internet connection). The figures above and the Reports pages still work.</p>
<div class="grid lg:grid-cols-2 gap-4 mb-6">
    <div class="bg-white border border-stone-200 rounded-xl p-4"><h3 class="font-medium mb-2 text-sm">Sales overview (before returns)</h3><div class="h-64"><canvas id="c-sales"></canvas></div></div>
    <div class="bg-white border border-stone-200 rounded-xl p-4"><h3 class="font-medium mb-2 text-sm">Sales by category</h3><div class="h-64"><canvas id="c-cat"></canvas></div></div>
    <div class="bg-white border border-stone-200 rounded-xl p-4"><h3 class="font-medium mb-2 text-sm">Best-selling products (units)</h3><div class="h-64"><canvas id="c-best"></canvas></div></div>
    @if ($can['profit'])<div class="bg-white border border-stone-200 rounded-xl p-4"><h3 class="font-medium mb-2 text-sm">Profit overview</h3><div class="h-64"><canvas id="c-profit"></canvas></div></div>@endif
</div>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.7/dist/chart.umd.min.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function () {
    if (typeof Chart === 'undefined') { document.getElementById('chart-note').classList.remove('hidden'); return; }
    const SYM = @json($sym);
    const fmt = v => SYM + ' ' + Number(v).toLocaleString(undefined, { minimumFractionDigits: 2, maximumFractionDigits: 2 });
    const base = { responsive: true, maintainAspectRatio: false };
    const money = { ...base, plugins: { tooltip: { callbacks: { label: c => c.dataset.label + ': ' + fmt(c.parsed.y ?? c.parsed.x ?? c.parsed) } } } };
    const palette = ['#b45309', '#0f766e', '#1d4ed8', '#be123c', '#7c3aed', '#4d7c0f', '#c2410c', '#0369a1', '#a21caf', '#475569', '#ca8a04', '#15803d'];
    new Chart(document.getElementById('c-sales'), { type: 'bar',
        data: { labels: @json($d['series']['labels']), datasets: [
            { label: 'Sales', data: @json($d['series']['sales']), backgroundColor: '#b45309' }
            @if ($can['profit']), { label: 'Gross profit', type: 'line', data: @json($d['series']['profit']), borderColor: '#0f766e', backgroundColor: '#0f766e', tension: .25 } @endif
        ] }, options: money });
    new Chart(document.getElementById('c-cat'), { type: 'doughnut',
        data: { labels: @json($d['categories']->pluck('n')), datasets: [{ data: @json($d['categories']->pluck('t')->map(fn ($x) => (float) $x)), backgroundColor: palette }] },
        options: { ...base, plugins: { tooltip: { callbacks: { label: c => c.label + ': ' + fmt(c.parsed) } } } } });
    new Chart(document.getElementById('c-best'), { type: 'bar',
        data: { labels: @json($d['best']->pluck('n')), datasets: [{ label: 'Units sold', data: @json($d['best']->pluck('q')->map(fn ($x) => (int) $x)), backgroundColor: '#1d4ed8' }] },
        options: { ...base, indexAxis: 'y' } });
    @if ($can['profit'])
    new Chart(document.getElementById('c-profit'), { type: 'bar',
        data: { labels: ['Sales', 'Cost of goods', 'Gross profit', 'Expenses', 'Net profit'], datasets: [{ label: 'Amount', data: @json($profitBars),
            backgroundColor: ['#b45309', '#64748b', '#0f766e', '#be123c', '#15803d'] }] }, options: { ...money, plugins: { legend: { display: false }, tooltip: money.plugins.tooltip } } });
    @endif
});
</script>
@endif
@endsection
