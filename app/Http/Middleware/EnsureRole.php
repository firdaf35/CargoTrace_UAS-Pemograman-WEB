<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Server-side role check: usage `role:admin,operator`.
 */
class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        abort_unless($user && in_array($user->role->value, $roles, true), 403, 'You do not have permission to access this resource.');

        return $next($request);
    }
}
