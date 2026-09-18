<?php

declare(strict_types=1);

namespace Jengo\Notifications\Messages;

class WebhookMessage
{
    public ?string $url = null;
    public array $data = [];
    public array $headers = [
        'Content-Type' => 'application/json',
        'User-Agent'   => 'Jengo-Notifications-Webhook/1.0',
    ];
    public ?string $secret = null;
    public int $timeout = 10;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function url(string $url): static
    {
        $this->url = $url;
        return $this;
    }

    public function data(array $data): static
    {
        $this->data = array_merge($this->data, $data);
        return $this;
    }

    public function header(string $name, string $value): static
    {
        $this->headers[$name] = $value;
        return $this;
    }

    public function headers(array $headers): static
    {
        $this->headers = array_merge($this->headers, $headers);
        return $this;
    }

    public function secret(string $secret): static
    {
        $this->secret = $secret;
        return $this;
    }

    public function timeout(int $seconds): static
    {
        $this->timeout = $seconds;
        return $this;
    }
}
