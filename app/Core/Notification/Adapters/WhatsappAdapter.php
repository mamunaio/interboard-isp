<?php

declare(strict_types=1);

namespace App\Core\Notification\Adapters;

use App\Core\Notification\Contracts\NotificationAdapter;
use App\Core\Settings\SettingsRegistry;
use Illuminate\Support\Facades\Http;

class WhatsappAdapter implements NotificationAdapter
{
    public function __construct(
        protected SettingsRegistry $settings
    ) {}

    /**
     * Send WhatsApp message via configured HTTP API wrapper.
     */
    public function send(string $recipient, ?string $subject, string $content): bool
    {
        $apiUrl = $this->settings->get('whatsapp_gateway_url', 'https://api.whatsapp-gateway.com/send');
        $apiToken = $this->settings->get('whatsapp_gateway_token');

        if (empty($apiToken)) {
            throw new \Exception('WhatsApp API credentials not configured.');
        }

        $response = Http::withToken($apiToken)->post($apiUrl, [
            'to' => $recipient,
            'message' => $content,
        ]);

        if ($response->failed()) {
            throw new \Exception('WhatsApp Gateway Error: ' . $response->body());
        }

        return true;
    }
}
