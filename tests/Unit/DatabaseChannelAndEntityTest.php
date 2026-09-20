<?php

declare(strict_types=1);

namespace Tests\Unit;

use CodeIgniter\I18n\Time;
use CodeIgniter\Test\CIUnitTestCase;
use Jengo\Notifications\Entities\DatabaseNotification;

final class DatabaseChannelAndEntityTest extends CIUnitTestCase
{
    public function testDatabaseNotificationAccessorsAndStatus(): void
    {
        $notification = new DatabaseNotification([
            'id'              => 'notif-12345',
            'type'            => 'App\Notifications\OrderShipped',
            'notifiable_type' => 'App\Entities\User',
            'notifiable_id'   => '42',
            'data'            => json_encode([
                'title'       => 'Order Dispatched',
                'message'     => 'Your package is on the way!',
                'action_url'  => 'https://example.com/track/123',
                'action_text' => 'Track Package',
                'level'       => 'success',
                'icon'        => 'truck',
            ]),
            'read_at'         => null,
            'created_at'      => Time::now()->toDateTimeString(),
            'updated_at'      => Time::now()->toDateTimeString(),
        ]);

        $this->assertTrue($notification->unread());
        $this->assertFalse($notification->read());
        $this->assertSame('Order Dispatched', $notification->title());
        $this->assertSame('Your package is on the way!', $notification->message());
        $this->assertSame('https://example.com/track/123', $notification->link());
        $this->assertSame('https://example.com/track/123', $notification->url());
        $this->assertSame('Track Package', $notification->actionText());
        $this->assertSame('success', $notification->level());
        $this->assertSame('truck', $notification->icon());

        // When read_at is set
        $notification->read_at = Time::now()->toDateTimeString();
        $this->assertTrue($notification->read());
        $this->assertFalse($notification->unread());
    }

    public function testCreateNotificationsTableMigrationClass(): void
    {
        require_once dirname(__DIR__, 2) . '/src/Database/Migrations/2026-09-20-165008_CreateNotificationsTable.php';
        $migration = new \Jengo\Notifications\Database\Migrations\CreateNotificationsTable();
        $this->assertInstanceOf(\CodeIgniter\Database\Migration::class, $migration);
    }
}
