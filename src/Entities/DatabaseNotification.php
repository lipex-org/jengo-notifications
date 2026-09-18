<?php

declare(strict_types=1);

namespace Jengo\Notifications\Entities;

use CodeIgniter\I18n\Time;
use Jengo\Base\Entities\BaseEntity;
use Jengo\Notifications\Models\NotificationModel;

/**
 * @property string $id
 * @property string $type
 * @property string $notifiable_type
 * @property int|string $notifiable_id
 * @property array|string $data
 * @property Time|string|null $read_at
 * @property Time|string $created_at
 * @property Time|string $updated_at
 */
class DatabaseNotification extends BaseEntity
{
    protected $dates = ['read_at', 'created_at', 'updated_at'];
    protected $casts = [
        'id'              => 'string',
        'type'            => 'string',
        'notifiable_type' => 'string',
        'notifiable_id'   => 'string',
    ];

    /**
     * Mutator for data attribute.
     */
    public function setData(array|string $data): static
    {
        if (is_array($data)) {
            $this->attributes['data'] = json_encode($data, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        } else {
            $this->attributes['data'] = $data;
        }

        return $this;
    }

    /**
     * Determine if the notification has been read.
     */
    public function read(): bool
    {
        return !empty($this->attributes['read_at']);
    }

    /**
     * Determine if the notification is unread.
     */
    public function unread(): bool
    {
        return empty($this->attributes['read_at']);
    }

    /**
     * Mark the notification as read.
     */
    public function markAsRead(): static
    {
        if ($this->unread()) {
            $now = Time::now()->toDateTimeString();
            $this->attributes['read_at'] = $now;

            $model = new NotificationModel();
            $model->update($this->attributes['id'], ['read_at' => $now]);
        }

        return $this;
    }

    /**
     * Mark the notification as unread.
     */
    public function markAsUnread(): static
    {
        if ($this->read()) {
            $this->attributes['read_at'] = null;

            $model = new NotificationModel();
            $model->update($this->attributes['id'], ['read_at' => null]);
        }

        return $this;
    }

    /**
     * Get the notification payload array.
     */
    public function getData(): array
    {
        $val = $this->attributes['data'] ?? [];
        if (is_array($val)) {
            return $val;
        }

        if (is_string($val)) {
            $decoded = json_decode($val, true);
            while (is_string($decoded)) {
                $decoded = json_decode($decoded, true);
            }
            return is_array($decoded) ? $decoded : [];
        }

        return [];
    }

    /**
     * Get the notification title if available in payload.
     */
    public function title(): ?string
    {
        $data = $this->getData();
        return $data['title'] ?? null;
    }

    /**
     * Get the notification body message text if available.
     */
    public function message(): ?string
    {
        $data = $this->getData();
        return $data['message'] ?? $data['body'] ?? null;
    }

    /**
     * Get the target action URL link if available.
     */
    public function link(): ?string
    {
        $data = $this->getData();
        return $data['link'] ?? $data['action_url'] ?? $data['url'] ?? null;
    }

    /**
     * Alias for link().
     */
    public function url(): ?string
    {
        return $this->link();
    }

    /**
     * Get the action button label text if available.
     */
    public function actionText(): ?string
    {
        $data = $this->getData();
        return $data['action_text'] ?? $data['action'] ?? null;
    }

    /**
     * Get the notification level (info, success, warning, error).
     */
    public function level(): string
    {
        $data = $this->getData();
        return $data['level'] ?? 'info';
    }

    /**
     * Get the icon identifier if available.
     */
    public function icon(): ?string
    {
        $data = $this->getData();
        return $data['icon'] ?? null;
    }
}
