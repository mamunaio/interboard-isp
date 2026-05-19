<?php

declare(strict_types=1);

namespace App\Core\Http\Middleware;

use App\Core\Tenant\TenantManager;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnforceTenantIsolation
{
    public function __construct(
        protected TenantManager $tenantManager
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        if ($this->tenantManager->hasTenant()) {
            $tenantId = $this->tenantManager->getTenantId();

            // 1. Verify authenticated user matches active tenant
            $user = $request->user();
            if ($user && isset($user->tenant_id) && (int)$user->tenant_id !== $tenantId) {
                // Instantly invalidate authentication context
                auth()->logout();
                abort(Response::HTTP_FORBIDDEN, 'Access Violation: Authenticated user does not belong to the resolved tenant.');
            }

            // 2. Intercept route parameters and check model tenant_ids
            foreach ($request->route()->parameters() as $parameter) {
                if (is_object($parameter) && method_exists($parameter, 'getTable')) {
                    // Check if model has a tenant_id property and verify it matches
                    if (isset($parameter->tenant_id) && (int)$parameter->tenant_id !== $tenantId) {
                        abort(Response::HTTP_FORBIDDEN, 'Access Violation: Cross-tenant data boundary breached.');
                    }
                }
            }
        }

        return $next($request);
    }
}
