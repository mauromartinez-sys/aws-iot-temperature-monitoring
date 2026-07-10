<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasPermission
{
    public function handle(Request $request, Closure $next, string $permission): Response
    {
        $user = $request->user();

        if (!$user || !method_exists($user, 'hasPermission') || !$user->hasPermission($permission)) {
            if ($user && $request->isMethod('GET') && method_exists($user, 'homeRouteName')) {
                return redirect()->route($user->homeRouteName());
            }

            abort(403, 'No tenés permisos para acceder a este recurso.');
        }

        return $next($request);
    }
}
