<?php

declare(strict_types=1);

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use Jengo\Notifications\Channels\BroadcastChannel;
use Jengo\Notifications\Channels\DatabaseChannel;
use Jengo\Notifications\Channels\MailChannel;
use Jengo\Notifications\Channels\PushChannel;
use Jengo\Notifications\Channels\SlackChannel;
use Jengo\Notifications\Channels\SmsChannel;
use Jengo\Notifications\Channels\WebhookChannel;
use Jengo\Notifications\Contracts\ChannelInterface;
use Jengo\Notifications\Drivers\Push\NullPushDriver;
use Jengo\Notifications\Drivers\Sms\NullSmsDriver;
use Jengo\Notifications\Exceptions\ChannelNotFoundException;
use Jengo\Notifications\Messages\BroadcastMessage;
use Jengo\Notifications\Messages\PushMessage;
use Jengo\Notifications\Messages\SmsMessage;
use Jengo\Notifications\Notification;
use Jengo\Notifications\Support\ChannelManager;

final class ChannelsTest extends CIUnitTestCase
{
    public function testChannelManagerResolvesBuiltInChannels(): void
    {
        $manager = new ChannelManager();

        $this->assertInstanceOf(MailChannel::class, $manager->channel('mail'));
        $this->assertInstanceOf(DatabaseChannel::class, $manager->channel('database'));
        $this->assertInstanceOf(SmsChannel::class, $manager->channel('sms'));
        $this->assertInstanceOf(PushChannel::class, $manager->channel('push'));
        $this->assertInstanceOf(SlackChannel::class, $manager->channel('slack'));
        $this->assertInstanceOf(WebhookChannel::class, $manager->channel('webhook'));
        $this->assertInstanceOf(BroadcastChannel::class, $manager->channel('broadcast'));
    }

    public function testChannelManagerThrowsOnUnknownChannel(): void
    {
        $this->expectException(ChannelNotFoundException::class);

        $manager = new ChannelManager();
        $manager->channel('unsupported-telepathy-channel');
    }

    public function testChannelManagerCustomDriverExtension(): void
    {
        $manager = new ChannelManager();

        $customChannel = new class implements ChannelInterface {
            public function send(object $notifiable, Notification $notification): mixed
            {
                return 'custom-sent';
            }
        };

        $manager->extend('telegram', fn () => $customChannel);

        $this->assertSame($customChannel, $manager->channel('telegram'));
    }

    public function testSmsChannelWithNullDriver(): void
    {
        $channel = new SmsChannel(new NullSmsDriver());

        $user = new class {
            public string $phone_number = '+254700000000';
            public function routeNotificationForSms(): string { return $this->phone_number; }
        };

        $notification = new class extends Notification {
            public function toSms(object $notifiable): SmsMessage
            {
                return new SmsMessage('Hello SMS');
            }
        };

        $result = $channel->send($user, $notification);
        $this->assertIsString($result);
        $this->assertStringStartsWith('null-', $result);
    }

    public function testPushChannelWithNullDriver(): void
    {
        $channel = new PushChannel(new NullPushDriver());

        $user = new class {
            public function routeNotificationForPush(): string { return 'token-abc'; }
        };

        $notification = new class extends Notification {
            public function toPush(object $notifiable): PushMessage
            {
                return new PushMessage('Title', 'Body');
            }
        };

        $this->assertTrue($channel->send($user, $notification));
    }

    public function testBroadcastChannelGracefullySucceeds(): void
    {
        $channel = new BroadcastChannel();

        $user = new class {
            public int $id = 55;
            public function getNotifiableKey(): int { return 55; }
            public function getNotifiableMorphClass(): string { return 'App\Entities\User'; }
        };

        $notification = new class extends Notification {
            public function toBroadcast(object $notifiable): BroadcastMessage
            {
                return (new BroadcastMessage(['foo' => 'bar']))->event('test.event');
            }
        };

        $this->assertTrue($channel->send($user, $notification));
    }
}
