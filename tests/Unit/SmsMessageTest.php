<?php

declare(strict_types=1);

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use Jengo\Notifications\Messages\SmsMessage;

final class SmsMessageTest extends CIUnitTestCase
{
    public function testSmsContentWithoutLink(): void
    {
        $sms = (new SmsMessage('Your verification code is 123456.'))
            ->sender('JENGO');

        $this->assertSame('Your verification code is 123456.', $sms->getContent());
        $this->assertSame('JENGO', $sms->senderId);
        $this->assertSame('Your verification code is 123456.', (string) $sms);
    }

    public function testSmsContentWithEmbeddedLink(): void
    {
        $sms = (new SmsMessage('Your invoice is ready.'))
            ->link('https://example.com/i/abc1234', 'Pay now')
            ->sender('STORE')
            ->to('+254712345678');

        $expected = 'Your invoice is ready. Pay now: https://example.com/i/abc1234';
        $this->assertSame($expected, $sms->getContent());
        $this->assertSame('+254712345678', $sms->to);
        $this->assertSame('STORE', $sms->senderId);
    }

    public function testSmsContentWithLinkWithoutLabel(): void
    {
        $sms = (new SmsMessage('Check out your order:'))
            ->link('https://example.com/orders/42');

        $expected = 'Check out your order: https://example.com/orders/42';
        $this->assertSame($expected, $sms->getContent());
    }
}
