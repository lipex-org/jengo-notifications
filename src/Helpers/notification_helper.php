<?php

declare(strict_types=1);

use Jengo\Notifications\Notification;
use Jengo\Notifications\NotificationManager;
use Jengo\Notifications\Support\AnonymousNotifiable;

if (!function_exists('notify')) {
    /**
     * Send a notification to one or multiple notifiables.
     */
    function notify(mixed $notifiables, Notification $notification): void
    {
        Notification::send($notifiables, $notification);
    }
}

if (!function_exists('notification')) {
    /**
     * Get the NotificationManager instance or start an on-demand notification.
     */
    function notification(?string $channel = null, mixed $route = null): NotificationManager|AnonymousNotifiable
    {
        $manager = NotificationManager::getInstance();

        if ($channel !== null && $route !== null) {
            return (new AnonymousNotifiable())->route($channel, $route);
        }

        return $manager;
    }
}
