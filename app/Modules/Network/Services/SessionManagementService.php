<?php

declare(strict_types=1);

namespace App\Modules\Network\Services;

use App\Modules\Network\Models\RadAcct;
use Illuminate\Support\Collection;
use Carbon\Carbon;

class SessionManagementService
{
    /**
     * Get all currently active sessions for a tenant.
     */
    public function getActiveSessions(int $tenantId): Collection
    {
        return RadAcct::where('tenant_id', $tenantId)
            ->whereNull('acctstoptime')
            ->orderBy('acctstarttime', 'desc')
            ->get();
    }

    /**
     * Get historical sessions for a specific subscription.
     */
    public function getSubscriptionSessions(int $subscriptionId, int $limit = 50): Collection
    {
        return RadAcct::where('subscription_id', $subscriptionId)
            ->orderBy('acctstarttime', 'desc')
            ->limit($limit)
            ->get();
    }

    /**
     * Calculate total data usage (upload/download in bytes) for a subscription in the current billing cycle.
     */
    public function getSubscriptionUsage(int $subscriptionId, string $cycleStartDate): array
    {
        $stats = RadAcct::where('subscription_id', $subscriptionId)
            ->where('acctstarttime', '>=', Carbon::parse($cycleStartDate))
            ->selectRaw('SUM(acctinputoctets) as total_upload, SUM(acctoutputoctets) as total_download')
            ->first();

        return [
            'upload_bytes' => (int)($stats->total_upload ?? 0),
            'download_bytes' => (int)($stats->total_download ?? 0),
        ];
    }
}
