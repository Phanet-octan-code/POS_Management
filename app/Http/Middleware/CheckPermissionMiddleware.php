<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class CheckPermissionMiddleware
{
    public function handle(Request $request, Closure $next, ...$permissions): Response
    {
        $user = $request->user();

        if (! $user) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthenticated.',
                ], 401);
            }

            return redirect()->route('login');
        }

        // Admins have unrestricted system access
        if ($user->isAdmin()) {
            return $next($request);
        }

        // Support comma-delimited strings like 'products,categories'
        $requiredPermissions = [];
        foreach ($permissions as $perm) {
            foreach (explode(',', $perm) as $p) {
                $trimmed = trim($p);
                if ($trimmed !== '') {
                    $requiredPermissions[] = $trimmed;
                }
            }
        }

        if (empty($requiredPermissions) || ! $user->hasPermission($requiredPermissions)) {
            if ($request->expectsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => 'Unauthorized. You do not have permission to access this resource.',
                    'required' => $requiredPermissions,
                ], 403);
            }

            abort(403, 'Unauthorized. Missing required permission: ' . implode(', ', $requiredPermissions));
        }

        return $next($request);
    }
}
