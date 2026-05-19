<?php

declare(strict_types=1);

namespace App\Core\Notification\Contracts;

interface NotificationAdapter
{
    /**
     * Send notification payload.
     * Returns true on success, throws exception on failure.
     */
    public function send(string $recipient, ?string $subject, string $content): bool;
}
