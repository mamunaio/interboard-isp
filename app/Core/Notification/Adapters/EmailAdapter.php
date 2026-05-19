<?php

declare(strict_types=1);

namespace App\Core\Notification\Adapters;

use App\Core\Notification\Contracts\NotificationAdapter;
use Illuminate\Support\Facades\Mail;

class EmailAdapter implements NotificationAdapter
{
    /**
     * Send email via Laravel standard Mail Facade.
     */
    public function send(string $recipient, ?string $subject, string $content): bool
    {
        Mail::html($content, function ($message) use ($recipient, $subject) {
            $message->to($recipient)
                ->subject($subject ?? 'Notification');
        });

        return true;
    }
}
