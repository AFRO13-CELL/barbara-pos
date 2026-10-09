<?php
namespace App\Http\Controllers;
use App\Models\{Role, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;

class StaffController extends Controller
{
    public function index(Request $request)
    {
        $q = trim((string) $request->get('q', ''));
        $query = User::with('role');
        if ($q !== '') {
            $like = "%{$q}%";
            $query->where(fn ($w) => $w->where('name', 'like', $like)->orWhere('email', 'like', $like)->orWhereHas('role', fn ($r) => $r->where('label', 'like', $like)));
        }
        return view('staff.index', ['staff' => $query->orderBy('name')->paginate(25)->withQueryString(), 'roles' => Role::orderBy('id')->get()]);
    }

    private function activeOwners(?int $exceptId = null): int
    {
        return User::where('is_active', true)->whereHas('role', fn ($r) => $r->where('name', 'owner'))
            ->when($exceptId, fn ($q) => $q->where('id', '!=', $exceptId))->count();
    }

    private function guardOwnerRules(Request $request, ?User $target, ?Role $newRole)
    {
        $actorIsOwner = $request->user()->role?->name === 'owner';
        if (!$actorIsOwner && ($newRole?->name === 'owner' || $target?->role?->name === 'owner')) {
            return 'Only an owner can create or change an owner account.';
        }
        return null;
    }

    public function store(Request $request)
    {
        $d = $request->validate([
            'name' => 'required|string|max:120', 'email' => 'required|email|max:150|unique:users,email',
            'phone' => 'nullable|string|max:40', 'role_id' => 'required|exists:roles,id',
            'password' => 'required|string|min:10|max:100', 'max_discount_percent' => 'nullable|numeric|min:0|max:100',
        ]);
        if ($msg = $this->guardOwnerRules($request, null, Role::find($d['role_id']))) return back()->withErrors(['role_id' => $msg])->withInput();
        $d['max_discount_percent'] = $d['max_discount_percent'] ?? 0;
        User::create($d + ['is_active' => true]);
        return back()->with('status', 'Staff account created. Share the password privately and ask them to keep it safe.');
    }

    public function update(Request $request, User $user)
    {
        $d = $request->validate([
            'name' => 'required|string|max:120', 'email' => 'required|email|max:150|unique:users,email,' . $user->id,
            'phone' => 'nullable|string|max:40', 'role_id' => 'required|exists:roles,id',
            'password' => 'nullable|string|min:10|max:100', 'max_discount_percent' => 'nullable|numeric|min:0|max:100',
        ]);
        $newRole = Role::find($d['role_id']);
        if ($msg = $this->guardOwnerRules($request, $user, $newRole)) return back()->withErrors(['role_id' => $msg]);

        $active = $request->boolean('is_active');
        $wasOwner = $user->role?->name === 'owner' && $user->is_active;
        if ($wasOwner && ($newRole->name !== 'owner' || !$active) && $this->activeOwners($user->id) < 1) {
            return back()->withErrors(['role_id' => 'There must always be at least one active owner. Make someone else an owner first.']);
        }
        if ($user->id === $request->user()->id && !$active) return back()->withErrors(['is_active' => 'You cannot switch off your own account.']);

        $data = ['name' => $d['name'], 'email' => $d['email'], 'phone' => $d['phone'] ?? null, 'role_id' => $d['role_id'],
                 'max_discount_percent' => $d['max_discount_percent'] ?? 0, 'is_active' => $active];
        if (!empty($d['password'])) $data['password'] = $d['password'];
        $user->update($data);
        Cache::forget("user.{$user->id}.perms");
        return back()->with('status', 'Staff account saved.');
    }

    public function destroy(Request $request, User $user)
    {
        if ($user->id === $request->user()->id) return back()->withErrors(['staff' => 'You cannot remove your own account.']);
        if ($msg = $this->guardOwnerRules($request, $user, null)) return back()->withErrors(['staff' => $msg]);
        if ($user->role?->name === 'owner' && $user->is_active && $this->activeOwners($user->id) < 1) {
            return back()->withErrors(['staff' => 'There must always be at least one active owner.']);
        }
        $user->update(['is_active' => false]);
        $user->delete();                                  // soft delete: their sales and history stay intact
        Cache::forget("user.{$user->id}.perms");
        return back()->with('status', 'Staff account removed. Their past sales and records are kept.');
    }
}
