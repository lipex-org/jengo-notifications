<?php

declare(strict_types=1);

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use Jengo\Notifications\Contracts\ShouldQueue;
use Jengo\Notifications\Jobs\SendQueuedNotification;
use Jengo\Notifications\Messages\MailMessage;
use Jengo\Notifications\Notification;
use Jengo\Notifications\NotificationManager;

class QueuedTestNotification extends Notification implements ShouldQueue
{
    public function __construct(public string $msg)
    {
        parent::__construct();
        $this->queue = 'emails';
        $this->tries = 5;
    }

    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())->subject($this->msg);
    }
}

class DummyQueuedUser
{
    public int $id = 7;
    public function getNotifiableKey(): int { return 7; }
    public function getNotifiableMorphClass(): string { return 'App\Entities\User'; }
}

final class QueuedNotificationTest extends CIUnitTestCase
{
    protected function tearDown(): void
    {
        NotificationManager::getInstance()->unmock();
        parent::tearDown();
    }

    public function testQueuedNotificationHasQueueProperties(): void
    {
        $notification = new QueuedTestNotification('Queued Alert');

        $this->assertInstanceOf(ShouldQueue::class, $notification);
        $this->assertSame('emails', $notification->queue);
        $this->assertSame(5, $notification->tries);
    }

    public function testSendQueuedNotificationJobProcessesPayload(): void
    {
        $fake = Notification::fake();

        $user = new DummyQueuedUser();

        $notification = new QueuedTestNotification('Async Task');

        $job = new SendQueuedNotification([
            'notification' => serialize($notification),
            'notifiables'  => serialize([$user]),
        ]);

        $this->assertTrue($job->process());
        $fake->assertSentTo($user, QueuedTestNotification::class);
    }
}
