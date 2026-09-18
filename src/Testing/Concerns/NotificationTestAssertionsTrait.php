<?php

declare(strict_types=1);

namespace Jengo\Notifications\Testing\Concerns;

use Jengo\Notifications\Notification;
use Jengo\Notifications\Testing\NotificationFake;

trait NotificationTestAssertionsTrait
{
    protected ?NotificationFake $notificationFake = null;

    /**
     * Fake notification dispatching for the current test.
     */
    protected function fakeNotifications(): NotificationFake
    {
        return $this->notificationFake = Notification::fake();
    }

    /**
     * Assert that a notification was sent.
     */
    protected function assertNotificationSentTo(mixed $notifiable, string $notificationClass, ?callable $callback = null): void
    {
        ($this->notificationFake ?? Notification::fake())->assertSentTo($notifiable, $notificationClass, $callback);
    }

    /**
     * Assert that a notification was not sent.
     */
    protected function assertNotificationNotSentTo(mixed $notifiable, string $notificationClass, ?callable $callback = null): void
    {
        ($this->notificationFake ?? Notification::fake())->assertNotSentTo($notifiable, $notificationClass, $callback);
    }

    /**
     * Assert that no notifications were sent.
     */
    protected function assertNoNotificationsSent(): void
    {
        ($this->notificationFake ?? Notification::fake())->assertNothingSent();
    }
}
