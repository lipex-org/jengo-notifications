<?php

declare(strict_types=1);

namespace Jengo\Notifications\Events;

use Jengo\Notifications\Notification;

class NotificationSent
{
    public function __construct(
        public readonly object $notifiable,
        public readonly Notification $notification,
        public readonly string $channel,
        public readonly mixed $response = null
    ) {
    }
}
