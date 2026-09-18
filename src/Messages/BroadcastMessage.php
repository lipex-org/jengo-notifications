<?php

declare(strict_types=1);

namespace Jengo\Notifications\Messages;

class BroadcastMessage
{
    public array $data = [];
    public ?string $event = 'notification.received';
    public ?string $channel = null;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function data(array $data): static
    {
        $this->data = array_merge($this->data, $data);
        return $this;
    }

    public function event(string $event): static
    {
        $this->event = $event;
        return $this;
    }

    public function channel(string $channel): static
    {
        $this->channel = $channel;
        return $this;
    }

    public function toArray(): array
    {
        return $this->data;
    }
}
