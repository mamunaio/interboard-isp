<?php

declare(strict_types=1);

namespace App\Core\Notification\Adapters;

use App\Core\Notification\Contracts\NotificationAdapter;
use App\Core\Settings\SettingsRegistry;
use Illuminate\Support\Facades\Http;

class SmsAdapter implements NotificationAdapter
{
    public function __construct(
        protected SettingsRegistry $settings
    ) {}

    /**
     * Send SMS via configured dynamic API gateway.
     */
    public function send(string $recipient, ?string $subject, string $content): bool
    {
        // Fetch API credentials dynamically using settings registry
        $apiUrl = $this->settings->get('sms_gateway_url', 'https://api.sms-gateway.com/send');
        $apiKey = $this->settings->get('sms_gateway_key');

        if (empty($apiKey)) {
            throw new \Exception('SMS API credentials not configured.');
        }

        $response = Http::post($apiUrl, [
            'api_key' => $apiKey,
            'to' => $recipient,
            'message' => $content,
        ]);

        if ($response->failed()) {
            throw new \Exception('SMS Gateway Error: ' . $response->body());
        }

        return true;
    }
}
