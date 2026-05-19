<?php

declare(strict_types=1);

namespace App\Core\Webhook\Jobs;

use App\Modules\Tenant\Models\WebhookLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Http;

class ProcessWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     */
    public int $tries = 3;

    /**
     * Create a new job instance.
     */
    public function __construct(
        protected int $webhookLogId
    ) {}

    /**
     * Execute the job to perform signing and dispatching outbound POST.
     */
    public function handle(): void
    {
        $log = WebhookLog::with('config')->find($this->webhookLogId);

        if (!$log || !$log->config) {
            return;
        }

        $secret = $log->config->secret_key;
        $payloadJson = json_encode($log->payload);

        // 1. Generate SHA-256 HMAC Digital Signature
        $signature = hash_hmac('sha256', $payloadJson, $secret);

        // 2. Perform HTTP Outbound POST
        try {
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'X-Interboard-Signature' => $signature,
                'User-Agent' => 'Interboard-Webhook-Dispatcher/1.0',
            ])->timeout(10)->post($log->url, $log->payload);

            // 3. Update Log on completion
            $log->update([
                'signature' => $signature,
                'status_code' => $response->status(),
                'response_body' => substr($response->body(), 0, 60000), // cap size to prevent DB bloat
                'status' => $response->successful() ? 'success' : 'failed',
            ]);

            if ($response->failed()) {
                throw new \Exception("Outbound Webhook HTTP Failure: " . $response->status());
            }
        } catch (\Throwable $e) {
            $log->update([
                'signature' => $signature,
                'response_body' => $e->getMessage(),
                'status' => 'failed',
            ]);

            // Release back to queue with decay delay for retries
            $this->release(30 * $this->attempts());
        }
    }
}
