<?php

declare(strict_types=1);

namespace Jengo\Notifications\Contracts;

use Jengo\Notifications\Notification;

interface ChannelInterface
{
    /**
     * Send the given notification to the given notifiable.
     *
     * @param object $notifiable The recipient entity or anonymous notifiable.
     * @param Notification $notification The notification instance.
     * @return mixed Channel-specific delivery result or message ID.
     */
    public function send(object $notifiable, Notification $notification): mixed;
}
