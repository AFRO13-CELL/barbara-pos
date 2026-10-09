<?php
namespace App\Http\Controllers;
use App\Models\{ProductVariation, Purchase, Supplier};
use App\Services\PurchaseService;
use Illuminate\Http\Request;

class PurchaseController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $query = Purchase::with('supplier')->withCount('items');
        if ($q !== '') {
            $like = "%{$q}%";
            $query->where(fn ($w) => $w->where('purchase_number', 'like', $like)
                ->orWhereHas('supplier', fn ($s) => $s->where('name', 'like', $like))
                ->orWhereHas('items', fn ($i) => $i->where('product_name', 'like', $like)->orWhere('sku', 'like', $like)));
        }
        if ($request->filled('status')) $query->where('status', $request->status);
        if ($request->filled('from')) $query->whereDate('purchase_date', '>=', $request->from);
        if ($request->filled('to')) $query->whereDate('purchase_date', '<=', $request->to);
        $purchases = $query->latest('purchase_date')->latest('id')->paginate(20)->withQueryString();
        return view('purchases.index', compact('purchases'));
    }

    public function create(Request $request)
    {
        return view('purchases.create', [
            'suppliers' => Supplier::orderBy('name')->get(['id', 'name']),
            'selected' => $request->get('supplier'),
        ]);
    }

    public function search(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $query = ProductVariation::with('product:id,name')->whereHas('product');
        if ($q !== '') {
            $like = "%{$q}%";
            $query->where(fn ($w) => $w->where('sku', 'like', $like)->orWhere('barcode', 'like', $like)
                ->orWhere('size', 'like', $like)->orWhere('color', 'like', $like)
                ->orWhereHas('product', fn ($p) => $p->where('name', 'like', $like)));
        }
        $rows = $query->orderBy('id', 'desc')->limit(20)->get()->map(fn ($v) => [
            'id' => $v->id, 'name' => $v->product->name, 'label' => $v->options_label, 'sku' => $v->sku,
            'cost' => (float) $v->cost_price, 'stock' => (int) $v->stock_qty]);
        return response()->json(['data' => $rows]);
    }

    public function store(Request $request)
    {
        $d = $request->validate([
            'supplier_id' => 'required|integer|exists:suppliers,id',
            'purchase_date' => 'required|date',
            'items' => 'required|array|min:1',
            'items.*.variation_id' => 'required|integer|exists:product_variations,id',
            'items.*.qty' => 'required|integer|min:1|max:100000',
            'items.*.cost' => 'required|numeric|min:0',
            'pay_amount' => 'nullable|numeric|min:0',
            'pay_method' => 'nullable|in:cash,mobile_money,card,bank_transfer',
            'pay_reference' => 'nullable|string|max:100',
            'receive_now' => 'nullable|boolean',
            'update_cost' => 'nullable|boolean',
            'notes' => 'nullable|string|max:500',
        ]);
        try {
            $p = PurchaseService::create($request->user(), $d);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['ok' => true, 'url' => route('purchases.show', $p)]);
    }

    public function show(Purchase $purchase)
    {
        $purchase->load(['items', 'payments.user', 'supplier', 'user']);
        return view('purchases.show', compact('purchase'));
    }

    public function receive(Request $request, Purchase $purchase)
    {
        try {
            PurchaseService::receive($request->user(), $purchase, $request->boolean('update_cost'));
        } catch (\RuntimeException $e) {
            return back()->withErrors(['receive' => $e->getMessage()]);
        }
        return back()->with('status', 'Stock received. Inventory has been increased.');
    }

    public function addPayment(Request $request, Purchase $purchase)
    {
        $d = $request->validate(['method' => 'required|in:cash,mobile_money,card,bank_transfer', 'amount' => 'required|numeric|gt:0', 'reference' => 'nullable|string|max:100']);
        if ($d['method'] !== 'cash' && empty($d['reference'])) return back()->withErrors(['reference' => 'Enter the payment reference.'])->withInput();
        try {
            PurchaseService::addPayment($request->user(), $purchase, $d);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['amount' => $e->getMessage()])->withInput();
        }
        return back()->with('status', 'Payment recorded.');
    }
}
