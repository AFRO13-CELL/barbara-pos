<?php
namespace App\Services;
use App\Models\{Notification, Sale};
use App\Support\Money;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

class ReportService
{
    /** @return array{0:Carbon,1:Carbon,2:string,3:string} from, to, label, key */
    public static function range(?string $key, ?string $from = null, ?string $to = null): array
    {
        $now = now();
        switch ($key) {
            case 'today': $f = $now->copy()->startOfDay(); $t = $now->copy()->endOfDay(); $l = 'Today'; break;
            case 'week': $f = $now->copy()->startOfWeek(); $t = $now->copy()->endOfDay(); $l = 'This week'; break;
            case 'last_month': $f = $now->copy()->subMonthNoOverflow()->startOfMonth(); $t = $f->copy()->endOfMonth(); $l = 'Last month'; break;
            case 'year': $f = $now->copy()->startOfYear(); $t = $now->copy()->endOfDay(); $l = 'This year'; break;
            case 'custom':
                try { $f = Carbon::parse($from)->startOfDay(); $t = Carbon::parse($to ?: $from)->endOfDay(); }
                catch (\Throwable $e) { $f = $now->copy()->subDays(29)->startOfDay(); $t = $now->copy()->endOfDay(); }
                if ($t < $f) { [$f, $t] = [$t->copy()->startOfDay(), $f->copy()->endOfDay()]; }
                $l = $f->format('d M Y') . ' - ' . $t->format('d M Y'); break;
            default: $key = 'month'; $f = $now->copy()->startOfMonth(); $t = $now->copy()->endOfDay(); $l = 'This month';
        }
        return [$f, $t, $l, $key];
    }

    /** Sales, cost, profit and expenses for a period. Returns are taken off, and profit uses the cost saved at the time of each sale. */
    public static function totals(Carbon $from, Carbon $to): array
    {
        $s = DB::table('sales')->whereBetween('created_at', [$from, $to])
            ->selectRaw('COUNT(*) c, COALESCE(SUM(total),0) total, COALESCE(SUM(discount_amount),0) discount, COALESCE(SUM(gross_profit),0) profit')->first();
        $returned = (float) DB::table('returns')->whereBetween('created_at', [$from, $to])->sum('value');
        $reversal = (float) DB::table('return_items as ri')->join('returns as r', 'r.id', '=', 'ri.return_id')
            ->whereBetween('r.created_at', [$from, $to])
            ->selectRaw("COALESCE(SUM(CASE WHEN ri.item_condition = 'sellable' THEN ri.amount - ri.cost_price * ri.quantity ELSE ri.amount END),0) v")->value('v');
        $expenses = (float) DB::table('expenses')->whereNull('deleted_at')
            ->whereBetween('expense_date', [$from->toDateString(), $to->toDateString()])->sum('amount');

        $gross = round((float) $s->profit - $reversal, 2);
        $netSales = round((float) $s->total - $returned, 2);
        return [
            'count' => (int) $s->c, 'gross_sales' => round((float) $s->total, 2), 'discounts' => round((float) $s->discount, 2),
            'returns' => round($returned, 2), 'net_sales' => $netSales, 'cogs' => round($netSales - $gross, 2),
            'gross_profit' => $gross, 'expenses' => round($expenses, 2), 'net_profit' => round($gross - $expenses, 2),
        ];
    }

    /** Chart series: hourly for today, monthly for long periods, otherwise daily. */
    public static function series(Carbon $from, Carbon $to, string $key): array
    {
        $mode = $key === 'today' ? 'hour' : (($key === 'year' || $from->diffInDays($to, true) > 92) ? 'month' : 'day');
        $expr = ['hour' => 'HOUR(created_at)', 'day' => 'DATE(created_at)', 'month' => "DATE_FORMAT(created_at, '%Y-%m')"][$mode];
        $rows = DB::table('sales')->whereBetween('created_at', [$from, $to])
            ->selectRaw("$expr as b, SUM(total) t, SUM(gross_profit) p")->groupBy('b')->get()->keyBy('b');
        $labels = []; $sales = []; $profit = [];
        $push = function ($bucket, $label) use ($rows, &$labels, &$sales, &$profit) {
            $labels[] = $label; $sales[] = round((float) ($rows[$bucket]->t ?? 0), 2); $profit[] = round((float) ($rows[$bucket]->p ?? 0), 2);
        };
        if ($mode === 'hour') { for ($h = 0; $h < 24; $h++) $push($h, sprintf('%02d:00', $h)); }
        elseif ($mode === 'day') { for ($d = $from->copy()->startOfDay(); $d <= $to; $d->addDay()) $push($d->format('Y-m-d'), $d->format('d M')); }
        else { for ($d = $from->copy()->startOfMonth(); $d <= $to; $d->addMonthNoOverflow()) $push($d->format('Y-m'), $d->format('M Y')); }
        return compact('labels', 'sales', 'profit');
    }

    public static function byCategory(Carbon $from, Carbon $to)
    {
        return DB::table('sale_items as si')->join('sales as s', 's.id', '=', 'si.sale_id')
            ->join('product_variations as pv', 'pv.id', '=', 'si.product_variation_id')
            ->join('products as p', 'p.id', '=', 'pv.product_id')->join('categories as c', 'c.id', '=', 'p.category_id')
            ->whereBetween('s.created_at', [$from, $to])->groupBy('c.name')
            ->selectRaw('c.name as n, SUM(si.line_total) as t')->orderByDesc('t')->limit(12)->get();
    }

    public static function bestSellers(Carbon $from, Carbon $to, int $limit = 8)
    {
        return DB::table('sale_items as si')->join('sales as s', 's.id', '=', 'si.sale_id')
            ->whereBetween('s.created_at', [$from, $to])->groupBy('si.product_name')
            ->selectRaw('si.product_name as n, SUM(si.quantity) as q, SUM(si.line_total) as t')->orderByDesc('q')->limit($limit)->get();
    }

    public static function activeVariations()
    {
        return DB::table('product_variations as pv')->join('products as p', 'p.id', '=', 'pv.product_id')
            ->whereNull('pv.deleted_at')->whereNull('p.deleted_at');
    }

    /** Once a day: raise a notification for each overdue credit sale. */
    public static function checkOverdue(): void
    {
        Cache::remember('overdue_checked_' . now()->format('Ymd'), 86400, function () {
            foreach (Sale::where('balance', '>', 0)->whereDate('due_date', '<', today())->with('customer')->get() as $s) {
                $url = route('sales.show', $s);
                if (Notification::where('type', 'credit_overdue')->where('url', $url)->exists()) continue;
                Notification::create(['type' => 'credit_overdue', 'title' => 'Credit overdue',
                    'message' => "{$s->receipt_number} ({$s->customer?->name}) owes " . Money::fmt($s->balance) . ', due ' . $s->due_date->format('d M Y'), 'url' => $url]);
            }
            return true;
        });
    }
}
