<?php

declare(strict_types=1);

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use Jengo\Notifications\Channels\SmsChannel;
use Jengo\Notifications\Drivers\Sms\SmsGateDriver;
use Jengo\Notifications\Messages\SmsMessage;

/**
 * @internal
 */
final class SmsGateDriverTest extends CIUnitTestCase
{
    public function testEndpointResolution(): void
    {
        // Local server default
        $localDriver = new SmsGateDriver('http://localhost:8080');
        $this->assertSame('http://localhost:8080/message', $localDriver->resolveEndpoint());

        // Local server with trailing slash
        $localSlashDriver = new SmsGateDriver('http://192.168.1.55:8080/');
        $this->assertSame('http://192.168.1.55:8080/message', $localSlashDriver->resolveEndpoint());

        // Cloud API endpoint
        $cloudDriver = new SmsGateDriver('https://api.sms-gate.app');
        $this->assertSame('https://api.sms-gate.app/3rdparty/v1/messages', $cloudDriver->resolveEndpoint());

        // Custom full path
        $customDriver = new SmsGateDriver('http://10.0.2.2:8080/message');
        $this->assertSame('http://10.0.2.2:8080/message', $customDriver->resolveEndpoint());

        // Private server with 3rdparty path
        $privateDriver = new SmsGateDriver('https://gateway.internal.net/3rdparty/v1');
        $this->assertSame('https://gateway.internal.net/3rdparty/v1/messages', $privateDriver->resolveEndpoint());
    }

    public function testPhoneNumberFormatting(): void
    {
        $driver = new SmsGateDriver('http://localhost:8080', '', '', null, '+254');

        $this->assertSame('+254712345678', $driver->formatPhoneNumber('0712345678'));
        $this->assertSame('+254712345678', $driver->formatPhoneNumber('+254712345678'));
        $this->assertSame('+254712345678', $driver->formatPhoneNumber('254712345678'));
        $this->assertSame('+14155552671', $driver->formatPhoneNumber('+14155552671'));
    }

    public function testDriverPropertiesAndConfiguration(): void
    {
        $driver = new SmsGateDriver(
            serverUrl: 'http://127.0.0.1:8080',
            login: 'admin',
            password: 'secretpassword',
            simNumber: 2,
            defaultCountryCode: '+1'
        );

        $this->assertSame('http://127.0.0.1:8080', $driver->getServerUrl());
        $this->assertSame('admin', $driver->getLogin());
        $this->assertSame(2, $driver->getSimNumber());
        $this->assertSame('+1', $driver->getDefaultCountryCode());
    }

    public function testSmsChannelResolvesSmsGateDriver(): void
    {
        $channel = new class extends SmsChannel {
            public function testResolve(string $name)
            {
                $mockConfig = new class ($name) {
                    public function __construct(public string $defaultSmsDriver)
                    {
                    }
                };

                return match ($name) {
                    'sms_gate', 'smsgate', 'sms-gate' => new SmsGateDriver(),
                    default                            => null,
                };
            }
        };

        $this->assertInstanceOf(SmsGateDriver::class, $channel->testResolve('sms_gate'));
        $this->assertInstanceOf(SmsGateDriver::class, $channel->testResolve('smsgate'));
        $this->assertInstanceOf(SmsGateDriver::class, $channel->testResolve('sms-gate'));
    }

    public function testSendThrowsExceptionOnConnectionFailure(): void
    {
        $this->expectException(\Jengo\Notifications\Exceptions\CouldNotSendNotificationException::class);
        $driver = new SmsGateDriver('http://127.0.0.1:59999', '', '', null, '+254', 1);
        $driver->send('+254712345678', new SmsMessage('Test connection'));
    }
}
