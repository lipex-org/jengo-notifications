<?php

declare(strict_types=1);

namespace Jengo\Notifications\Contracts;

use Jengo\Notifications\Messages\SmsMessage;

interface SmsDriverInterface
{
    /**
     * Send an SMS message to a recipient phone number.
     *
     * @param string $to Recipient phone number (normalized to E.164 where applicable).
     * @param SmsMessage $message The formatted SMS message DTO.
     * @return bool|string True or provider message identifier on success; throws or returns false on failure.
     */
    public function send(string $to, SmsMessage $message): bool|string;
}
