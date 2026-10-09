<?php
namespace App\Http\Controllers;
use App\Models\{PurchaseItem, Supplier};
use Illuminate\Http\Request;

class SupplierController extends Controller
{
    private function rules(): array
    {
        return ['name' => 'required|string|max:150', 'contact_person' => 'nullable|string|max:150', 'phone' => 'nullable|string|max:40',
                'email' => 'nullable|email|max:150', 'address' => 'nullable|string|max:255', 'notes' => 'nullable|string|max:1000'];
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $query = Supplier::withSum('purchases', 'total')->withSum('purchases', 'balance')->withCount('purchases');
        if ($q !== '') {
            $like = "%{$q}%";
            $query->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('phone', 'like', $like)->orWhere('contact_person', 'like', $like)
                ->orWhereHas('purchases.items', fn ($i) => $i->where('product_name', 'like', $like)));
        }
        $suppliers = $query->orderBy('name')->paginate(25)->withQueryString();
        return view('suppliers.index', compact('suppliers'));
    }

    public function store(Request $request)
    {
        $s = Supplier::create($request->validate($this->rules()));
        return redirect()->route('suppliers.show', $s)->with('status', 'Supplier added.');
    }

    public function show(Supplier $supplier)
    {
        $stats = ['total' => (float) $supplier->purchases()->sum('total'), 'balance' => (float) $supplier->purchases()->sum('balance'), 'count' => $supplier->purchases()->count()];
        $products = PurchaseItem::whereHas('purchase', fn ($p) => $p->where('supplier_id', $supplier->id))->distinct()->orderBy('product_name')->pluck('product_name');
        $purchases = $supplier->purchases()->latest('purchase_date')->latest('id')->paginate(15);
        return view('suppliers.show', compact('supplier', 'stats', 'products', 'purchases'));
    }

    public function update(Request $request, Supplier $supplier)
    {
        $supplier->update($request->validate($this->rules()));
        return back()->with('status', 'Supplier saved.');
    }
}
