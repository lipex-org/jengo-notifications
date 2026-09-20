<?php

declare(strict_types=1);

namespace Jengo\Notifications\Messages;

class PushMessage
{
    public string $title = '';
    public string $body = '';
    public ?string $icon = null;
    public ?string $badge = null;
    public ?string $image = null;
    public ?string $clickAction = null;
    public array $data = [];
    public ?string $sound = 'default';
    public int $ttl = 86400; // 24 hours

    public function __construct(string $title = '', string $body = '')
    {
        $this->title = $title;
        $this->body = $body;
    }

    public function title(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    public function body(string $body): static
    {
        $this->body = $body;
        return $this;
    }

    public function icon(string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    public function badge(string $badge): static
    {
        $this->badge = $badge;
        return $this;
    }

    public function image(string $image): static
    {
        $this->image = $image;
        return $this;
    }

    public function clickAction(string $url): static
    {
        $this->clickAction = $url;
        return $this;
    }

    public function link(string $url): static
    {
        return $this->clickAction($url);
    }

    public function data(array $data): static
    {
        $this->data = array_merge($this->data, $data);
        return $this;
    }

    public function sound(string $sound): static
    {
        $this->sound = $sound;
        return $this;
    }

    public function ttl(int $seconds): static
    {
        $this->ttl = $seconds;
        return $this;
    }

    public function toArray(): array
    {
        return [
            'notification' => [
                'title' => $this->title,
                'body'  => $this->body,
                'image' => $this->image,
                'icon'  => $this->icon,
            ],
            'data' => array_merge($this->data, [
                'click_action' => $this->clickAction,
            ]),
            'webpush' => [
                'notification' => [
                    'title' => $this->title,
                    'body'  => $this->body,
                    'badge' => $this->badge,
                    'icon'  => $this->icon,
                    'image' => $this->image,
                    'data'  => ['url' => $this->clickAction],
                ],
            ],
        ];
    }
}
