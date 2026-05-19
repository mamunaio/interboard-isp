<?php

declare(strict_types=1);

namespace App\Modules\Tenant\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class GatewayStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public string $attemptReference,
        public string $gateway,
        public string $amount,
        public string $status,
        public string $timestamp
    ) {}

    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.dashboard'),
        ];
    }
}
