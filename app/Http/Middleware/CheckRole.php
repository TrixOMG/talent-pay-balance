<?php

namespace App\Http\Middleware;

use App\Enums\UserRole;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next, string $role): Response
    {
        if (!$request->user() || !$request->user()->role) {
            abort(403, 'Доступ запрещен');
        }

        if ($request->user()->role === UserRole::CLIENT && $request->user()->role->value !== $role) {
            return redirect('/client');
        }

        if ($request->user()->role === UserRole::ADMIN && $request->user()->role->value !== $role) {
            return redirect('/admin');
        }

        return $next($request);
    }
}
