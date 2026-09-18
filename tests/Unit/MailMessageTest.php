<?php

declare(strict_types=1);

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use Jengo\Notifications\Messages\MailMessage;

final class MailMessageTest extends CIUnitTestCase
{
    public function testFluentBuildingAndPlainTextConversion(): void
    {
        $mail = (new MailMessage())
            ->subject('Order Confirmation')
            ->greeting('Hello Ian!')
            ->line('Your order #1001 has been confirmed.')
            ->panel('Total Due: KES 4,500.00')
            ->table(
                ['Item', 'Qty', 'Price'],
                [
                    ['Widget A', 2, '2,000.00'],
                    ['Widget B', 1, '2,500.00'],
                ]
            )
            ->action('View Order', 'https://example.com/orders/1001', 'primary')
            ->line('Delivery is expected within 48 hours.')
            ->salutation('Warm regards, The Store Team')
            ->theme('minimal')
            ->priority(1)
            ->from('orders@example.com', 'Order Desk')
            ->replyTo('support@example.com')
            ->cc('manager@example.com')
            ->bcc('archive@example.com');

        $this->assertSame('Order Confirmation', $mail->subject);
        $this->assertSame('Hello Ian!', $mail->greeting);
        $this->assertSame(['Your order #1001 has been confirmed.'], $mail->introLines);
        $this->assertSame('Total Due: KES 4,500.00', $mail->panel);
        $this->assertNotNull($mail->table);
        $this->assertSame(['View Order', 'https://example.com/orders/1001', 'primary'], [
            $mail->action['text'],
            $mail->action['url'],
            $mail->action['color'],
        ]);
        $this->assertSame(['Delivery is expected within 48 hours.'], $mail->outroLines);
        $this->assertSame('Warm regards, The Store Team', $mail->salutation);
        $this->assertSame('minimal', $mail->theme);
        $this->assertSame(1, $mail->priority);
        $this->assertSame('orders@example.com', $mail->from['address']);
        $this->assertSame('support@example.com', $mail->replyTo['address']);
        $this->assertContains('manager@example.com', $mail->cc);
        $this->assertContains('archive@example.com', $mail->bcc);

        $plain = $mail->toPlainText();
        $this->assertStringContainsString('ORDER CONFIRMATION', $plain);
        $this->assertStringContainsString('Hello Ian!', $plain);
        $this->assertStringContainsString('Total Due: KES 4,500.00', $plain);
        $this->assertStringContainsString('View Order: https://example.com/orders/1001', $plain);
        $this->assertStringContainsString('Warm regards, The Store Team', $plain);
    }
}
