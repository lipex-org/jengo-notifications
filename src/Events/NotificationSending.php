<?php

declare(strict_types=1);

namespace Jengo\Notifications\Events;

use Jengo\Notifications\Notification;

class NotificationSending
{
    public function __construct(
        public readonly object $notifiable,
        public readonly Notification $notification,
        public readonly string $channel
    ) {
    }
}
