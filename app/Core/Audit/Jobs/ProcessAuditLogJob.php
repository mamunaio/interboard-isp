<?php

declare(strict_types=1);

namespace App\Core\Audit\Jobs;

use App\Modules\Tenant\Models\AuditLog;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class ProcessAuditLogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * Create a new job instance.
     */
    public function __construct(
        public array $payload
    ) {}


    /**
     * Execute the job to insert the audit log asynchronously.
     */
    public function handle(): void
    {
        AuditLog::create($this->payload);
    }
}
