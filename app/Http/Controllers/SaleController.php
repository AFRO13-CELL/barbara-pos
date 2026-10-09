<?php
namespace App\Http\Controllers;
use App\Models\Sale;
use App\Services\SaleService;
use Illuminate\Http\Request;

class SaleController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $query = Sale::with(['customer', 'user'])->withCount('items');
        if ($q !== '') {
            $like = "%{$q}%";
            $query->where(function ($w) use ($like) {
                $w->where('receipt_number', 'like', $like)
                  ->orWhereHas('customer', fn ($c) => $c->where('name', 'like', $like)->orWhere('phone', 'like', $like))
                  ->orWhereHas('user', fn ($u) => $u->where('name', 'like', $like))
                  ->orWhereHas('items', fn ($i) => $i->where('product_name', 'like', $like)->orWhere('sku', 'like', $like));
            });
        }
        if ($request->filled('method')) $query->whereHas('payments', fn ($p) => $p->where('method', $request->method));
        if ($request->filled('status')) $query->where('payment_status', $request->status);
        if ($request->filled('from')) $query->whereDate('created_at', '>=', $request->from);
        if ($request->filled('to')) $query->whereDate('created_at', '<=', $request->to);
        $sales = $query->latest()->paginate(20)->withQueryString();
        return view('sales.index', compact('sales'));
    }

    public function show(Sale $sale)
    {
        $sale->load(['items', 'payments.user', 'customer', 'user']);
        return view('sales.receipt', compact('sale'));
    }

    public function addPayment(Request $request, Sale $sale)
    {
        $d = $request->validate([
            'method' => 'required|in:cash,mobile_money,card,bank_transfer',
            'amount' => 'required|numeric|gt:0',
            'provider' => 'nullable|string|max:60',
            'reference' => 'nullable|string|max:100',
        ]);
        if (in_array($d['method'], ['mobile_money', 'bank_transfer']) && empty($d['provider'])) {
            return back()->withErrors(['provider' => 'Enter the provider or bank name.'])->withInput();
        }
        if ($d['method'] !== 'cash' && empty($d['reference'])) {
            return back()->withErrors(['reference' => 'Enter the transaction/payment reference.'])->withInput();
        }
        try {
            SaleService::addPayment($request->user(), $sale, $d);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['amount' => $e->getMessage()])->withInput();
        }
        return back()->with('status', 'Payment recorded.');
    }
}
