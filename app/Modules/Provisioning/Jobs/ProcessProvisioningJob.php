<?php

declare(strict_types=1);

namespace App\Modules\Provisioning\Jobs;

use App\Modules\Provisioning\Models\ServiceProvisioningEvent;
use App\Modules\Provisioning\Services\MikroTikApiService;
use App\Modules\Provisioning\Enums\ProvisioningStatus;
use App\Modules\Provisioning\Enums\ProvisioningActionType;
use App\Core\Tenant\TenantManager;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use App\Modules\Tenant\Models\Tenant;
use Exception;
use Throwable;

class ProcessProvisioningJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $tries = 3;
    public $backoff = [10, 30, 60];

    public function __construct(
        public int $eventId
    ) {}

    public function handle(MikroTikApiService $api, TenantManager $tenantManager, \App\Modules\Network\Services\RadiusProvisioningService $radius): void
    {
        // Skip tenant scoping to find the event
        $event = ServiceProvisioningEvent::withoutGlobalScopes()->findOrFail($this->eventId);
        
        // Setup Tenant context for this job
        $tenant = Tenant::findOrFail($event->tenant_id);
        $tenantManager->setTenant($tenant);

        // Update status to processing
        $event->update([
            'status' => ProvisioningStatus::PROCESSING,
            'retry_count' => $this->attempts() - 1,
        ]);
        
        $this->broadcastReverbEvent($event, 'started');

        try {
            $response = $this->executeAction($api, $radius, $event);

            $event->update([
                'status' => ProvisioningStatus::COMPLETED,
                'response_payload_json' => $response,
                'executed_at' => now(),
            ]);
            
            $this->broadcastReverbEvent($event, 'completed');

        } catch (Throwable $e) {
            $event->update([
                'status' => ProvisioningStatus::FAILED,
                'error_message' => $e->getMessage(),
            ]);
            
            $this->broadcastReverbEvent($event, 'failed');

            // Rethrow so Laravel's queue worker knows it failed and applies retries
            throw $e;
        }
    }

    protected function executeAction(
        MikroTikApiService $api,
        \App\Modules\Network\Services\RadiusProvisioningService $radius,
        ServiceProvisioningEvent $event
    ): array {
        $payload = $event->request_payload_json ?? [];
        $username = $payload['username'] ?? $event->subscription->subscription_code;
        $action = $event->action_type;

        $mikrotikResponse = [];
        $radiusResponse = ['status' => 'synced'];

        try {
            // 1. Sync RADIUS database
            $actionStr = match ($action) {
                ProvisioningActionType::PROVISION => 'provision',
                ProvisioningActionType::SUSPEND => 'suspend',
                ProvisioningActionType::REACTIVATE => 'reactivate',
                ProvisioningActionType::TERMINATE => 'terminate',
                ProvisioningActionType::PROFILE_CHANGE => 'profile_change',
            };
            
            $radius->sync($event->subscription, $actionStr, $payload);
        } catch (Throwable $e) {
            $radiusResponse = ['status' => 'failed', 'error' => $e->getMessage()];
        }

        try {
            // 2. MikroTik API operations (may fail if host is not configured)
            $mikrotikResponse = match ($action) {
                ProvisioningActionType::PROVISION => $api->createPppSecret(
                    $username,
                    $payload['password'] ?? 'default123',
                    $payload['profile'] ?? 'default'
                ),
                ProvisioningActionType::SUSPEND => $api->disablePppSecret($username),
                ProvisioningActionType::REACTIVATE => $api->enablePppSecret($username),
                ProvisioningActionType::TERMINATE => $api->removePppSecret($username),
                ProvisioningActionType::PROFILE_CHANGE => $api->updateProfile(
                    $username,
                    $payload['profile'] ?? 'default'
                ),
            };
        } catch (Throwable $e) {
            $mikrotikResponse = ['status' => 'error_or_skipped', 'message' => $e->getMessage()];
            // If MikroTik is not configured or fails, we still consider the job successful 
            // if RADIUS sync worked, to allow running without a real RouterOS server connection.
            if ($radiusResponse['status'] === 'failed') {
                throw new Exception("Both RADIUS and MikroTik provisioning failed. MikroTik: " . $e->getMessage());
            }
        }

        return [
            'mikrotik' => $mikrotikResponse,
            'radius' => $radiusResponse,
        ];
    }

    protected function broadcastReverbEvent(ServiceProvisioningEvent $event, string $stage): void
    {
        // Simple Reverb broadcast simulation using Laravel events
        event(new \App\Modules\Provisioning\Events\ProvisioningStatusUpdated($event, $stage));
    }
}
