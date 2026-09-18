<?php

declare(strict_types=1);

namespace Jengo\Notifications\Messages;

class SlackMessage
{
    public string $text = '';
    public array $blocks = [];
    public ?string $channel = null;
    public ?string $username = null;
    public ?string $iconUrl = null;

    public function __construct(string $text = '')
    {
        $this->text = $text;
    }

    public function text(string $text): static
    {
        $this->text = $text;
        return $this;
    }

    public function header(string $text): static
    {
        $this->blocks[] = [
            'type' => 'header',
            'text' => [
                'type'  => 'plain_text',
                'text'  => $text,
                'emoji' => true,
            ],
        ];

        return $this;
    }

    public function section(string $markdown): static
    {
        $this->blocks[] = [
            'type' => 'section',
            'text' => [
                'type' => 'mrkdwn',
                'text' => $markdown,
            ],
        ];

        return $this;
    }

    public function fields(array $fields): static
    {
        $formatted = [];
        foreach ($fields as $key => $val) {
            $formatted[] = [
                'type' => 'mrkdwn',
                'text' => is_string($key) ? "*{$key}:*\n{$val}" : (string) $val,
            ];
        }

        $this->blocks[] = [
            'type'   => 'section',
            'fields' => $formatted,
        ];

        return $this;
    }

    public function button(string $text, string $url, string $style = 'primary'): static
    {
        $this->blocks[] = [
            'type'     => 'actions',
            'elements' => [
                [
                    'type'  => 'button',
                    'text'  => [
                        'type' => 'plain_text',
                        'text' => $text,
                    ],
                    'url'   => $url,
                    'style' => $style,
                ],
            ],
        ];

        return $this;
    }

    public function divider(): static
    {
        $this->blocks[] = ['type' => 'divider'];
        return $this;
    }

    public function channel(string $channel): static
    {
        $this->channel = $channel;
        return $this;
    }

    public function username(string $username): static
    {
        $this->username = $username;
        return $this;
    }

    public function iconUrl(string $url): static
    {
        $this->iconUrl = $url;
        return $this;
    }

    public function toArray(): array
    {
        $payload = ['text' => $this->text];

        if (!empty($this->blocks)) {
            $payload['blocks'] = $this->blocks;
        }
        if (!empty($this->channel)) {
            $payload['channel'] = $this->channel;
        }
        if (!empty($this->username)) {
            $payload['username'] = $this->username;
        }
        if (!empty($this->iconUrl)) {
            $payload['icon_url'] = $this->iconUrl;
        }

        return $payload;
    }
}
