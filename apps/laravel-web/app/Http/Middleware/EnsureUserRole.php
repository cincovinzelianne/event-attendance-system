<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserRole
{
    /**
     * Ensure authenticated users can only access routes for their assigned role.
     */
    public function handle(Request $request, Closure $next, string $role): Response|RedirectResponse
    {
        $user = $request->user();

        if (! $user || $user->role !== $role) {
            abort(403, 'Unauthorized role access.');
        }

        return $next($request);
    }
}
