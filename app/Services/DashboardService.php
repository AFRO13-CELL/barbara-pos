<?php
namespace App\Services;
use App\Models\{Customer, Product, ProductVariation, Sale, User};
use Illuminate\Http\Request;

class DashboardService
{
    public static function data(Request $request, User $u): array
    {
        ReportService::checkOverdue();
        $now = now();
        $today = ReportService::totals($now->copy()->startOfDay(), $now->copy()->endOfDay());
        $month = ReportService::totals($now->copy()->startOfMonth(), $now->copy()->endOfDay());
        [$from, $to, $label, $key] = ReportService::range($request->get('range', 'month'), $request->get('from'), $request->get('to'));

        $d = [
            'can' => [
                'sales' => $u->can_do('sales.view'), 'profit' => $u->can_do('profit.view'), 'costs' => $u->can_do('costs.view'),
                'inventory' => $u->can_do('inventory.view'), 'customers' => $u->can_do('customers.view'),
                'expenses' => $u->can_do('expenses.view'), 'reports' => $u->can_do('reports.view'),
            ],
            'today' => $today, 'month' => $month, 'range' => ['from' => $from, 'to' => $to, 'label' => $label, 'key' => $key],
            'products' => Product::count(),
            'low' => ReportService::activeVariations()->where('pv.stock_qty', '>', 0)->whereColumn('pv.stock_qty', '<=', 'pv.min_stock')->count(),
            'out' => ReportService::activeVariations()->where('pv.stock_qty', '<=', 0)->count(),
            'stock_value' => $u->can_do('costs.view')
                ? (float) ReportService::activeVariations()->selectRaw('COALESCE(SUM(pv.stock_qty * pv.cost_price),0) v')->value('v') : null,
            'customers' => Customer::count(),
            'credit' => (float) Sale::where('balance', '>', 0)->sum('balance'),
            'overdue' => Sale::where('balance', '>', 0)->whereDate('due_date', '<', today())->count(),
            'lowList' => ProductVariation::with('product')->whereHas('product')->whereColumn('stock_qty', '<=', 'min_stock')
                ->orderBy('stock_qty')->limit(8)->get(),
        ];
        if ($d['can']['reports']) {
            $d['series'] = ReportService::series($from, $to, $key);
            $d['categories'] = ReportService::byCategory($from, $to);
            $d['best'] = ReportService::bestSellers($from, $to);
            $d['period'] = ReportService::totals($from, $to);
        }
        return $d;
    }
}
