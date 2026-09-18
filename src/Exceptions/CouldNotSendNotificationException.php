<?php

declare(strict_types=1);

namespace Jengo\Notifications\Exceptions;

class CouldNotSendNotificationException extends NotificationException
{
    public static function serviceRespondedWithError(string $channel, string $error): self
    {
        return new self("Could not send notification via [{$channel}]: {$error}");
    }
}
