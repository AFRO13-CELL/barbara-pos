<?php
namespace App\Http\Controllers;
use App\Models\{ReturnItem, Sale, SaleReturn};
use App\Services\ReturnService;
use Illuminate\Http\Request;

class ReturnController extends Controller
{
    public const REASONS = ['Wrong size', 'Defective', 'Changed mind', 'Not as described', 'Damaged on delivery', 'Other'];

    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $query = SaleReturn::with(['sale', 'customer', 'user', 'items']);
        if ($q !== '') {
            $like = "%{$q}%";
            $query->where(fn ($w) => $w->where('return_number', 'like', $like)
                ->orWhereHas('sale', fn ($s) => $s->where('receipt_number', 'like', $like))
                ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', $like))
                ->orWhereHas('items', fn ($i) => $i->where('product_name', 'like', $like)));
        }
        $returns = $query->latest()->paginate(20)->withQueryString();
        return view('returns.index', compact('returns'));
    }

    public function create(Request $request)
    {
        $sale = $request->filled('sale') ? Sale::with(['items', 'customer'])->find($request->sale) : null;
        $returned = $sale ? ReturnItem::whereIn('sale_item_id', $sale->items->pluck('id'))
            ->selectRaw('sale_item_id, SUM(quantity) as q')->groupBy('sale_item_id')->pluck('q', 'sale_item_id') : collect();
        return view('returns.create', ['sale' => $sale, 'returned' => $returned, 'reasons' => self::REASONS, 'lookup' => $request->get('receipt')]);
    }

    public function find(Request $request)
    {
        $s = Sale::where('receipt_number', trim((string) $request->get('receipt')))->first();
        if (!$s) return back()->withErrors(['receipt' => 'No sale found with that receipt number.']);
        return redirect()->route('returns.create', ['sale' => $s->id]);
    }

    public function store(Request $request)
    {
        $d = $request->validate([
            'sale_id' => 'required|exists:sales,id',
            'type' => 'required|in:refund,exchange,store_credit',
            'reason' => 'required|in:' . implode(',', self::REASONS),
            'refund_method' => 'nullable|in:cash,mobile_money,card,bank_transfer',
            'refund_reference' => 'nullable|string|max:100',
            'notes' => 'nullable|string|max:500',
            'items' => 'required|array',
            'items.*.qty' => 'nullable|integer|min:0|max:10000',
            'items.*.condition' => 'nullable|in:sellable,damaged',
        ]);
        $rows = [];
        foreach ($d['items'] as $saleItemId => $row) {
            if ((int) ($row['qty'] ?? 0) > 0) $rows[] = ['sale_item_id' => (int) $saleItemId, 'qty' => (int) $row['qty'], 'condition' => $row['condition'] ?? 'sellable'];
        }
        try {
            $ret = ReturnService::process($request->user(), Sale::findOrFail($d['sale_id']), $d + ['items' => $rows]);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['return' => $e->getMessage()])->withInput();
        }
        return redirect()->route('returns.index', ['q' => $ret->return_number])->with('status', "Return {$ret->return_number} saved.");
    }
}
