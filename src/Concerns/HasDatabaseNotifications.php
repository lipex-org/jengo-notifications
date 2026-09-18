<?php

declare(strict_types=1);

namespace Jengo\Notifications\Concerns;

use CodeIgniter\I18n\Time;
use Jengo\Notifications\Entities\DatabaseNotification;
use Jengo\Notifications\Models\NotificationModel;

trait HasDatabaseNotifications
{
    /**
     * Get the morph class name for this notifiable entity.
     */
    public function getNotifiableMorphClass(): string
    {
        return static::class;
    }

    /**
     * Get the identifier value for this notifiable.
     */
    public function getNotifiableKey(): int|string
    {
        if (isset($this->attributes['id'])) {
            return $this->attributes['id'];
        }

        if (property_exists($this, 'id')) {
            return $this->id;
        }

        if (method_exists($this, 'getPrimaryKey')) {
            return $this->getPrimaryKey();
        }

        return 0;
    }

    /**
     * Query all notifications for this notifiable.
     *
     * @return array<int, DatabaseNotification>
     */
    public function notifications(?int $limit = null, int $offset = 0): array
    {
        $model = new NotificationModel();
        $builder = $model->where('notifiable_type', $this->getNotifiableMorphClass())
            ->where('notifiable_id', (string) $this->getNotifiableKey())
            ->orderBy('created_at', 'DESC');

        if ($limit !== null) {
            $builder->limit($limit, $offset);
        }

        return $builder->findAll();
    }

    /**
     * Query all unread notifications for this notifiable.
     *
     * @return array<int, DatabaseNotification>
     */
    public function unreadNotifications(?int $limit = null, int $offset = 0): array
    {
        $model = new NotificationModel();
        $builder = $model->where('notifiable_type', $this->getNotifiableMorphClass())
            ->where('notifiable_id', (string) $this->getNotifiableKey())
            ->where('read_at IS NULL', null, false)
            ->orderBy('created_at', 'DESC');

        if ($limit !== null) {
            $builder->limit($limit, $offset);
        }

        return $builder->findAll();
    }

    /**
     * Query all read notifications for this notifiable.
     *
     * @return array<int, DatabaseNotification>
     */
    public function readNotifications(?int $limit = null, int $offset = 0): array
    {
        $model = new NotificationModel();
        $builder = $model->where('notifiable_type', $this->getNotifiableMorphClass())
            ->where('notifiable_id', (string) $this->getNotifiableKey())
            ->where('read_at IS NOT NULL', null, false)
            ->orderBy('created_at', 'DESC');

        if ($limit !== null) {
            $builder->limit($limit, $offset);
        }

        return $builder->findAll();
    }

    /**
     * Get the total count of unread notifications.
     */
    public function unreadNotificationsCount(): int
    {
        $model = new NotificationModel();
        return $model->where('notifiable_type', $this->getNotifiableMorphClass())
            ->where('notifiable_id', (string) $this->getNotifiableKey())
            ->where('read_at IS NULL', null, false)
            ->countAllResults();
    }

    /**
     * Mark all unread notifications as read.
     */
    public function markAllNotificationsAsRead(): int
    {
        $now = Time::now()->toDateTimeString();
        $model = new NotificationModel();

        $builder = $model->builder();
        $builder->where('notifiable_type', $this->getNotifiableMorphClass())
            ->where('notifiable_id', (string) $this->getNotifiableKey())
            ->where('read_at IS NULL', null, false);

        $count = $builder->countAllResults(false);
        if ($count > 0) {
            $builder->update(['read_at' => $now]);
        }

        return $count;
    }
}
