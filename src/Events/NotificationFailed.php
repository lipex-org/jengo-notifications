<?php

declare(strict_types=1);

namespace Jengo\Notifications\Events;

use Jengo\Notifications\Notification;
use Throwable;

class NotificationFailed
{
    public function __construct(
        public readonly object $notifiable,
        public readonly Notification $notification,
        public readonly string $channel,
        public readonly ?Throwable $exception = null,
        public readonly mixed $data = null
    ) {
    }
}
