<?php

declare(strict_types=1);

namespace Jengo\Notifications\Channels;

use Jengo\Notifications\Contracts\ChannelInterface;
use Jengo\Notifications\Entities\DatabaseNotification;
use Jengo\Notifications\Messages\DatabaseMessage;
use Jengo\Notifications\Models\NotificationModel;
use Jengo\Notifications\Notification;

class DatabaseChannel implements ChannelInterface
{
    public function send(object $notifiable, Notification $notification): mixed
    {
        if (!method_exists($notification, 'toDatabase')) {
            return null;
        }

        $data = $notification->toDatabase($notifiable);
        if ($data instanceof DatabaseMessage) {
            $data = $data->toArray();
        }

        if (!is_array($data)) {
            return null;
        }

        $notifiableType = method_exists($notifiable, 'getNotifiableMorphClass')
            ? $notifiable->getNotifiableMorphClass()
            : get_class($notifiable);

        $notifiableId = method_exists($notifiable, 'getNotifiableKey')
            ? $notifiable->getNotifiableKey()
            : ($notifiable->id ?? 0);

        $record = [
            'id'              => $notification->id,
            'type'            => get_class($notification),
            'notifiable_type' => $notifiableType,
            'notifiable_id'   => (string) $notifiableId,
            'data'            => json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE),
            'read_at'         => null,
        ];

        $model = new NotificationModel();
        $model->insert($record);

        return new DatabaseNotification($record);
    }
}
