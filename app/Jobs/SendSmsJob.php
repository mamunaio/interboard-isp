<?php

declare(strict_types=1);

namespace App\Jobs;

use App\Services\SmsService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Exception;

class SendSmsJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * The number of times the job may be attempted.
     *
     * @var int
     */
    public int $tries = 3;

    /**
     * The number of seconds to wait before retrying the job.
     *
     * @var array<int>
     */
    public array $backoff = [10, 30];

    /**
     * Create a new job instance.
     */
    public function __construct(
        public string $phone,
        public string $message
    ) {}

    /**
     * Execute the job.
     *
     * @param SmsService $smsService
     * @return void
     * @throws Exception
     */
    public function handle(SmsService $smsService): void
    {
        $status = $smsService->send($this->phone, $this->message);

        if (!$status) {
            throw new Exception("SMS delivery failed for number: {$this->phone}");
        }
    }
}
