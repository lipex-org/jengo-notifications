<?php

declare(strict_types=1);

namespace Jengo\Notifications\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Jengo\Notifications\Messages\DatabaseMessage;
use Jengo\Notifications\Messages\MailMessage;
use Jengo\Notifications\Messages\SmsMessage;
use Jengo\Notifications\Notification;
use Throwable;

class NotificationTestCommand extends BaseCommand
{
    protected $group       = 'Notifications';
    protected $name        = 'notifications:test';
    protected $description = 'Send a test notification across a specified channel to verify credentials.';
    protected $usage       = 'notifications:test <channel> <recipient>';
    protected $arguments   = [
        'channel'   => 'Channel to test (mail, sms, database, slack, webhook).',
        'recipient' => 'Target recipient address (email, phone, webhook URL, or user ID).',
    ];

    public function run(array $params)
    {
        $channel = $params[0] ?? CLI::getSegment(2);
        $recipient = $params[1] ?? CLI::getSegment(3);

        if (empty($channel)) {
            $channel = CLI::prompt('Delivery channel (mail, sms, database, slack, webhook)', 'mail');
        }

        if (empty($recipient)) {
            $recipient = CLI::prompt('Target recipient (email, phone, URL)', null, 'required');
        }

        CLI::write("Sending test notification to [{$recipient}] via [{$channel}]...", 'cyan');

        $testNotification = new class ($channel) extends Notification {
            public function __construct(protected string $targetChannel)
            {
                parent::__construct();
            }

            public function via(object $notifiable): array
            {
                return [$this->targetChannel];
            }

            public function toMail(object $notifiable): MailMessage
            {
                return (new MailMessage())
                    ->subject('Jengo Notifications Test')
                    ->greeting('Hello!')
                    ->line('This is a test notification confirming that your email channel is operational.')
                    ->action('Visit Jengo Docs', 'https://jengo.dev')
                    ->salutation('Best regards, Jengo Framework');
            }

            public function toSms(object $notifiable): SmsMessage
            {
                return (new SmsMessage('Jengo test SMS: Your SMS channel is configured correctly.'))
                    ->link('https://jengo.dev');
            }

            public function toDatabase(object $notifiable): DatabaseMessage
            {
                return (new DatabaseMessage())
                    ->title('Jengo Notifications Test')
                    ->message('Database notification channel is operational.')
                    ->link('https://jengo.dev', 'View Docs');
            }
        };

        try {
            Notification::route($channel, $recipient)->notify($testNotification);
            CLI::write('Notification dispatched successfully.', 'green');
        } catch (Throwable $e) {
            CLI::error('Delivery failed: ' . $e->getMessage());
        }
    }
}
