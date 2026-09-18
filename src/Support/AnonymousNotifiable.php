<?php

declare(strict_types=1);

namespace Jengo\Notifications\Support;

use Jengo\Notifications\Notification;
use Jengo\Notifications\NotificationManager;

class AnonymousNotifiable
{
    /**
     * The target recipient routes keyed by channel name.
     *
     * @var array<string, mixed>
     */
    public array $routes = [];

    /**
     * Add a target address for a specific channel.
     */
    public function route(string $channel, mixed $route): static
    {
        $this->routes[$channel] = $route;
        return $this;
    }

    /**
     * Resolve the target route for a channel.
     */
    public function routeNotificationFor(string $channel, ?Notification $notification = null): mixed
    {
        return $this->routes[$channel] ?? null;
    }

    /**
     * Dispatch the given notification to this anonymous notifiable.
     */
    public function notify(Notification $notification): void
    {
        NotificationManager::getInstance()->send($this, $notification);
    }
}
