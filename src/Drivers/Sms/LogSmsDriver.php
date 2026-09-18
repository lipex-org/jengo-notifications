<?php

declare(strict_types=1);

namespace Jengo\Notifications\Drivers\Sms;

use Jengo\Notifications\Contracts\SmsDriverInterface;
use Jengo\Notifications\Messages\SmsMessage;

class LogSmsDriver implements SmsDriverInterface
{
    public function send(string $to, SmsMessage $message): bool|string
    {
        $sender = $message->senderId ?? $message->from ?? 'JENGO';
        $content = $message->getContent();

        log_message('info', "[SMS] To: {$to} | From: {$sender} | Body: {$content}");

        return 'log-' . bin2hex(random_bytes(8));
    }
}
