<?php

declare(strict_types=1);

namespace Tests\Unit;

use CodeIgniter\Test\CIUnitTestCase;
use Jengo\Notifications\Messages\DatabaseMessage;

final class DatabaseMessageTest extends CIUnitTestCase
{
    public function testDatabaseMessageWithLinksAndLevels(): void
    {
        $db = (new DatabaseMessage())
            ->title('Password Reset')
            ->message('A request was made to reset your password.')
            ->link('https://example.com/reset?token=xyz', 'Reset Password')
            ->level('warning')
            ->icon('shield-alert')
            ->data(['ip_address' => '192.168.1.1']);

        $array = $db->toArray();

        $this->assertSame('Password Reset', $array['title']);
        $this->assertSame('A request was made to reset your password.', $array['message']);
        $this->assertSame('https://example.com/reset?token=xyz', $array['link']);
        $this->assertSame('https://example.com/reset?token=xyz', $array['action_url']);
        $this->assertSame('Reset Password', $array['action_text']);
        $this->assertSame('warning', $array['level']);
        $this->assertSame('shield-alert', $array['icon']);
        $this->assertSame('192.168.1.1', $array['ip_address']);
    }

    public function testDatabaseMessageConstructorHydration(): void
    {
        $db = new DatabaseMessage([
            'title'       => 'Welcome',
            'message'     => 'Welcome to the platform!',
            'action_url'  => 'https://example.com/dashboard',
            'action_text' => 'Get Started',
            'level'       => 'success',
            'user_id'     => 10,
        ]);

        $this->assertSame('Welcome', $db->title);
        $this->assertSame('Welcome to the platform!', $db->message);
        $this->assertSame('https://example.com/dashboard', $db->link);
        $this->assertSame('Get Started', $db->actionText);
        $this->assertSame('success', $db->level);
        $this->assertSame(10, $db->extraData['user_id']);
    }
}
