<?php

declare(strict_types=1);

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use Jengo\Notifications\Concerns\Notifiable;
use Jengo\Notifications\Messages\MailMessage;
use Jengo\Notifications\Notification;
use Jengo\Notifications\NotificationManager;

class DummyUser
{
    use Notifiable;

    public array $attributes = [
        'id'           => 99,
        'email'        => 'john@example.com',
        'phone_number' => '+254712345678',
        'fcm_token'    => 'fcm-test-token-123',
    ];

    public function getNotifiableKey(): int
    {
        return $this->attributes['id'];
    }
}

class CustomRoutingUser
{
    use Notifiable;

    public function routeNotificationForMail(): string
    {
        return 'custom-mail@example.com';
    }

    public function routeNotificationForSms(): string
    {
        return '+1234567890';
    }
}

final class NotifiableTest extends CIUnitTestCase
{
    protected function tearDown(): void
    {
        NotificationManager::getInstance()->unmock();
        parent::tearDown();
    }

    public function testNotifiableResolvesDefaultRoutes(): void
    {
        $user = new DummyUser();

        $this->assertSame('john@example.com', $user->routeNotificationFor('mail'));
        $this->assertSame('+254712345678', $user->routeNotificationFor('sms'));
        $this->assertSame('fcm-test-token-123', $user->routeNotificationFor('push'));
    }

    public function testNotifiableResolvesCustomRoutes(): void
    {
        $user = new CustomRoutingUser();

        $this->assertSame('custom-mail@example.com', $user->routeNotificationFor('mail'));
        $this->assertSame('+1234567890', $user->routeNotificationFor('sms'));
    }

    public function testDirectUserNotifyMethod(): void
    {
        $fake = Notification::fake();

        $user = new DummyUser();
        $notification = new class extends Notification {
            public function via(object $notifiable): array { return ['mail']; }
        };

        $user->notify($notification);

        $fake->assertSentTo($user, get_class($notification));
    }
}
