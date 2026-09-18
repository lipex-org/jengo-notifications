<?php

declare(strict_types=1);

namespace Jengo\Notifications\Drivers\Sms;

use Jengo\Notifications\Contracts\SmsDriverInterface;
use Jengo\Notifications\Messages\SmsMessage;

class NullSmsDriver implements SmsDriverInterface
{
    public function send(string $to, SmsMessage $message): bool|string
    {
        return 'null-' . bin2hex(random_bytes(8));
    }
}
