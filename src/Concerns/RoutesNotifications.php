<?php

declare(strict_types=1);

namespace Jengo\Notifications\Concerns;

use Jengo\Notifications\Notification;

trait RoutesNotifications
{
    /**
     * Resolve the recipient address or route for the given channel.
     */
    public function routeNotificationFor(string $channel, ?Notification $notification = null): mixed
    {
        $method = 'routeNotificationFor' . str_replace(' ', '', ucwords(str_replace(['-', '_'], ' ', $channel)));

        if (method_exists($this, $method)) {
            return $this->{$method}($notification);
        }

        return match ($channel) {
            'mail' => $this->resolveMailRoute(),
            'sms' => $this->resolveSmsRoute(),
            'push' => $this->resolvePushRoute(),
            default => null,
        };
    }

    /**
     * Default resolution for email route.
     */
    protected function resolveMailRoute(): ?string
    {
        if (isset($this->attributes['email'])) {
            return (string) $this->attributes['email'];
        }

        if (property_exists($this, 'email') && !empty($this->email)) {
            return (string) $this->email;
        }

        return null;
    }

    /**
     * Default resolution for SMS route.
     */
    protected function resolveSmsRoute(): ?string
    {
        $candidates = ['phone_number', 'phone', 'mobile', 'telephone'];

        foreach ($candidates as $key) {
            if (isset($this->attributes[$key]) && !empty($this->attributes[$key])) {
                return (string) $this->attributes[$key];
            }
            if (property_exists($this, $key) && !empty($this->{$key})) {
                return (string) $this->{$key};
            }
        }

        return null;
    }

    /**
     * Default resolution for Push notification route.
     */
    protected function resolvePushRoute(): mixed
    {
        $candidates = ['fcm_token', 'push_token', 'device_token'];

        foreach ($candidates as $key) {
            if (isset($this->attributes[$key]) && !empty($this->attributes[$key])) {
                return $this->attributes[$key];
            }
            if (property_exists($this, $key) && !empty($this->{$key})) {
                return $this->{$key};
            }
        }

        return null;
    }
}
