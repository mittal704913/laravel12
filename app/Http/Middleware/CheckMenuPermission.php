<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;
use App\Models\Menu;

class CheckMenuPermission
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // login check
        if (!$user) {
            return redirect()->route('login');
        }

        // permission check
        $currentRoute = $request->route()->uri();

        // menu check
        $menu = Menu::where('route', '/' . ltrim($currentRoute, '/'))->first();

        // menu permission check
        if ($menu && $menu->permission_name) {
            if (!$user->can($menu->permission_name)) {
                abort(403, 'Unauthorized - You do not have permission to access this page.');
            }
        }

        return $next($request);
    }
}
