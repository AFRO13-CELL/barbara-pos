<?php
namespace App\Http\Controllers;
use App\Models\{AuditLog, Permission, Role, Setting, User};
use Illuminate\Http\Request;
use Illuminate\Support\Facades\{Cache, Storage};

class SettingsController extends Controller
{
    private const TEXT = ['business_name', 'business_address', 'business_phone', 'business_email', 'business_website', 'currency_code', 'currency_symbol', 'receipt_footer'];

    public function index()
    {
        return view('settings.index', ['s' => fn ($k, $d = null) => Setting::get($k, $d)]);
    }

    public function update(Request $request)
    {
        $d = $request->validate([
            'business_name' => 'required|string|max:120', 'business_address' => 'nullable|string|max:255', 'business_phone' => 'nullable|string|max:60',
            'business_email' => 'nullable|email|max:150', 'business_website' => 'nullable|string|max:150',
            'currency_code' => 'required|string|max:5', 'currency_symbol' => 'required|string|max:5',
            'receipt_footer' => 'nullable|string|max:255', 'low_stock_threshold' => 'required|integer|min:0|max:1000',
            'large_discount_percent' => 'required|numeric|min:1|max:100',
            'logo' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
        ]);
        $new = collect($d)->only(self::TEXT)->all() + [
            'low_stock_threshold' => (string) $d['low_stock_threshold'], 'large_discount_percent' => (string) $d['large_discount_percent'],
            'allow_negative_stock' => $request->boolean('allow_negative_stock') ? '1' : '0',
        ];
        $changes = [];
        foreach ($new as $k => $v) {
            $old = Setting::get($k);
            if ((string) $old !== (string) $v) $changes[$k] = [$old, $v];
            Setting::set($k, $v);
        }
        if ($request->hasFile('logo')) {
            $path = $request->file('logo')->store('branding', 'public');
            $oldLogo = Setting::get('business_logo');
            Setting::set('business_logo', $path);
            if ($oldLogo) Storage::disk('public')->delete($oldLogo);
            $changes['business_logo'] = [$oldLogo, $path];
        } elseif ($request->boolean('remove_logo') && Setting::get('business_logo')) {
            Storage::disk('public')->delete(Setting::get('business_logo'));
            $changes['business_logo'] = [Setting::get('business_logo'), null];
            Setting::set('business_logo', null);
        }
        if ($changes) {
            AuditLog::record('settings_changed', null, auth()->user()->name . ' changed settings: ' . implode(', ', array_keys($changes)),
                collect($changes)->map(fn ($c) => $c[0])->all(), collect($changes)->map(fn ($c) => $c[1])->all());
        }
        return back()->with('status', 'Settings saved.');
    }

    public function roles()
    {
        $roles = Role::with('permissions')->whereIn('name', ['manager', 'cashier'])->get()->keyBy('name');
        $perms = Permission::orderBy('group')->orderBy('id')->get()->groupBy('group');
        return view('settings.roles', compact('roles', 'perms'));
    }

    public function saveRoles(Request $request)
    {
        $request->validate(['perms' => 'nullable|array']);
        foreach (['manager', 'cashier'] as $name) {
            $role = Role::where('name', $name)->firstOrFail();
            $keys = array_keys(array_filter($request->input("perms.$name", [])));
            $ids = Permission::whereIn('key', $keys)->pluck('id');
            $before = $role->permissions()->pluck('key')->sort()->values()->all();
            $role->permissions()->sync($ids);
            $after = $role->permissions()->pluck('key')->sort()->values()->all();
            if ($before !== $after) {
                AuditLog::record('role_permissions_changed', $role, auth()->user()->name . " changed what the {$role->label} role can do",
                    ['removed' => array_values(array_diff($before, $after))], ['added' => array_values(array_diff($after, $before))]);
            }
            foreach (User::where('role_id', $role->id)->pluck('id') as $uid) Cache::forget("user.{$uid}.perms");
        }
        return back()->with('status', 'Role permissions saved. They apply straight away.');
    }
}
