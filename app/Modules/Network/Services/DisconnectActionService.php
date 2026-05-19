<?php

declare(strict_types=1);

namespace App\Modules\Network\Services;

use App\Modules\Network\Models\RadAcct;
use App\Modules\Provisioning\Services\MikroTikApiService;
use Exception;

class DisconnectActionService
{
    public function __construct(
        protected MikroTikApiService $mikrotikApi
    ) {}

    /**
     * Forcefully disconnect an active session using MikroTik API.
     * We use the username (subscription_code) to find the active connection.
     */
    public function disconnectUser(string $username, int $tenantId): bool
    {
        try {
            // Drop the active connection from the NAS
            $this->mikrotikApi->terminateActiveConnection($username);

            // Optional: Manually close the session in radacct if RADIUS misses the disconnect packet
            $activeSession = RadAcct::where('tenant_id', $tenantId)
                ->where('username', $username)
                ->whereNull('acctstoptime')
                ->latest('acctstarttime')
                ->first();

            if ($activeSession) {
                $activeSession->update([
                    'acctstoptime' => now(),
                    'acctterminatecause' => 'Admin-Reset',
                ]);
            }

            return true;
        } catch (Exception $e) {
            // Log error
            return false;
        }
    }
}
