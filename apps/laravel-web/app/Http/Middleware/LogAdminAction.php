<?php

namespace App\Http\Middleware;

use App\Models\ActivityLog;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class LogAdminAction
{
    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();

        if ($user && $user->isAdmin() && in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'], true)) {
            ActivityLog::query()->create([
                'user_id' => $user->id,
                'action_type' => strtolower($request->method()).':'.$request->path(),
                'action_data' => [
                    'payload' => $request->except(['password', 'password_confirmation', '_token']),
                ],
                'ip_address' => $request->ip(),
                'user_agent' => substr((string) $request->userAgent(), 0, 1000),
            ]);
        }

        return $response;
    }
}
