<?php

declare(strict_types=1);

namespace App\Core\Notification;

use App\Core\Notification\Adapters\EmailAdapter;
use App\Core\Notification\Adapters\SmsAdapter;
use App\Core\Notification\Adapters\WhatsappAdapter;
use App\Core\Tenant\TenantManager;
use App\Modules\Customer\Models\NotificationLog;
use App\Modules\Customer\Models\NotificationTemplate;
use Illuminate\Container\Container;

class NotificationRouter
{
    public function __construct(
        protected TenantManager $tenantManager,
        protected Container $container
    ) {}

    /**
     * Parse template placeholders, execute delivery via appropriate channel adapter, and log output.
     */
    public function send(string $recipient, string $templateSlug, array $variables = [], string $channel = 'email'): bool
    {
        $tenantId = $this->tenantManager->getTenantId();

        // 1. Locate Template
        $template = null;
        if ($tenantId) {
            $template = NotificationTemplate::where('tenant_id', $tenantId)
                ->where('slug', $templateSlug)
                ->where('channel', $channel)
                ->first();
        }

        if (!$template) {
            $template = NotificationTemplate::whereNull('tenant_id')
                ->where('slug', $templateSlug)
                ->where('channel', $channel)
                ->first();
        }

        if (!$template) {
            throw new \Exception("Notification template [{$templateSlug}] for channel [{$channel}] not found.");
        }

        // 2. Parse Placeholders
        $subject = $template->subject;
        $content = $template->content;

        foreach ($variables as $key => $val) {
            $placeholder = '{{' . $key . '}}';
            if ($subject !== null) {
                $subject = str_replace($placeholder, (string)$val, $subject);
            }
            $content = str_replace($placeholder, (string)$val, $content);
        }

        // 3. Create Pending Delivery Log
        $log = NotificationLog::create([
            'tenant_id' => $tenantId ?? 0,
            'channel' => $channel,
            'recipient' => $recipient,
            'subject' => $subject,
            'content' => $content,
            'status' => 'pending',
        ]);

        // 4. Resolve Adapter
        try {
            $adapter = match ($channel) {
                'email' => $this->container->make(EmailAdapter::class),
                'sms' => $this->container->make(SmsAdapter::class),
                'whatsapp' => $this->container->make(WhatsappAdapter::class),
                default => throw new \Exception("Unsupported notification channel: {$channel}"),
            };

            // 5. Execute Delivery
            $adapter->send($recipient, $subject, $content);

            // 6. Update Log on Success
            $log->update([
                'status' => 'sent',
            ]);

            return true;
        } catch (\Throwable $e) {
            // 7. Update Log on Failure
            $log->update([
                'status' => 'failed',
                'error_message' => $e->getMessage(),
            ]);

            return false;
        }
    }
}
