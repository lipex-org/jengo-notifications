<?php

declare(strict_types=1);

namespace Jengo\Notifications\Testing;

use Jengo\Notifications\Notification;
use Jengo\Notifications\NotificationManager;
use PHPUnit\Framework\Assert;

class NotificationFake
{
    /**
     * In-memory log of dispatched notifications.
     *
     * @var array<int, array{notifiable: object, notification: Notification, channels: array<int, string>}>
     */
    protected array $records = [];

    public function __construct(
        protected NotificationManager $manager
    ) {
    }

    /**
     * Record a notification sent through the fake.
     */
    public function send(mixed $notifiables, Notification $notification): void
    {
        $this->sendNow($notifiables, $notification);
    }

    /**
     * Record an immediate notification sent through the fake.
     */
    public function sendNow(mixed $notifiables, Notification $notification, ?array $channels = null): void
    {
        $notifiables = is_iterable($notifiables) ? $notifiables : [$notifiables];

        foreach ($notifiables as $notifiable) {
            if (!is_object($notifiable)) {
                continue;
            }

            $targetChannels = $channels ?? $notification->via($notifiable);

            $this->records[] = [
                'notifiable'   => $notifiable,
                'notification' => $notification,
                'channels'     => $targetChannels,
            ];
        }
    }

    /**
     * Get all recorded notifications.
     */
    public function all(): array
    {
        return $this->records;
    }

    /**
     * Assert that a notification was sent to a specific notifiable.
     */
    public function assertSentTo(mixed $notifiable, string $notificationClass, ?callable $callback = null): self
    {
        $matching = $this->findNotifications($notifiable, $notificationClass, $callback);

        Assert::assertNotEmpty(
            $matching,
            "Failed asserting that notification [{$notificationClass}] was sent to the given notifiable."
        );

        return $this;
    }

    /**
     * Assert that a notification was not sent to a specific notifiable.
     */
    public function assertNotSentTo(mixed $notifiable, string $notificationClass, ?callable $callback = null): self
    {
        $matching = $this->findNotifications($notifiable, $notificationClass, $callback);

        Assert::assertEmpty(
            $matching,
            "Notification [{$notificationClass}] was unexpectedly sent to the given notifiable."
        );

        return $this;
    }

    /**
     * Assert that a notification was sent on a specific delivery channel.
     */
    public function assertSentOnChannel(string $channel, string $notificationClass): self
    {
        $matching = [];

        foreach ($this->records as $record) {
            if ($record['notification'] instanceof $notificationClass && in_array($channel, $record['channels'], true)) {
                $matching[] = $record;
            }
        }

        Assert::assertNotEmpty(
            $matching,
            "Failed asserting that notification [{$notificationClass}] was sent on channel [{$channel}]."
        );

        return $this;
    }

    /**
     * Assert that a notification was sent a specific number of times.
     */
    public function assertSentTimes(string $notificationClass, int $expectedCount): self
    {
        $actual = 0;
        foreach ($this->records as $record) {
            if ($record['notification'] instanceof $notificationClass) {
                $actual++;
            }
        }

        Assert::assertSame(
            $expectedCount,
            $actual,
            "Expected [{$notificationClass}] to be sent {$expectedCount} times, but was sent {$actual} times."
        );

        return $this;
    }

    /**
     * Assert that no notifications were sent.
     */
    public function assertNothingSent(): self
    {
        Assert::assertEmpty(
            $this->records,
            'Failed asserting that no notifications were sent. ' . count($this->records) . ' notifications were sent.'
        );

        return $this;
    }

    /**
     * Assert the total number of notifications sent.
     */
    public function assertCount(int $expectedCount): self
    {
        Assert::assertCount(
            $expectedCount,
            $this->records,
            "Expected total sent notifications to be {$expectedCount}, found " . count($this->records) . '.'
        );

        return $this;
    }

    /**
     * Find matching notifications based on notifiable, class, and optional callback.
     */
    protected function findNotifications(mixed $notifiable, string $notificationClass, ?callable $callback = null): array
    {
        $matches = [];

        foreach ($this->records as $record) {
            if (!$record['notification'] instanceof $notificationClass) {
                continue;
            }

            if (!$this->notifiableMatches($record['notifiable'], $notifiable)) {
                continue;
            }

            if ($callback !== null && !$callback($record['notification'], $record['channels'], $record['notifiable'])) {
                continue;
            }

            $matches[] = $record;
        }

        return $matches;
    }

    /**
     * Determine if a recorded notifiable matches the expected target.
     */
    protected function notifiableMatches(object $recorded, mixed $expected): bool
    {
        if ($recorded === $expected) {
            return true;
        }

        // Support matching AnonymousNotifiable by route string (e.g. 'admin@example.com')
        if (is_string($expected) && property_exists($recorded, 'routes')) {
            return in_array($expected, $recorded->routes, true);
        }

        // Support matching by primary key
        if (method_exists($recorded, 'getNotifiableKey') && method_exists($expected, 'getNotifiableKey')) {
            return $recorded->getNotifiableMorphClass() === $expected->getNotifiableMorphClass()
                && (string) $recorded->getNotifiableKey() === (string) $expected->getNotifiableKey();
        }

        return false;
    }
}
