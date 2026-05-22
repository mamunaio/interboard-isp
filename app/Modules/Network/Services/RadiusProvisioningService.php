<?php

declare(strict_types=1);

namespace App\Modules\Network\Services;

use App\Modules\Customer\Models\CustomerServiceSubscription;
use App\Modules\Network\Models\RadCheck;
use App\Modules\Network\Models\RadReply;
use Exception;

class RadiusProvisioningService
{
    /**
     * Synchronize subscription configuration to RADIUS tables.
     */
    public function sync(CustomerServiceSubscription $subscription, string $actionType, array $payload = []): void
    {
        $tenantId = $subscription->tenant_id;
        $username = $payload['username'] ?? $subscription->subscription_code;
        $password = $payload['password'] ?? 'default123';

        switch ($actionType) {
            case 'provision':
            case 'reactivate':
            case 'profile_change':
                $this->createOrUpdateRadiusUser($tenantId, $subscription->id, $username, $password, $subscription);
                break;

            case 'suspend':
                $this->suspendRadiusUser($tenantId, $username);
                break;

            case 'terminate':
                $this->deleteRadiusUser($tenantId, $username);
                break;
        }
    }

    /**
     * Create or update a RADIUS check & reply rules.
     */
    protected function createOrUpdateRadiusUser(
        int $tenantId,
        int $subscriptionId,
        string $username,
        string $password,
        CustomerServiceSubscription $subscription
    ): void {
        // 1. Update/Create RadCheck (Auth credentials)
        RadCheck::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'username' => $username,
                'attribute' => 'Cleartext-Password',
            ],
            [
                'subscription_id' => $subscriptionId,
                'op' => ':=',
                'value' => $password,
            ]
        );

        // 2. Resolve speed profiles / attributes
        $package = $subscription->servicePackage;
        $rateLimit = '10M/10M'; // Default limit
        
        if ($package) {
            if (!empty($package->radius_profile_name)) {
                $rateLimit = $package->radius_profile_name;
            } elseif ($package->download_speed_kbps > 0 && $package->upload_speed_kbps > 0) {
                // Format: upload_limit/download_limit (e.g. 51200k/51200k)
                $rateLimit = sprintf(
                    '%dK/%dK',
                    $package->upload_speed_kbps,
                    $package->download_speed_kbps
                );
            }
        }

        // Update/Create RadReply (Rate Limit / Speed Profile)
        RadReply::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'username' => $username,
                'attribute' => 'Mikrotik-Rate-Limit',
            ],
            [
                'subscription_id' => $subscriptionId,
                'op' => '=',
                'value' => $rateLimit,
            ]
        );
    }

    /**
     * Suspend user by deleting or modifying credentials.
     */
    protected function suspendRadiusUser(int $tenantId, string $username): void
    {
        // One standard way is to modify check to block, or change Password
        // Let's modify the Cleartext-Password to a random suspended password
        // so authentication fails.
        RadCheck::where('tenant_id', $tenantId)
            ->where('username', $username)
            ->where('attribute', 'Cleartext-Password')
            ->update([
                'value' => 'SUSPENDED_' . bin2hex(random_bytes(4)),
            ]);

        // Alternatively, we can add a Mikrotik-Address-List reply rule to redirect to suspension page
        RadReply::updateOrCreate(
            [
                'tenant_id' => $tenantId,
                'username' => $username,
                'attribute' => 'Mikrotik-Address-List',
            ],
            [
                'op' => '=',
                'value' => 'suspended_users',
            ]
        );
    }

    /**
     * Delete user from RADIUS tables.
     */
    protected function deleteRadiusUser(int $tenantId, string $username): void
    {
        RadCheck::where('tenant_id', $tenantId)->where('username', $username)->delete();
        RadReply::where('tenant_id', $tenantId)->where('username', $username)->delete();
    }
}
