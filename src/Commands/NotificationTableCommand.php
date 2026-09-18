<?php

declare(strict_types=1);

namespace Jengo\Notifications\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class NotificationTableCommand extends BaseCommand
{
    protected $group       = 'Notifications';
    protected $name        = 'notifications:table';
    protected $description = 'Create a migration file for the notifications database table.';
    protected $usage       = 'notifications:table [--table=notifications]';
    protected $options     = [
        '--table' => 'Custom table name (defaults to "notifications").',
    ];

    public function run(array $params)
    {
        $tableName = $params['table'] ?? CLI::getOption('table') ?? 'notifications';

        $migrationDir = APPPATH . 'Database/Migrations';
        if (!is_dir($migrationDir)) {
            mkdir($migrationDir, 0755, true);
        }

        $timestamp = date('Y-m-d-His');
        $fileName = "{$timestamp}_Create" . str_replace(' ', '', ucwords(str_replace(['_', '-'], ' ', $tableName))) . "Table.php";
        $filePath = "{$migrationDir}/{$fileName}";

        $stub = <<<PHP
<?php

declare(strict_types=1);

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

class Create{$tableName}Table extends Migration
{
    public function up(): void
    {
        \$this->forge->addField([
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

        \$this->forge->addKey('id', true);
        \$this->forge->addKey(['notifiable_type', 'notifiable_id', 'read_at'], false, false, 'notifiable_unread_idx');
        \$this->forge->addKey('created_at', false, false, 'created_at_idx');

        \$this->forge->createTable('{$tableName}', true);
    }

    public function down(): void
    {
        \$this->forge->dropTable('{$tableName}', true);
    }
}

PHP;

        file_put_contents($filePath, $stub);
        CLI::write("Created migration: [{$filePath}]", 'green');
        CLI::write("Run 'php spark migrate' to create the table.", 'cyan');
    }
}
