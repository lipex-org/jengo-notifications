<?php

declare(strict_types=1);

namespace Jengo\Notifications\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class MakeNotificationCommand extends BaseCommand
{
    protected $group       = 'Generators';
    protected $name        = 'make:notification';
    protected $description = 'Generate a new Jengo notification class.';
    protected $usage       = 'make:notification <name> [--queue]';
    protected $arguments   = [
        'name' => 'The notification class name.',
    ];
    protected $options     = [
        '--queue' => 'Implement the ShouldQueue contract for background delivery.',
    ];

    public function run(array $params)
    {
        $name = $params[0] ?? CLI::getSegment(2);

        if (empty($name)) {
            $name = CLI::prompt('Notification name', null, 'required');
        }

        $name = str_replace(' ', '', ucwords(str_replace(['-', '_', '/'], ' ', $name)));
        if (!str_ends_with($name, 'Notification')) {
            $name .= 'Notification';
        }

        $targetDir = APPPATH . 'Notifications';
        if (!is_dir($targetDir)) {
            mkdir($targetDir, 0755, true);
        }

        $filePath = "{$targetDir}/{$name}.php";
        if (file_exists($filePath)) {
            CLI::error("Notification [{$name}] already exists.");
            return;
        }

        $shouldQueue = array_key_exists('queue', $params) || CLI::getOption('queue');
        $queueImport = $shouldQueue ? "use Jengo\\Notifications\\Contracts\\ShouldQueue;\n" : '';
        $queueImplements = $shouldQueue ? ' implements ShouldQueue' : '';

        $stub = <<<PHP
<?php

declare(strict_types=1);

namespace App\Notifications;

use Jengo\Notifications\Messages\DatabaseMessage;
use Jengo\Notifications\Messages\MailMessage;
use Jengo\Notifications\Messages\SmsMessage;
use Jengo\Notifications\Notification;
{$queueImport}
class {$name} extends Notification{$queueImplements}
{
    public function __construct()
    {
        parent::__construct();
    }

    /**
     * Get the notification's delivery channels.
     *
     * @param object \$notifiable
     * @return array<int, string>
     */
    public function via(object \$notifiable): array
    {
        return ['mail', 'database'];
    }

    /**
     * Format the notification for email.
     */
    public function toMail(object \$notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('New Notification')
            ->greeting('Hello!')
            ->line('You have received a new notification.')
            ->action('View Details', site_url('/'))
            ->line('Thank you for using our application!');
    }

    /**
     * Format the notification for in-app database display.
     */
    public function toDatabase(object \$notifiable): DatabaseMessage
    {
        return (new DatabaseMessage())
            ->title('New Notification')
            ->message('You have received a new notification.')
            ->link(site_url('/'), 'View Details')
            ->level('info');
    }

    /**
     * Format the notification for SMS delivery.
     */
    public function toSms(object \$notifiable): SmsMessage
    {
        return (new SmsMessage())
            ->content('You have received a new notification.')
            ->link(site_url('/'));
    }
}

PHP;

        file_put_contents($filePath, $stub);
        CLI::write("Notification created: [{$filePath}]", 'green');
    }
}
