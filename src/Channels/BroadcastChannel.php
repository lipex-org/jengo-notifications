<?php

declare(strict_types=1);

namespace Jengo\Notifications\Channels;

use Jengo\Notifications\Contracts\ChannelInterface;
use Jengo\Notifications\Messages\BroadcastMessage;
use Jengo\Notifications\Notification;

class BroadcastChannel implements ChannelInterface
{
    public function send(object $notifiable, Notification $notification): mixed
    {
        if (!method_exists($notification, 'toBroadcast')) {
            return null;
        }

        $message = $notification->toBroadcast($notifiable);
        if (is_array($message)) {
            $message = new BroadcastMessage($message);
        }

        if (!$message instanceof BroadcastMessage) {
            return null;
        }

        $channelName = $message->channel ?? (method_exists($notifiable, 'routeNotificationFor')
            ? $notifiable->routeNotificationFor('broadcast', $notification)
            : null);

        if (empty($channelName)) {
            $morph = method_exists($notifiable, 'getNotifiableMorphClass')
                ? $notifiable->getNotifiableMorphClass()
                : get_class($notifiable);

            $key = method_exists($notifiable, 'getNotifiableKey')
                ? $notifiable->getNotifiableKey()
                : ($notifiable->id ?? 0);

            $channelName = 'private-' . str_replace('\\', '.', $morph) . '.' . $key;
        }

        // Bridge to jengo/broadcasting if installed
        if (class_exists(\Jengo\Broadcasting\Broadcast::class)) {
            return \Jengo\Broadcasting\Broadcast::on($channelName)
                ->as($message->event ?? 'notification.received')
                ->with($message->toArray())
                ->send();
        }

        if (function_exists('broadcast')) {
            return broadcast($channelName, $message->event ?? 'notification.received', $message->toArray());
        }

        log_message('debug', "[BroadcastChannel] jengo/broadcasting not detected; skipping realtime event.");
        return true;
    }
}
