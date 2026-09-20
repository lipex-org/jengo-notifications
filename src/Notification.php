<?php

declare(strict_types=1);

namespace Jengo\Notifications;

use Jengo\Notifications\Support\AnonymousNotifiable;
use Jengo\Notifications\Testing\NotificationFake;

abstract class Notification
{
    /**
     * Unique identifier for this notification instance.
     */
    public string $id;

    /**
     * The name of the queue the job should be sent to.
     */
    public ?string $queue = null;

    /**
     * The number of seconds to wait before sending the queued notification.
     */
    public ?int $delay = null;

    /**
     * The number of times the notification job may be attempted.
     */
    public int $tries = 3;

    /**
     * The number of seconds the notification job can run before timing out.
     */
    public int $timeout = 60;

    /**
     * The backoff duration in seconds between retry attempts.
     *
     * @var array<int, int>|int
     */
    public array|int $backoff = [5, 15, 60];

    public function __construct()
    {
        $this->id = $this->generateId();
    }

    /**
     * Generate a unique identifier for the notification.
     */
    protected function generateId(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param object $notifiable
     * @return array<int, string>
     */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    /**
     * Determine if the notification should be sent on the given channel.
     */
    public function shouldSend(object $notifiable, string $channel): bool
    {
        return true;
    }

    /**
     * Send the given notification to the given notifiables.
     */
    public static function send(mixed $notifiables, Notification $notification): void
    {
        NotificationManager::getInstance()->send($notifiables, $notification);
    }

    /**
     * Send the given notification immediately, bypassing any queueing.
     */
    public static function sendNow(mixed $notifiables, Notification $notification, ?array $channels = null): void
    {
        NotificationManager::getInstance()->sendNow($notifiables, $notification, $channels);
    }

    /**
     * Begin configuring an on-demand notification with a target channel and route.
     */
    public static function route(string $channel, mixed $route): AnonymousNotifiable
    {
        return (new AnonymousNotifiable())->route($channel, $route);
    }

    /**
     * Swap the notification manager with an in-memory test double.
     */
    public static function fake(): NotificationFake
    {
        return NotificationManager::getInstance()->fake();
    }

    /**
     * Restore the notification manager to its original unmocked state.
     */
    public static function restore(): void
    {
        NotificationManager::getInstance()->unmock();
    }

    /**
     * Dynamically proxy static calls to the active fake when in test mode.
     */
    public static function __callStatic(string $method, array $arguments): mixed
    {
        $manager = NotificationManager::getInstance();
        if ($manager->isFake()) {
            return $manager->fake()->{$method}(...$arguments);
        }

        throw new \BadMethodCallException("Static method Notification::{$method}() does not exist.");
    }
}
