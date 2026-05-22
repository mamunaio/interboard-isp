<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SuperAdminMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        // Check both default (web) and staff guards — mirrors the pattern used in the app layout
        $user = $request->user() ?? $request->user('staff');

        if (!$user) {
            abort(401, 'Authentication required. Please log in first.');
        }

        // For regular users (from `users` table), check is_superadmin flag
        // For staff users, check if they have an admin-level role
        $isSuperAdmin = false;

        if (isset($user->is_superadmin)) {
            $isSuperAdmin = (bool) $user->is_superadmin;
        }

        // Also allow staff users with 'admin' role to access SaaS panel
        if (!$isSuperAdmin && method_exists($user, 'hasRole')) {
            $isSuperAdmin = $user->hasRole('super_admin') || $user->hasRole('platform_admin');
        }

        if (!$isSuperAdmin) {
            abort(403, 'Access denied. Super Admin privileges required.');
        }

        return $next($request);
    }
}
