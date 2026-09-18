<?php

declare(strict_types=1);

namespace Jengo\Notifications\Channels;

use Jengo\Notifications\Contracts\ChannelInterface;
use Jengo\Notifications\Contracts\PushDriverInterface;
use Jengo\Notifications\Drivers\Push\FcmPushDriver;
use Jengo\Notifications\Drivers\Push\LogPushDriver;
use Jengo\Notifications\Drivers\Push\NullPushDriver;
use Jengo\Notifications\Drivers\Push\WebPushDriver;
use Jengo\Notifications\Messages\PushMessage;
use Jengo\Notifications\Notification;

class PushChannel implements ChannelInterface
{
    protected ?PushDriverInterface $driver = null;

    public function __construct(?PushDriverInterface $driver = null)
    {
        $this->driver = $driver;
    }

    public function send(object $notifiable, Notification $notification): mixed
    {
        if (!method_exists($notification, 'toPush')) {
            return null;
        }

        $message = $notification->toPush($notifiable);
        if (!$message instanceof PushMessage) {
            return null;
        }

        $target = null;
        if (method_exists($notifiable, 'routeNotificationFor')) {
            $target = $notifiable->routeNotificationFor('push', $notification);
        } elseif (method_exists($notifiable, 'routeNotificationForPush')) {
            $target = $notifiable->routeNotificationForPush($notification);
        } else {
            $target = $notifiable->fcm_token ?? $notifiable->device_token ?? null;
        }

        if (empty($target)) {
            return null;
        }

        $driver = $this->resolveDriver();
        return $driver->send($target, $message);
    }

    public function resolveDriver(): PushDriverInterface
    {
        if ($this->driver !== null) {
            return $this->driver;
        }

        $driverName = 'log';
        if (function_exists('config')) {
            $config = config('Notifications');
            $driverName = $config->push['driver'] ?? 'log';
        }

        return match ($driverName) {
            'fcm'     => new FcmPushDriver(),
            'webpush' => new WebPushDriver(),
            'null'    => new NullPushDriver(),
            default   => new LogPushDriver(),
        };
    }
}
