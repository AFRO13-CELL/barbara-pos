<?php
namespace App\Http\Middleware;
use Closure;
use Illuminate\Http\Request;

// Usage: Route::middleware('permission:products.view')
class CheckPermission
{
    public function handle(Request $request, Closure $next, string $permission)
    {
        $user = $request->user();
        abort_unless($user && $user->is_active && $user->can_do($permission), 403, 'You do not have access to this area.');
        return $next($request);
    }
}
