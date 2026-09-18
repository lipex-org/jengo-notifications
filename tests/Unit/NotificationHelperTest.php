<?php

declare(strict_types=1);

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use Jengo\Notifications\Notification;
use Jengo\Notifications\NotificationManager;
use Jengo\Notifications\Support\AnonymousNotifiable;

final class NotificationHelperTest extends CIUnitTestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        helper('Jengo\Notifications\Helpers\notification');
    }

    protected function tearDown(): void
    {
        NotificationManager::getInstance()->unmock();
        parent::tearDown();
    }

    public function testNotificationHelperReturnsManager(): void
    {
        $this->assertInstanceOf(NotificationManager::class, notification());
    }

    public function testNotificationHelperWithChannelAndRouteReturnsAnonymousNotifiable(): void
    {
        $onDemand = notification('mail', 'hello@example.com');
        $this->assertInstanceOf(AnonymousNotifiable::class, $onDemand);
        $this->assertSame('hello@example.com', $onDemand->routeNotificationFor('mail'));
    }

    public function testNotifyHelperDispatchesNotification(): void
    {
        $fake = Notification::fake();

        $user = new class {
            public int $id = 1;
            public function getNotifiableKey(): int { return 1; }
            public function getNotifiableMorphClass(): string { return 'User'; }
        };

        $notification = new class extends Notification {
            public function via(object $notifiable): array { return ['mail']; }
        };

        notify($user, $notification);

        $fake->assertSentTo($user, get_class($notification));
    }
}
