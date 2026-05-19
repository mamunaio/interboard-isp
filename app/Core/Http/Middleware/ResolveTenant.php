<?php

declare(strict_types=1);

namespace App\Core\Http\Middleware;

use App\Core\Tenant\TenantManager;
use App\Modules\Tenant\Models\Tenant;
use App\Modules\Tenant\Models\TenantDomain;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ResolveTenant
{
    /**
     * Create a new middleware instance.
     */
    public function __construct(
        protected TenantManager $tenantManager
    ) {}

    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $host = $request->getHost();
        $tenant = null;

        // 1. Resolve via Custom Domain or Subdomain (Primary Pathway)
        $domainRecord = TenantDomain::withoutGlobalScopes()->where('domain', $host)->first();
        if ($domainRecord) {
            $tenant = $domainRecord->tenant;
        }

        if (!$tenant) {
            // Check if it's a subdomain resolved via slug (e.g. {slug}.interboard.net)
            $centralDomain = config('app.central_domain', 'interboard.net');
            if (str_ends_with($host, '.' . $centralDomain)) {
                $subdomain = str_replace('.' . $centralDomain, '', $host);
                $tenant = Tenant::where('slug', $subdomain)->first();
            }
        }



        // 2. Fallback: Resolve via API Headers ONLY if explicitly trusted internal API secret matches
        if (!$tenant && $request->hasHeader('X-Tenant-UUID')) {
            $internalSecret = config('app.internal_api_secret');
            if (!empty($internalSecret) && $request->header('X-Internal-Secret') === $internalSecret) {
                $uuid = $request->header('X-Tenant-UUID');
                $tenant = Tenant::where('uuid', $uuid)->first();
            }
        }

        // 3. Fallback: Check if it matches central domain exactly (superadmin domain)
        if (!$tenant && $host === config('app.central_domain', 'interboard.net')) {
            // Clear any previously resolved tenant context to guarantee clean isolation
            $this->tenantManager->setTenant(null);
            return $next($request);
        }


        // 4. Enforce tenant presence
        if (!$tenant) {
            abort(Response::HTTP_NOT_FOUND, 'Tenant not found.');
        }

        // 5. Enforce tenant status
        if ($tenant->status === 'suspended') {
            abort(Response::HTTP_FORBIDDEN, 'Account Suspended: Please contact support.');
        }

        if ($tenant->status === 'terminated') {
            abort(Response::HTTP_NOT_FOUND, 'Tenant account terminated.');
        }

        // 6. Bind resolved tenant context to singleton
        $this->tenantManager->setTenant($tenant);

        return $next($request);
    }
}
