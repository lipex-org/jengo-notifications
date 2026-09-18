<?php

declare(strict_types=1);

namespace Jengo\Notifications\Drivers\Push;

use Jengo\Notifications\Contracts\PushDriverInterface;
use Jengo\Notifications\Messages\PushMessage;

class NullPushDriver implements PushDriverInterface
{
    public function send(string|array $target, PushMessage $message): bool|array
    {
        return true;
    }
}
