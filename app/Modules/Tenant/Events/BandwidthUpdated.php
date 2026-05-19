<?php

declare(strict_types=1);

namespace App\Modules\Tenant\Events;

use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BandwidthUpdated implements ShouldBroadcast
{
    use Dispatchable, SerializesModels;

    /**
     * Create a new event instance.
     */
    public function __construct(
        public string $speed
    ) {}

    /**
     * Get the channels the event should broadcast on.
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('tenant.dashboard'),
        ];
    }
}
