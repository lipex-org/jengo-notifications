<?php

declare(strict_types=1);

namespace Jengo\Notifications;

use Jengo\Notifications\Contracts\ChannelInterface;
use Jengo\Notifications\Contracts\ShouldQueue;
use Jengo\Notifications\Events\NotificationFailed;
use Jengo\Notifications\Events\NotificationSending;
use Jengo\Notifications\Events\NotificationSent;
use Jengo\Notifications\Jobs\SendQueuedNotification;
use Jengo\Notifications\Support\ChannelManager;
use Jengo\Notifications\Testing\NotificationFake;
use Throwable;

class NotificationManager
{
    protected static ?self $instance = null;
    protected ChannelManager $channelManager;
    protected ?NotificationFake $fake = null;

    public function __construct(?ChannelManager $channelManager = null)
    {
        $this->channelManager = $channelManager ?? new ChannelManager();
    }

    public static function getInstance(): self
    {
        if (static::$instance !== null) {
            return static::$instance;
        }

        if (function_exists('service')) {
            try {
                $service = service('notifications');
                if ($service instanceof self) {
                    return static::$instance = $service;
                }
            } catch (Throwable) {
                // Service not registered yet, fallback to new instance
            }
        }

        return static::$instance = new self();
    }

    public static function setInstance(?self $instance): void
    {
        static::$instance = $instance;
    }

    public function getChannelManager(): ChannelManager
    {
        return $this->channelManager;
    }

    public function channel(string $name): ChannelInterface
    {
        return $this->channelManager->channel($name);
    }

    /**
     * Swap the manager with a NotificationFake for testing.
     */
    public function fake(): NotificationFake
    {
        return $this->fake = new NotificationFake($this);
    }

    /**
     * Reset the active fake.
     */
    public function unmock(): void
    {
        $this->fake = null;
    }

    /**
     * Determine if currently running under a fake.
     */
    public function isFake(): bool
    {
        return $this->fake !== null;
    }

    /**
     * Send the given notification to the given notifiables.
     */
    public function send(mixed $notifiables, Notification $notification): void
    {
        if ($this->fake !== null) {
            $this->fake->send($notifiables, $notification);
            return;
        }

        if ($notification instanceof ShouldQueue) {
            $this->queueNotification($notifiables, $notification);
            return;
        }

        $this->sendNow($notifiables, $notification);
    }

    /**
     * Send the given notification immediately, bypassing queueing.
     */
    public function sendNow(mixed $notifiables, Notification $notification, ?array $channels = null): void
    {
        if ($this->fake !== null) {
            $this->fake->sendNow($notifiables, $notification, $channels);
            return;
        }

        $notifiables = is_iterable($notifiables) ? $notifiables : [$notifiables];

        foreach ($notifiables as $notifiable) {
            if (!is_object($notifiable)) {
                continue;
            }

            $targetChannels = $channels ?? $notification->via($notifiable);

            foreach ($targetChannels as $channel) {
                if (!$notification->shouldSend($notifiable, $channel)) {
                    continue;
                }

                $this->sendToNotifiableOnChannel($notifiable, $notification, $channel);
            }
        }
    }

    /**
     * Dispatch notification to a single notifiable on a specific channel.
     */
    protected function sendToNotifiableOnChannel(object $notifiable, Notification $notification, string $channel): mixed
    {
        if (function_exists('events')) {
            events()->trigger('notifications.sending', new NotificationSending($notifiable, $notification, $channel));
        }

        try {
            $channelInstance = $this->channelManager->channel($channel);
            $response = $channelInstance->send($notifiable, $notification);

            if (function_exists('events')) {
                events()->trigger('notifications.sent', new NotificationSent($notifiable, $notification, $channel, $response));
            }

            return $response;
        } catch (Throwable $e) {
            if (function_exists('events')) {
                events()->trigger('notifications.failed', new NotificationFailed($notifiable, $notification, $channel, $e));
            }

            log_message('error', "[Notifications] Failed delivery on channel [{$channel}]: " . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Queue the notification for background dispatching.
     */
    protected function queueNotification(mixed $notifiables, Notification $notification): void
    {
        // Support codeigniter4/queue if installed
        if (function_exists('service')) {
            try {
                $queue = service('queue');
                if (is_object($queue) && method_exists($queue, 'push')) {
                    $queueName = $notification->queue ?? 'default';
                    $data = [
                        'notification' => serialize($notification),
                        'notifiables'  => serialize($notifiables),
                    ];
                    $queue->push($queueName, SendQueuedNotification::class, $data);
                    return;
                }
            } catch (Throwable) {
                // Queue service not active, fall through to synchronous execution
            }
        }

        // Fallback: synchronous delivery when no queue driver is configured
        $this->sendNow($notifiables, $notification);
    }
}
