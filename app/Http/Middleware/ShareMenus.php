<?php

namespace App\Http\Middleware;

use App\Models\Menu;
use Closure;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Symfony\Component\HttpFoundation\Response;

class ShareMenus
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        Inertia::share('menus', function () use ($user) {
            if (!$user) return [];

            // menu secured by permission, but we want to show parent menu if any child has permission
            $allMenus = Menu::orderBy('order')->get();

            // Index menus by id for easy lookup
            $indexed = $allMenus->keyBy('id');

            // Recursive builder (filtered by permission)
            $buildTree = function ($parentId = null) use (&$buildTree, $indexed, $user) {
                return $indexed
                    ->filter(
                        fn($menu) =>
                        $menu->parent_id === $parentId &&
                            (!$menu->permission_name || $user->can($menu->permission_name))
                    )
                    ->map(function ($menu) use (&$buildTree) {
                        $menu->children = $buildTree($menu->id)->values();
                        return $menu;
                    })
                    ->filter(
                        fn($menu) =>
                        $menu->route || $menu->children->isNotEmpty()
                    )
                    ->values();
            };

            $menus = $buildTree();

            return $menus;
        });

        return $next($request);
    }
}
