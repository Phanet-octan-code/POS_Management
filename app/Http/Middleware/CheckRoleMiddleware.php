<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckRoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        // Admin has full system access to all resources
        if ($user->hasRole('admin') || $user->hasRole('super-admin')) {
            return $next($request);
        }

        if (! $user->hasRole($roles)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. You do not have the required role to access this resource.',
                ], 403);
            }

            abort(403, 'Unauthorized. Access restricted to: ' . implode(', ', $roles));
        }

        return $next($request);
    }
}
