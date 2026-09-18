<?php

declare(strict_types=1);

namespace Jengo\Notifications\Contracts;

use Jengo\Notifications\Messages\PushMessage;

interface PushDriverInterface
{
    /**
     * Send a push notification to one or multiple device tokens or subscription endpoints.
     *
     * @param string|array $target Single token or array of tokens/subscription descriptors.
     * @param PushMessage $message The formatted push message DTO.
     * @return bool|array True, message ID, or array of results per token.
     */
    public function send(string|array $target, PushMessage $message): bool|array;
}
