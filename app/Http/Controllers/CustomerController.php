<?php
namespace App\Http\Controllers;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    private function rules(): array
    {
        return ['name' => 'required|string|max:150', 'phone' => 'nullable|string|max:40', 'email' => 'nullable|email|max:150',
                'address' => 'nullable|string|max:255', 'notes' => 'nullable|string|max:1000'];
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $query = Customer::withCount('sales')->withSum('sales', 'total')->withSum('sales', 'balance');
        if ($q !== '') {
            $like = "%{$q}%";
            $query->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('phone', 'like', $like)->orWhere('email', 'like', $like));
        }
        $customers = $query->orderBy('name')->paginate(25)->withQueryString();
        return view('customers.index', compact('customers'));
    }

    public function store(Request $request)
    {
        $customer = Customer::create($request->validate($this->rules()));
        return redirect()->route('customers.show', $customer)->with('status', 'Customer added.');
    }

    public function show(Customer $customer)
    {
        $stats = [
            'purchases' => $customer->sales()->count(),
            'spent' => (float) $customer->sales()->sum('total'),
            'balance' => (float) $customer->sales()->sum('balance'),
            'last' => $customer->sales()->latest()->value('created_at'),
        ];
        $sales = $customer->sales()->latest()->paginate(15);
        return view('customers.show', compact('customer', 'stats', 'sales'));
    }

    public function update(Request $request, Customer $customer)
    {
        $customer->update($request->validate($this->rules()));
        return back()->with('status', 'Customer saved.');
    }
}
