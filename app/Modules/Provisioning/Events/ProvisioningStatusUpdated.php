<?php

declare(strict_types=1);

namespace App\Modules\Provisioning\Events;

use App\Modules\Provisioning\Models\ServiceProvisioningEvent;
use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class ProvisioningStatusUpdated implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public ServiceProvisioningEvent $event,
        public string $stage
    ) {}

    public function broadcastOn(): Channel
    {
        return new Channel('tenant.' . $this->event->tenant_id . '.provisioning');
    }

    public function broadcastAs(): string
    {
        return 'provisioning.status.updated';
    }
}
