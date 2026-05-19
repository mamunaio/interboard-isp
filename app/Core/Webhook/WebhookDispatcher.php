<?php

declare(strict_types=1);

namespace App\Core\Webhook;

use App\Core\Tenant\TenantManager;
use App\Core\Webhook\Jobs\ProcessWebhookJob;
use App\Modules\Tenant\Models\WebhookConfig;
use App\Modules\Tenant\Models\WebhookLog;

class WebhookDispatcher
{
    public function __construct(
        protected TenantManager $tenantManager
    ) {}

    /**
     * Dispatch an outbound webhook event for all active tenant webhook endpoints matching the event type.
     */
    public function dispatch(string $event, array $payload): void
    {
        $tenantId = $this->tenantManager->getTenantId();

        if (!$tenantId) {
            return;
        }

        // 1. Locate all active webhook endpoints matching this event type
        $configs = WebhookConfig::where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->get();

        foreach ($configs as $config) {
            // Check if this endpoint subscribes to this specific event (or matches wildcard '*')
            if (in_array($event, $config->events, true) || in_array('*', $config->events, true)) {
                
                // 2. Create pending log record
                $log = WebhookLog::create([
                    'tenant_id' => $tenantId,
                    'webhook_config_id' => $config->id,
                    'event' => $event,
                    'url' => $config->url,
                    'payload' => $payload,
                    'signature' => '',
                    'status' => 'pending',
                ]);

                // 3. Dispatch processing job to queue
                ProcessWebhookJob::dispatch($log->id);
            }
        }
    }
}
