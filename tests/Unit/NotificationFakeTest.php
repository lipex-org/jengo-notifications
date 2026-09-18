<?php

declare(strict_types=1);

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use Jengo\Notifications\Messages\MailMessage;
use Jengo\Notifications\Notification;
use Jengo\Notifications\NotificationManager;

class DummyInvoiceNotification extends Notification
{
    public function __construct(public string $invoiceNumber)
    {
        parent::__construct();
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'sms'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())->subject("Invoice #{$this->invoiceNumber}");
    }
}

class DummySecurityNotification extends Notification
{
}

final class NotificationFakeTest extends CIUnitTestCase
{
    protected function tearDown(): void
    {
        NotificationManager::getInstance()->unmock();
        parent::tearDown();
    }

    public function testFakeRecordsAndAssertsNotifications(): void
    {
        $fake = Notification::fake();

        $user = new class {
            public int $id = 42;
            public string $email = 'ian@example.com';
            public function getNotifiableMorphClass(): string { return 'App\Entities\User'; }
            public function getNotifiableKey(): int { return 42; }
        };

        $fake->assertNothingSent();
        $fake->assertCount(0);

        Notification::send($user, new DummyInvoiceNotification('INV-2026-001'));

        $fake->assertCount(1);
        $fake->assertSentTo($user, DummyInvoiceNotification::class);
        $fake->assertSentTo($user, DummyInvoiceNotification::class, function ($n) {
            return $n->invoiceNumber === 'INV-2026-001';
        });

        $fake->assertSentOnChannel('mail', DummyInvoiceNotification::class);
        $fake->assertSentOnChannel('sms', DummyInvoiceNotification::class);
        $fake->assertSentTimes(DummyInvoiceNotification::class, 1);

        $fake->assertNotSentTo($user, DummySecurityNotification::class);
    }

    public function testFakeAssertsAnonymousNotifiable(): void
    {
        $fake = Notification::fake();

        Notification::route('mail', 'finance@example.com')
            ->notify(new DummyInvoiceNotification('INV-999'));

        $fake->assertSentTo('finance@example.com', DummyInvoiceNotification::class);
    }
}
