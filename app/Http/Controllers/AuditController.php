<?php
namespace App\Http\Controllers;
use App\Models\AuditLog;
use Illuminate\Http\Request;

class AuditController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $query = AuditLog::with('user');
        if ($q !== '') {
            $like = "%{$q}%";
            $query->where(fn ($w) => $w->where('action', 'like', $like)->orWhere('description', 'like', $like)->orWhereHas('user', fn ($u) => $u->where('name', 'like', $like)));
        }
        if ($request->filled('from')) $query->whereDate('created_at', '>=', $request->from);
        if ($request->filled('to')) $query->whereDate('created_at', '<=', $request->to);
        return view('audit.index', ['logs' => $query->latest('id')->paginate(50)->withQueryString()]);
    }
}
