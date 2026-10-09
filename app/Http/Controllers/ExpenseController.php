<?php
namespace App\Http\Controllers;
use App\Models\Expense;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExpenseController extends Controller
{
    private function rules(): array
    {
        return [
            'description' => 'required|string|max:255',
            'category' => 'required|in:' . implode(',', Expense::CATEGORIES),
            'amount' => 'required|numeric|gt:0|max:99999999',
            'expense_date' => 'required|date',
            'payment_method' => 'required|in:cash,mobile_money,card,bank_transfer',
            'attachment' => 'nullable|file|mimes:jpg,jpeg,png,pdf|max:4096',
            'notes' => 'nullable|string|max:500',
        ];
    }

    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $query = Expense::with('user');
        if ($q !== '') {
            $like = "%{$q}%";
            $query->where(fn ($w) => $w->where('description', 'like', $like)->orWhere('category', 'like', $like));
        }
        if ($request->filled('category')) $query->where('category', $request->category);
        if ($request->filled('method')) $query->where('payment_method', $request->method);
        if ($request->filled('from')) $query->whereDate('expense_date', '>=', $request->from);
        if ($request->filled('to')) $query->whereDate('expense_date', '<=', $request->to);
        $filteredTotal = (float) (clone $query)->sum('amount');
        $expenses = $query->latest('expense_date')->latest('id')->paginate(20)->withQueryString();
        $today = (float) Expense::whereDate('expense_date', today())->sum('amount');
        $month = (float) Expense::whereYear('expense_date', now()->year)->whereMonth('expense_date', now()->month)->sum('amount');
        return view('expenses.index', ['expenses' => $expenses, 'categories' => Expense::CATEGORIES,
            'filteredTotal' => $filteredTotal, 'today' => $today, 'month' => $month]);
    }

    public function store(Request $request)
    {
        $d = $request->validate($this->rules());
        $data = collect($d)->except('attachment')->all() + ['user_id' => $request->user()->id];
        if ($request->hasFile('attachment')) $data['attachment_path'] = $request->file('attachment')->store('expenses', 'local');
        Expense::create($data);
        return back()->with('status', 'Expense saved.');
    }

    public function update(Request $request, Expense $expense)
    {
        $d = $request->validate($this->rules());
        $data = collect($d)->except('attachment')->all();
        if ($request->hasFile('attachment')) $data['attachment_path'] = $request->file('attachment')->store('expenses', 'local');
        $expense->update($data);
        return back()->with('status', 'Expense updated.');
    }

    public function destroy(Expense $expense)
    {
        $expense->delete();
        return back()->with('status', 'Expense deleted. It is kept in the audit log.');
    }

    // Receipts are private: only signed-in users with access can open them.
    public function attachment(Expense $expense)
    {
        abort_unless($expense->attachment_path && Storage::disk('local')->exists($expense->attachment_path), 404);
        return Storage::disk('local')->response($expense->attachment_path);
    }
}
