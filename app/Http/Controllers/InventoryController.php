<?php
namespace App\Http\Controllers;
use App\Models\{AuditLog, Category, InventoryMovement, ProductVariation};
use App\Services\StockService;
use Illuminate\Http\Request;

class InventoryController extends Controller
{
    public const REASONS = ['Damaged', 'Lost', 'Theft', 'Stock count correction', 'Returned to supplier', 'Found stock', 'Other'];

    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $query = ProductVariation::query()->with('product.category')->whereHas('product');
        if ($q !== '') {
            $like = "%{$q}%";
            $query->where(function ($w) use ($like) {
                $w->where('sku', 'like', $like)->orWhere('barcode', 'like', $like)
                  ->orWhere('size', 'like', $like)->orWhere('color', 'like', $like)
                  ->orWhereHas('product', fn ($p) => $p->where('name', 'like', $like)
                        ->orWhereHas('category', fn ($c) => $c->where('name', 'like', $like)));
            });
        }
        if ($request->filled('category')) $query->whereHas('product', fn ($p) => $p->where('category_id', $request->category));
        match ($request->get('status')) {
            'out' => $query->where('stock_qty', '<=', 0),
            'low' => $query->where('stock_qty', '>', 0)->whereColumn('stock_qty', '<=', 'min_stock'),
            'in'  => $query->whereColumn('stock_qty', '>', 'min_stock'),
            default => null,
        };
        $variations = $query->orderBy('stock_qty')->paginate(25)->withQueryString();

        $base = ProductVariation::whereHas('product');
        $stats = [
            'total' => (clone $base)->count(),
            'low' => (clone $base)->where('stock_qty', '>', 0)->whereColumn('stock_qty', '<=', 'min_stock')->count(),
            'out' => (clone $base)->where('stock_qty', '<=', 0)->count(),
            'value' => (float) (clone $base)->selectRaw('COALESCE(SUM(stock_qty * cost_price),0) as v')->value('v'),
        ];
        return view('inventory.index', [
            'variations' => $variations, 'stats' => $stats,
            'categories' => Category::orderBy('name')->get(), 'reasons' => self::REASONS,
        ]);
    }

    public function adjust(Request $r, ProductVariation $variation)
    {
        $d = $r->validate([
            'qty_change' => 'required|integer|not_in:0|between:-100000,100000',
            'reason' => 'required|in:' . implode(',', self::REASONS),
            'note' => 'nullable|string|max:500',
        ]);
        $before = $variation->stock_qty;
        try {
            $m = StockService::move($variation, (int) $d['qty_change'], 'adjustment', $d['reason'], $d['note'] ?? null);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['qty_change' => $e->getMessage()]);
        }
        AuditLog::record('stock_adjusted', $variation,
            "Stock adjusted from {$before} to {$m->new_stock} ({$d['reason']})",
            ['stock_qty' => $before], ['stock_qty' => $m->new_stock]);
        return back()->with('status', "Stock updated: {$before} → {$m->new_stock}.");
    }

    public function movements(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $query = InventoryMovement::with(['variation.product', 'user']);
        if ($q !== '') {
            $like = "%{$q}%";
            $query->whereHas('variation', fn ($v) => $v->where('sku', 'like', $like)->orWhere('barcode', 'like', $like)
                ->orWhereHas('product', fn ($p) => $p->where('name', 'like', $like)));
        }
        if ($request->filled('type')) $query->where('type', $request->type);
        $movements = $query->latest('created_at')->latest('id')->paginate(30)->withQueryString();
        return view('inventory.movements', compact('movements'));
    }
}
