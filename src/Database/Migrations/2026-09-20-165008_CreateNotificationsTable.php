<?php

declare(strict_types=1);

namespace Jengo\Notifications\Database\Migrations;

use CodeIgniter\Database\Migration;

class CreateNotificationsTable extends Migration
{
    public function up(): void
    {
        $tableName = 'notifications';
        if (function_exists('config')) {
            $config = config('Notifications');
            $tableName = $config->table ?? 'notifications';
        }

        $this->forge->addField([
            'id' => [
                'type'       => 'VARCHAR',
                'constraint' => 36,
            ],
            'type' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'notifiable_type' => [
                'type'       => 'VARCHAR',
                'constraint' => 255,
            ],
            'notifiable_id' => [
                'type'       => 'VARCHAR',
                'constraint' => 64,
            ],
            'data' => [
                'type' => 'TEXT',
            ],
            'read_at' => [
                'type' => 'DATETIME',
                'null' => true,
            ],
            'created_at' => [
                'type' => 'DATETIME',
            ],
            'updated_at' => [
                'type' => 'DATETIME',
            ],
        ]);

        $this->forge->addKey('id', true);
        $this->forge->addKey(['notifiable_type', 'notifiable_id', 'read_at'], false, false, 'notifiable_unread_idx');
        $this->forge->addKey('created_at', false, false, 'created_at_idx');

        $this->forge->createTable($tableName, true);
    }

    public function down(): void
    {
        $tableName = 'notifications';
        if (function_exists('config')) {
            $config = config('Notifications');
            $tableName = $config->table ?? 'notifications';
        }

        $this->forge->dropTable($tableName, true);
    }
}
