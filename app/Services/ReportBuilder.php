<?php
namespace App\Services;
use App\Models\{Customer, ProductVariation, Sale, User};
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/** Each method returns: title, uses_dates, filters, columns[key,label,type], rows[], summary[label=>[value,type]], note */
class ReportBuilder
{
    public static function build(string $type, Request $r, Carbon $from, Carbon $to, User $u, bool $export): array
    {
        $cap = $export ? 20000 : 1000;
        return self::{$type}($r, $from, $to, $u, $cap);
    }

    private static function col(string $key, string $label, string $type = 'text'): array { return compact('key', 'label', 'type'); }

    private static function sales(Request $r, Carbon $from, Carbon $to, User $u, int $cap): array
    {
        $q = trim((string) $r->get('q', '')); $group = $r->get('group', 'each'); $status = $r->get('status', '');
        $profit = $u->can_do('profit.view');
        $apply = function ($qq) use ($from, $to, $q, $status) {
            $qq->whereBetween('created_at', [$from, $to]);
            if ($q !== '') {
                $like = "%{$q}%";
                $qq->where(fn ($w) => $w->where('receipt_number', 'like', $like)
                    ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', $like))
                    ->orWhereHas('user', fn ($x) => $x->where('name', 'like', $like))
                    ->orWhereHas('items', fn ($i) => $i->where('product_name', 'like', $like)));
            }
            if ($status !== '') $qq->where('payment_status', $status);
            return $qq;
        };
        $t = $apply(Sale::query())->selectRaw('COUNT(*) c, COALESCE(SUM(total),0) total, COALESCE(SUM(discount_amount),0) d, COALESCE(SUM(amount_paid),0) paid, COALESCE(SUM(balance),0) bal, COALESCE(SUM(gross_profit),0) p')->first();
        $summary = ['Sales' => [(int) $t->c, 'int'], 'Total sales' => [(float) $t->total, 'money'], 'Discounts given' => [(float) $t->d, 'money'],
                    'Paid' => [(float) $t->paid, 'money'], 'Still owed' => [(float) $t->bal, 'money']];
        if ($profit) $summary['Gross profit (before returns)'] = [(float) $t->p, 'money'];

        if ($group === 'each') {
            $cols = [self::col('receipt', 'Receipt'), self::col('date', 'Date'), self::col('customer', 'Customer'), self::col('staff', 'Staff'), self::col('items', 'Items', 'int'),
                     self::col('subtotal', 'Subtotal', 'money'), self::col('discount', 'Discount', 'money'), self::col('total', 'Total', 'money'),
                     self::col('paid', 'Paid', 'money'), self::col('balance', 'Balance', 'money'), self::col('status', 'Status')];
            if ($profit) $cols[] = self::col('profit', 'Profit', 'money');
            $rows = $apply(Sale::with(['customer', 'user'])->withCount('items'))->latest()->limit($cap)->get()->map(fn ($s) => [
                'receipt' => $s->receipt_number, 'date' => $s->created_at->format('Y-m-d H:i'), 'customer' => $s->customer?->name ?? 'Walk-in',
                'staff' => $s->user?->name, 'items' => $s->items_count, 'subtotal' => $s->subtotal, 'discount' => $s->discount_amount, 'total' => $s->total,
                'paid' => $s->amount_paid, 'balance' => $s->balance, 'status' => ucfirst($s->payment_status), 'profit' => $s->gross_profit])->all();
        } else {
            $expr = ['day' => 'DATE(created_at)', 'week' => 'YEARWEEK(created_at, 3)', 'month' => "DATE_FORMAT(created_at, '%Y-%m')", 'year' => 'YEAR(created_at)'][$group] ?? 'DATE(created_at)';
            $cols = [self::col('period', 'Period'), self::col('count', 'Sales', 'int'), self::col('discount', 'Discounts', 'money'), self::col('total', 'Total sales', 'money')];
            if ($profit) $cols[] = self::col('profit', 'Gross profit', 'money');
            $rows = $apply(Sale::query())->selectRaw("$expr as period, COUNT(*) c, SUM(total) t, SUM(discount_amount) d, SUM(gross_profit) p")
                ->groupBy('period')->orderBy('period')->limit($cap)->get()->map(function ($x) use ($group) {
                    $label = $x->period;
                    if ($group === 'week') $label = 'Week ' . ((int) $x->period % 100) . ', ' . intdiv((int) $x->period, 100);
                    return ['period' => $label, 'count' => $x->c, 'discount' => $x->d, 'total' => $x->t, 'profit' => $x->p];
                })->all();
        }
        return ['title' => 'Sales report', 'uses_dates' => true, 'columns' => $cols, 'rows' => $rows, 'summary' => $summary, 'note' => 'Sales before returns. See the Profit report for figures after returns.',
            'filters' => [
                ['name' => 'group', 'label' => 'Group by', 'options' => ['each' => 'Each sale', 'day' => 'Day', 'week' => 'Week', 'month' => 'Month', 'year' => 'Year']],
                ['name' => 'status', 'label' => 'Payment status', 'options' => ['' => 'Any', 'paid' => 'Paid', 'partial' => 'Part paid', 'credit' => 'Credit']]]];
    }

    private static function profit(Request $r, Carbon $from, Carbon $to, User $u, int $cap): array
    {
        $t = ReportService::totals($from, $to);
        $rows = [
            ['line' => 'Sales (before returns)', 'amount' => $t['gross_sales']], ['line' => 'Less: returns', 'amount' => -$t['returns']],
            ['line' => 'Net sales', 'amount' => $t['net_sales']], ['line' => 'Cost of goods sold', 'amount' => -$t['cogs']],
            ['line' => 'GROSS PROFIT', 'amount' => $t['gross_profit']], ['line' => 'Less: expenses', 'amount' => -$t['expenses']],
            ['line' => 'NET PROFIT', 'amount' => $t['net_profit']],
        ];
        $exp = DB::table('expenses')->whereNull('deleted_at')->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])
            ->groupBy('category')->selectRaw('category, SUM(amount) a')->orderByDesc('a')->get();
        foreach ($exp as $e) $rows[] = ['line' => '   Expense: ' . $e->category, 'amount' => (float) $e->a];
        return ['title' => 'Profit report', 'uses_dates' => true, 'filters' => [],
            'columns' => [self::col('line', 'Line'), self::col('amount', 'Amount', 'money')], 'rows' => $rows,
            'summary' => ['Net sales' => [$t['net_sales'], 'money'], 'Gross profit' => [$t['gross_profit'], 'money'], 'Expenses' => [$t['expenses'], 'money'], 'Net profit' => [$t['net_profit'], 'money']],
            'note' => 'Gross profit = selling price minus the cost saved at the time of each sale, after returns. Net profit = gross profit minus expenses.'];
    }

    private static function inventory(Request $r, Carbon $from, Carbon $to, User $u, int $cap): array
    {
        $q = trim((string) $r->get('q', '')); $status = $r->get('status', ''); $costs = $u->can_do('costs.view');
        $query = ProductVariation::with('product.category')->whereHas('product');
        if ($q !== '') {
            $like = "%{$q}%";
            $query->where(fn ($w) => $w->where('sku', 'like', $like)->orWhere('barcode', 'like', $like)->orWhere('size', 'like', $like)->orWhere('color', 'like', $like)
                ->orWhereHas('product', fn ($p) => $p->where('name', 'like', $like)->orWhereHas('category', fn ($c) => $c->where('name', 'like', $like))));
        }
        match ($status) {
            'out' => $query->where('stock_qty', '<=', 0),
            'low' => $query->where('stock_qty', '>', 0)->whereColumn('stock_qty', '<=', 'min_stock'),
            'in' => $query->whereColumn('stock_qty', '>', 'min_stock'), default => null };
        $all = (clone $query)->get();
        $cols = [self::col('product', 'Product'), self::col('variation', 'Variation'), self::col('sku', 'SKU'), self::col('category', 'Category'),
                 self::col('stock', 'Stock', 'int'), self::col('min', 'Minimum', 'int'), self::col('status', 'Status')];
        if ($costs) { $cols[] = self::col('cost', 'Cost price', 'money'); $cols[] = self::col('value', 'Stock value', 'money'); }
        $label = ['out' => 'Out of stock', 'low' => 'Low stock', 'in' => 'In stock'];
        $rows = $query->orderBy('stock_qty')->limit($cap)->get()->map(fn ($v) => [
            'product' => $v->product->name, 'variation' => $v->options_label, 'sku' => $v->sku, 'category' => $v->product->category?->name,
            'stock' => $v->stock_qty, 'min' => $v->min_stock, 'status' => $label[$v->status], 'cost' => $v->cost_price, 'value' => $v->stock_qty * $v->cost_price])->all();
        $summary = ['Items' => [$all->count(), 'int'], 'Units in stock' => [(int) $all->sum('stock_qty'), 'int'],
                    'Low stock' => [$all->where('status', 'low')->count(), 'int'], 'Out of stock' => [$all->where('status', 'out')->count(), 'int']];
        if ($costs) $summary['Stock value (cost)'] = [(float) $all->sum(fn ($v) => $v->stock_qty * $v->cost_price), 'money'];
        return ['title' => 'Inventory report', 'uses_dates' => false, 'columns' => $cols, 'rows' => $rows, 'summary' => $summary,
            'note' => 'Shows current stock. Every stock change is listed under Stock Movements.',
            'filters' => [['name' => 'status', 'label' => 'Status', 'options' => ['' => 'All', 'in' => 'In stock', 'low' => 'Low stock', 'out' => 'Out of stock']]]];
    }

    private static function products(Request $r, Carbon $from, Carbon $to, User $u, int $cap): array
    {
        $profit = $u->can_do('profit.view'); $q = trim((string) $r->get('q', ''));
        $mode = $r->get('mode', 'best');
        if (in_array($mode, ['profitable', 'least']) && !$profit) $mode = 'best';
        $agg = DB::table('sale_items as si')->join('sales as s', 's.id', '=', 'si.sale_id')->whereBetween('s.created_at', [$from, $to])
            ->groupBy('si.product_variation_id')->selectRaw('si.product_variation_id as vid, SUM(si.quantity) as qty, SUM(si.line_total) as revenue, SUM(si.line_profit) as profit');
        $query = ReportService::activeVariations()->leftJoinSub($agg, 'a', 'a.vid', '=', 'pv.id')
            ->selectRaw('p.name, pv.size, pv.color, pv.sku, pv.stock_qty, COALESCE(a.qty,0) qty, COALESCE(a.revenue,0) revenue, COALESCE(a.profit,0) profit');
        if ($q !== '') { $like = "%{$q}%"; $query->where(fn ($w) => $w->where('p.name', 'like', $like)->orWhere('pv.sku', 'like', $like)); }
        match ($mode) {
            'slow' => $query->where('pv.stock_qty', '>', 0)->orderBy('qty')->orderByDesc('pv.stock_qty'),
            'profitable' => $query->having('qty', '>', 0)->orderByDesc('profit'),
            'least' => $query->having('qty', '>', 0)->orderBy('profit'),
            default => $query->orderByDesc('qty')->orderByDesc('revenue') };
        $rows = $query->limit(min($cap, 500))->get()->map(fn ($x) => [
            'product' => $x->name, 'variation' => collect([$x->size, $x->color])->filter()->implode(' / ') ?: 'Standard', 'sku' => $x->sku,
            'stock' => (int) $x->stock_qty, 'qty' => (int) $x->qty, 'revenue' => (float) $x->revenue, 'profit' => (float) $x->profit])->all();
        $cols = [self::col('product', 'Product'), self::col('variation', 'Variation'), self::col('sku', 'SKU'), self::col('stock', 'In stock', 'int'),
                 self::col('qty', 'Sold', 'int'), self::col('revenue', 'Sales', 'money')];
        if ($profit) $cols[] = self::col('profit', 'Profit', 'money');
        $modes = ['best' => 'Best sellers', 'slow' => 'Slow movers (in stock, selling least)'];
        if ($profit) $modes += ['profitable' => 'Most profitable', 'least' => 'Least profitable'];
        return ['title' => 'Product report', 'uses_dates' => true, 'columns' => $cols, 'rows' => $rows, 'note' => 'Top 500 items. Sales before returns.',
            'summary' => ['Items listed' => [count($rows), 'int'], 'Units sold' => [array_sum(array_column($rows, 'qty')), 'int']],
            'filters' => [['name' => 'mode', 'label' => 'Show', 'options' => $modes]]];
    }

    private static function customers(Request $r, Carbon $from, Carbon $to, User $u, int $cap): array
    {
        $q = trim((string) $r->get('q', '')); $mode = $r->get('mode', 'top');
        $inRange = fn ($s) => $s->whereBetween('created_at', [$from, $to]);
        $query = Customer::query()->withCount(['sales as purchases' => $inRange])->withSum(['sales as spent' => $inRange], 'total')
            ->withSum('sales as owed', 'balance')->withMax(['sales as last_purchase' => $inRange], 'created_at');
        if ($q !== '') { $like = "%{$q}%"; $query->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('phone', 'like', $like)); }
        match ($mode) {
            'new' => $query->whereBetween('created_at', [$from, $to])->orderByDesc('created_at'),
            'returning' => $query->having('purchases', '>=', 2)->orderByDesc('purchases'),
            'credit' => $query->having('owed', '>', 0)->orderByDesc('owed'),
            default => $query->having('spent', '>', 0)->orderByDesc('spent') };
        $rows = $query->limit($cap)->get()->map(fn ($c) => [
            'name' => $c->name, 'phone' => $c->phone, 'purchases' => (int) $c->purchases, 'spent' => (float) $c->spent, 'owed' => (float) $c->owed,
            'credit' => (float) $c->store_credit, 'last' => $c->last_purchase ? Carbon::parse($c->last_purchase)->format('Y-m-d') : ''])->all();
        return ['title' => 'Customer report', 'uses_dates' => true, 'note' => '"Owes" is the customer\'s total unpaid balance, not limited to the dates.',
            'columns' => [self::col('name', 'Customer'), self::col('phone', 'Phone'), self::col('purchases', 'Purchases', 'int'), self::col('spent', 'Spent', 'money'),
                          self::col('owed', 'Owes', 'money'), self::col('credit', 'Store credit', 'money'), self::col('last', 'Last purchase')],
            'rows' => $rows, 'summary' => ['Customers listed' => [count($rows), 'int'], 'Spent' => [array_sum(array_column($rows, 'spent')), 'money'], 'Owed' => [array_sum(array_column($rows, 'owed')), 'money']],
            'filters' => [['name' => 'mode', 'label' => 'Show', 'options' => ['top' => 'Top customers', 'new' => 'New customers', 'returning' => 'Returning customers (2+ purchases)', 'credit' => 'Credit customers (owe money)']]]];
    }

    private static function staff(Request $r, Carbon $from, Carbon $to, User $u, int $cap): array
    {
        $profit = $u->can_do('profit.view'); $q = trim((string) $r->get('q', ''));
        $sales = DB::table('sales')->whereBetween('created_at', [$from, $to])->groupBy('user_id')
            ->selectRaw('user_id, COUNT(*) c, SUM(total) t, SUM(discount_amount) d, SUM(gross_profit) p')->get()->keyBy('user_id');
        $rets = DB::table('returns')->whereBetween('created_at', [$from, $to])->groupBy('user_id')->selectRaw('user_id, COUNT(*) c, SUM(value) v')->get()->keyBy('user_id');
        $users = User::with('role')->when($q !== '', fn ($w) => $w->where('name', 'like', "%{$q}%"))->orderBy('name')->get();
        $rows = $users->map(fn ($x) => [
            'name' => $x->name, 'role' => $x->role?->label, 'count' => (int) ($sales[$x->id]->c ?? 0), 'total' => (float) ($sales[$x->id]->t ?? 0),
            'discount' => (float) ($sales[$x->id]->d ?? 0), 'rcount' => (int) ($rets[$x->id]->c ?? 0), 'rvalue' => (float) ($rets[$x->id]->v ?? 0),
            'profit' => (float) ($sales[$x->id]->p ?? 0)])->all();
        $cols = [self::col('name', 'Staff'), self::col('role', 'Role'), self::col('count', 'Transactions', 'int'), self::col('total', 'Sales', 'money'),
                 self::col('discount', 'Discounts given', 'money'), self::col('rcount', 'Returns processed', 'int'), self::col('rvalue', 'Returns value', 'money')];
        if ($profit) $cols[] = self::col('profit', 'Gross profit', 'money');
        return ['title' => 'Staff report', 'uses_dates' => true, 'columns' => $cols, 'rows' => $rows, 'filters' => [], 'note' => '',
            'summary' => ['Transactions' => [array_sum(array_column($rows, 'count')), 'int'], 'Sales' => [array_sum(array_column($rows, 'total')), 'money'], 'Discounts' => [array_sum(array_column($rows, 'discount')), 'money']]];
    }
}
