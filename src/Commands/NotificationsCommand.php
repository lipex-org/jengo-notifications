<?php

declare(strict_types=1);

namespace Jengo\Notifications\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Jengo\Notifications\Installers\NotificationsInstaller;

class NotificationsCommand extends BaseCommand
{
    protected $group       = 'Jengo';
    protected $name        = 'jengo:notifications';
    protected $description = 'Setup, install, and manage Jengo Notifications multi-channel engine.';
    protected $usage       = 'jengo:notifications [action] [options]';

    protected $arguments = [
        'action' => 'Action to perform: "install" (default) or "status".',
    ];

    protected $options = [
        '--force'     => 'Force re-installation and configuration overwrite.',
        '--overwrite' => 'Alias for --force.',
    ];

    public function run(array $params)
    {
        $action = $params[0] ?? CLI::getSegment(2) ?? 'install';

        if ($action === 'install') {
            $installer = new NotificationsInstaller();
            $installer->install();
            return;
        }

        if ($action === 'status') {
            CLI::newLine();
            CLI::write('  Jengo Notifications Subsystem Status', 'light_cyan');
            CLI::write('  ' . str_repeat('━', 45), 'dark_gray');

            $configFile = APPPATH . 'Config/Notifications.php';
            $configPresent = file_exists($configFile);
            CLI::write(
                '  Configuration:  ' . ($configPresent ? CLI::color('Config/Notifications.php present', 'green') : CLI::color('Not published', 'yellow'))
            );

            try {
                $db = \Config\Database::connect();
                $table = function_exists('config') && isset(config('Notifications')->table) ? config('Notifications')->table : 'notifications';
                $tableExists = $db->tableExists($table);
                CLI::write(
                    "  Database Table: [{$table}] " . ($tableExists ? CLI::color('Active (verified)', 'green') : CLI::color('Missing (run installer or migrations)', 'yellow'))
                );
            } catch (\Throwable $e) {
                CLI::write('  Database Table: ' . CLI::color('Connection unavailable (' . $e->getMessage() . ')', 'yellow'));
            }

            $curlLoaded = extension_loaded('curl');
            CLI::write(
                '  cURL Extension: ' . ($curlLoaded ? CLI::color('Loaded (SMS/Push/Slack ready)', 'green') : CLI::color('Missing', 'yellow'))
            );

            CLI::newLine();
            return;
        }

        CLI::error("Unknown action [{$action}]. Use 'install' or 'status'.");
    }
}
