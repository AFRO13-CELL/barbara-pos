<?php
namespace App\Http\Controllers;
use App\Models\{Category, Customer, ProductVariation};
use App\Services\SaleService;
use Illuminate\Http\Request;

class PosController extends Controller
{
    public function index()
    {
        return view('pos.index', [
            'categories' => Category::where('is_active', true)->orderBy('sort_order')->orderBy('name')->get(['id', 'name']),
            'customers' => Customer::orderBy('name')->limit(500)->get(['id', 'name', 'phone']),
            'canCredit' => auth()->user()->can_do('credit.create'),
            'maxDiscount' => auth()->user()->role?->name === 'owner' ? 100 : (float) auth()->user()->max_discount_percent,
        ]);
    }

    public function search(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $query = ProductVariation::with('product:id,name,category_id')
            ->whereHas('product', function ($p) use ($request) {
                if ($request->filled('category')) $p->where('category_id', $request->category);
            });
        if ($q !== '') {
            $like = "%{$q}%";
            $query->where(function ($w) use ($like) {
                $w->where('sku', 'like', $like)->orWhere('barcode', 'like', $like)
                  ->orWhere('size', 'like', $like)->orWhere('color', 'like', $like)
                  ->orWhereHas('product', fn ($p) => $p->where('name', 'like', $like));
            });
        } else {
            $query->where('stock_qty', '>', 0);
        }
        $rows = $query->orderByDesc('stock_qty')->limit(30)->get()->map(fn ($v) => [
            'id' => $v->id, 'name' => $v->product->name, 'label' => $v->options_label,
            'sku' => $v->sku, 'barcode' => $v->barcode,
            'price' => (float) ($v->discount_price ?: $v->selling_price), 'stock' => (int) $v->stock_qty,
        ]);
        return response()->json(['data' => $rows]);
    }

    public function checkout(Request $request)
    {
        $d = $request->validate([
            'items' => 'required|array|min:1',
            'items.*.variation_id' => 'required|integer|exists:product_variations,id',
            'items.*.qty' => 'required|integer|min:1|max:10000',
            'customer_id' => 'nullable|integer|exists:customers,id',
            'new_customer.name' => 'nullable|string|max:150',
            'new_customer.phone' => 'nullable|string|max:40',
            'discount_type' => 'nullable|in:none,percent,fixed',
            'discount_value' => 'nullable|numeric|min:0',
            'payments' => 'nullable|array',
            'payments.*.method' => 'required|in:cash,mobile_money,card,bank_transfer,store_credit',
            'payments.*.amount' => 'required|numeric|gt:0',
            'payments.*.provider' => 'nullable|string|max:60',
            'payments.*.reference' => 'nullable|string|max:100',
            'due_date' => 'nullable|date|after_or_equal:today',
            'notes' => 'nullable|string|max:500',
        ]);
        foreach ($d['payments'] ?? [] as $p) {
            if (in_array($p['method'], ['mobile_money', 'bank_transfer']) && empty($p['provider'])) {
                return response()->json(['message' => $p['method'] === 'mobile_money' ? 'Enter the Mobile Money provider.' : 'Enter the bank name.'], 422);
            }
            if (!in_array($p['method'], ['cash', 'store_credit']) && empty($p['reference'])) {
                return response()->json(['message' => 'Enter the transaction/payment reference for non-cash payments.'], 422);
            }
        }
        try {
            $sale = SaleService::checkout($request->user(), $d);
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }
        return response()->json(['ok' => true, 'receipt_url' => route('sales.show', $sale)]);
    }
}
