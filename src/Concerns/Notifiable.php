<?php

declare(strict_types=1);

namespace Jengo\Notifications\Concerns;

use Jengo\Notifications\Notification;
use Jengo\Notifications\NotificationManager;

trait Notifiable
{
    use HasDatabaseNotifications;
    use RoutesNotifications;

    /**
     * Send the given notification to this entity.
     */
    public function notify(Notification $notification): void
    {
        NotificationManager::getInstance()->send($this, $notification);
    }

    /**
     * Send the given notification immediately to this entity.
     */
    public function notifyNow(Notification $notification, ?array $channels = null): void
    {
        NotificationManager::getInstance()->sendNow($this, $notification, $channels);
    }
}
