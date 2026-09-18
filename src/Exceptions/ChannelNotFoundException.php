<?php

declare(strict_types=1);

namespace Jengo\Notifications\Exceptions;

class ChannelNotFoundException extends NotificationException
{
    public static function forChannel(string $channel): self
    {
        return new self("Notification channel [{$channel}] is not supported or registered.");
    }
}
