<?php

declare(strict_types=1);

namespace Jengo\Notifications\Drivers\Push;

use Jengo\Notifications\Contracts\PushDriverInterface;
use Jengo\Notifications\Messages\PushMessage;

class LogPushDriver implements PushDriverInterface
{
    public function send(string|array $target, PushMessage $message): bool|array
    {
        $tokens = is_array($target) ? implode(', ', $target) : $target;
        log_message('info', "[PUSH] Target: {$tokens} | Title: {$message->title} | Body: {$message->body}");

        return true;
    }
}
