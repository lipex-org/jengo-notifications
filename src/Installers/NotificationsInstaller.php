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
        return 'Install Jengo Notifications multi-channel delivery engine, publish configuration, and scaffold database migrations';
    }

    public static function reasonForSkipping(): string
    {
        return 'Notifications configuration already published in app/Config/Notifications.php.';
    }

    public function shouldRun(): bool
    {
        return !file_exists(APPPATH . 'Config/Notifications.php');
    }

    public function install(): void
    {
        $this->addRun();

        $dest = APPPATH . 'Config/Notifications.php';
        if (file_exists($dest)) {
            CLI::write('Config/Notifications.php already exists, skipping.', 'yellow');
            return;
        }

        $source = __DIR__ . '/../Config/Notifications.php';
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

        $this->writeFile($dest, $content);
        CLI::write('Published Config/Notifications.php successfully.', 'green');

        // Trigger notifications table migration scaffolding
        CLI::newLine();
        command('notifications:table');
    }
}
