<?php

declare(strict_types=1);

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use Jengo\Notifications\Drivers\Push\LogPushDriver;
use Jengo\Notifications\Drivers\Push\NullPushDriver;
use Jengo\Notifications\Drivers\Sms\AfricasTalkingDriver;
use Jengo\Notifications\Drivers\Sms\LogSmsDriver;
use Jengo\Notifications\Drivers\Sms\NullSmsDriver;
use Jengo\Notifications\Drivers\Sms\TwilioDriver;
use Jengo\Notifications\Exceptions\CouldNotSendNotificationException;
use Jengo\Notifications\Messages\PushMessage;
use Jengo\Notifications\Messages\SmsMessage;

final class DriversTest extends CIUnitTestCase
{
    public function testLogAndNullSmsDrivers(): void
    {
        $logDriver = new LogSmsDriver();
        $res1 = $logDriver->send('+254712345678', new SmsMessage('Test log SMS'));
        $this->assertIsString($res1);
        $this->assertStringStartsWith('log-', $res1);

        $nullDriver = new NullSmsDriver();
        $res2 = $nullDriver->send('+254712345678', new SmsMessage('Test null SMS'));
        $this->assertIsString($res2);
        $this->assertStringStartsWith('null-', $res2);
    }

    public function testLogAndNullPushDrivers(): void
    {
        $logPush = new LogPushDriver();
        $this->assertTrue($logPush->send('device-token-123', new PushMessage('Title', 'Body')));

        $nullPush = new NullPushDriver();
        $this->assertTrue($nullPush->send(['token-1', 'token-2'], new PushMessage('Title', 'Body')));
    }

    public function testTwilioDriverThrowsWhenUnconfigured(): void
    {
        $this->expectException(CouldNotSendNotificationException::class);
        $driver = new TwilioDriver('', '', '');
        $driver->send('+254712345678', new SmsMessage('Hello'));
    }

    public function testAfricasTalkingDriverThrowsWhenUnconfigured(): void
    {
        $this->expectException(CouldNotSendNotificationException::class);
        $driver = new AfricasTalkingDriver('sandbox', '', '');
        $driver->send('0712345678', new SmsMessage('Hello'));
    }

    public function testAfricasTalkingPhoneNormalization(): void
    {
        $driver = new class ('sandbox', 'key123', 'JENGO', '+254') extends AfricasTalkingDriver {
            public function normalize(string $phone): string
            {
                return $this->formatPhoneNumber($phone);
            }
        };

        $this->assertSame('+254712345678', $driver->normalize('0712345678'));
        $this->assertSame('+254712345678', $driver->normalize('+254712345678'));
        $this->assertSame('+254712345678', $driver->normalize('254712345678'));
    }
}
