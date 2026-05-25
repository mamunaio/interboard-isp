<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Throwable;

class SmsService
{
    /**
     * Send an SMS via a generic BD SMS Gateway API.
     *
     * @param string $phone The recipient phone number (e.g., +88017xxxxxxxx).
     * @param string $message The message body.
     * @return bool True if the SMS was sent successfully, false otherwise.
     */
    public function send(string $phone, string $message): bool
    {
        $apiUrl = config('services.sms.api_url');
        $apiKey = config('services.sms.api_key');
        $senderId = config('services.sms.sender_id', 'Interboard');

        if (empty($apiUrl) || empty($apiKey)) {
            Log::error('SMS Service: Missing API URL or API Key in configuration.');
            return false;
        }

        try {
            // Simulating a generic Bangladeshi SMS Gateway API request
            $response = Http::timeout(10)
                ->withHeaders([
                    'Accept' => 'application/json',
                ])
                ->post($apiUrl, [
                    'api_key' => $apiKey,
                    'to' => $phone,
                    'message' => $message,
                    'sender_id' => $senderId,
                ]);

            if ($response->successful()) {
                return true;
            }

            Log::error('SMS Service: Failed to send SMS.', [
                'phone' => $phone,
                'status' => $response->status(),
                'response' => $response->body(),
            ]);

            return false;
        } catch (Throwable $e) {
            Log::error('SMS Service: Exception occurred while sending SMS.', [
                'phone' => $phone,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return false;
        }
    }
}
