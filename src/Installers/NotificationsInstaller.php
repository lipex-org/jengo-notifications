<?php

declare(strict_types=1);

namespace Jengo\Notifications\Installers;

use CodeIgniter\CLI\CLI;
use Jengo\Base\Installers\Contracts\AbstractInstaller;

class NotificationsInstaller extends AbstractInstaller
{
    public static function name(): string
    {
        return 'notifications';
    }

    public static function description(): string
    {
        return 'Install Jengo Notifications multi-channel delivery engine, publish configuration, and run database migrations';
    }

    public static function reasonForSkipping(): string
    {
        return 'Notifications configuration is already published in app/Config/Notifications.php.';
    }

    public function shouldRun(): bool
    {
        if (CLI::getOption('force') !== null || CLI::getOption('overwrite') !== null) {
            return true;
        }

        return !file_exists(APPPATH . 'Config/Notifications.php');
    }

    public function install(): void
    {
        $this->addRun();

        CLI::newLine();
        CLI::write('  ' . str_repeat('━', 56), 'dark_gray');
        CLI::write('    JENGO NOTIFICATIONS INSTALLER', 'light_cyan');
        CLI::write('    Multi-Channel Delivery Engine (Mail, SMS, DB, Push, Slack)', 'dark_gray');
        CLI::write('  ' . str_repeat('━', 56), 'dark_gray');
        CLI::newLine();

        $force = CLI::getOption('force') !== null || CLI::getOption('overwrite') !== null;
        $dest = APPPATH . 'Config/Notifications.php';

        // 1. Environment and Prerequisite Checks
        CLI::write('1. Checking environment prerequisites...', 'cyan');
        if (!extension_loaded('curl')) {
            CLI::write('  ' . CLI::color('!', 'yellow') . ' PHP cURL extension is not loaded. cURL is recommended for SMS, Slack, WebPush/FCM, and Webhook drivers.', 'yellow');
        } else {
            CLI::write('  ' . CLI::color('✔', 'green') . ' PHP cURL extension is enabled.');
        }

        if (!extension_loaded('json')) {
            CLI::write('  ' . CLI::color('!', 'yellow') . ' PHP JSON extension is not loaded. JSON is required for notification payloads.', 'yellow');
        } else {
            CLI::write('  ' . CLI::color('✔', 'green') . ' PHP JSON extension is enabled.');
        }

        // 2. Publish Configuration File
        CLI::newLine();
        CLI::write('2. Publishing configuration...', 'cyan');
        if (file_exists($dest) && !$force) {
            CLI::write("  " . CLI::color('○', 'yellow') . " Config/Notifications.php already exists, skipping publication. (Use --force to overwrite)", 'yellow');
        } else {
            $source = dirname(__DIR__) . '/Config/Notifications.php';
            if (file_exists($source)) {
                $content = (string) file_get_contents($source);
                $content = str_replace(
                    "namespace Jengo\\Notifications\\Config;\n\nuse CodeIgniter\\Config\\BaseConfig;",
                    "namespace Config;\n\nuse Jengo\\Notifications\\Config\\Notifications as BaseNotifications;",
                    $content
                );
                $content = str_replace(
                    "class Notifications extends BaseConfig",
                    "class Notifications extends BaseNotifications",
                    $content
                );

                $targetDir = dirname($dest);
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }

                $this->writeFile($dest, $content);
                CLI::write('  ' . CLI::color('✔', 'green') . ' Published Config/Notifications.php successfully.', 'green');
            } else {
                CLI::error("  Failed to find source configuration at [{$source}].");
            }
        }

        // 3. Register notification helper in Autoload
        CLI::newLine();
        CLI::write('3. Configuring Autoload helper...', 'cyan');
        $this->addHelperToAutoload(['notification']);

        // 4. Run Database Migrations for Jengo\Notifications
        CLI::newLine();
        CLI::write('4. Running database migrations (php spark migrate -n \\Jengo\\Notifications)...', 'cyan');
        $migrationSuccess = false;
        try {
            command('migrate -n Jengo\\Notifications');
            $migrationSuccess = true;
            CLI::write('  ' . CLI::color('✔', 'green') . ' Notifications database migrations executed successfully.', 'green');
        } catch (\Throwable $e) {
            try {
                // Secondary fallback attempt via shell runner
                $this->run('php spark migrate -n "\\Jengo\\Notifications"');
                $migrationSuccess = true;
                CLI::write('  ' . CLI::color('✔', 'green') . ' Notifications database migrations executed successfully.', 'green');
            } catch (\Throwable $e2) {
                CLI::write('  ' . CLI::color('!', 'yellow') . ' Note: Automatic migration failed (' . $e2->getMessage() . '). Run `php spark migrate -n "\\Jengo\\Notifications"` once your database is connected.', 'yellow');
            }
        }

        // 5. Verify Installation Readiness
        CLI::newLine();
        CLI::write('5. Verifying notification subsystem readiness...', 'cyan');

        // Check database table
        try {
            $db = \Config\Database::connect();
            $table = 'notifications';
            if (function_exists('config')) {
                $cfg = config('Notifications');
                if (isset($cfg->table)) {
                    $table = $cfg->table;
                }
            }

            if ($db->tableExists($table)) {
                CLI::write('  ' . CLI::color('✔', 'green') . " Database table [{$table}] verified and ready for in-app alerts.");
            } else {
                CLI::write('  ' . CLI::color('●', 'yellow') . " Table [{$table}] is not yet present. Run `php spark migrate -n \"\\Jengo\\Notifications\"` when database is connected.");
            }
        } catch (\Throwable $e) {
            CLI::write('  ' . CLI::color('●', 'yellow') . ' Database table verification deferred (no active database connection).');
        }

        // Check NotificationManager
        try {
            $manager = \Jengo\Notifications\NotificationManager::getInstance();
            if ($manager !== null) {
                CLI::write('  ' . CLI::color('✔', 'green') . ' NotificationManager initialized with channels (Mail, Database, SMS, Push, Slack, Webhook, Broadcast).');
            }
        } catch (\Throwable $e) {
            CLI::write('  ' . CLI::color('!', 'yellow') . ' NotificationManager init warning: ' . $e->getMessage());
        }

        CLI::newLine();
        CLI::write('  ' . str_repeat('━', 56), 'dark_gray');
        CLI::write('    ' . CLI::color('✔ Jengo Notifications installation complete!', 'green'));
        CLI::write('    Test your setup with: ' . CLI::color('php spark notifications:test database <user_id>', 'light_cyan'));
        CLI::write('  ' . str_repeat('━', 56), 'dark_gray');
        CLI::newLine();
    }
}
