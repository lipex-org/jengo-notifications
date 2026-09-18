<?php

declare(strict_types=1);

namespace Jengo\Notifications\Messages;

class DatabaseMessage
{
    public ?string $title = null;
    public ?string $message = null;
    public ?string $link = null;
    public ?string $actionText = null;
    public string $level = 'info'; // 'info', 'success', 'warning', 'error'
    public ?string $icon = null;
    public array $extraData = [];

    public function __construct(array $data = [])
    {
        if (isset($data['title'])) {
            $this->title = (string) $data['title'];
            unset($data['title']);
        }
        if (isset($data['message'])) {
            $this->message = (string) $data['message'];
            unset($data['message']);
        }
        if (isset($data['link']) || isset($data['action_url']) || isset($data['url'])) {
            $this->link = (string) ($data['link'] ?? $data['action_url'] ?? $data['url']);
            unset($data['link'], $data['action_url'], $data['url']);
        }
        if (isset($data['action_text']) || isset($data['action'])) {
            $this->actionText = (string) ($data['action_text'] ?? $data['action']);
            unset($data['action_text'], $data['action']);
        }
        if (isset($data['level'])) {
            $this->level = (string) $data['level'];
            unset($data['level']);
        }
        if (isset($data['icon'])) {
            $this->icon = (string) $data['icon'];
            unset($data['icon']);
        }

        $this->extraData = $data;
    }

    /**
     * Set the notification title / subject.
     */
    public function title(string $title): static
    {
        $this->title = $title;
        return $this;
    }

    /**
     * Set the body / message text.
     */
    public function message(string $message): static
    {
        $this->message = $message;
        return $this;
    }

    /**
     * Attach an actionable URL link to the notification.
     */
    public function link(string $url, ?string $text = null): static
    {
        $this->link = $url;
        if ($text !== null) {
            $this->actionText = $text;
        }
        return $this;
    }

    /**
     * Alias for link with custom call-to-action text.
     */
    public function action(string $text, string $url): static
    {
        $this->actionText = $text;
        $this->link = $url;
        return $this;
    }

    /**
     * Set severity / category level ('info', 'success', 'warning', 'error').
     */
    public function level(string $level): static
    {
        $this->level = $level;
        return $this;
    }

    /**
     * Set an icon identifier (e.g. 'bell', 'check-circle', 'shield').
     */
    public function icon(string $icon): static
    {
        $this->icon = $icon;
        return $this;
    }

    /**
     * Append additional custom data fields.
     */
    public function data(array $data): static
    {
        $this->extraData = array_merge($this->extraData, $data);
        return $this;
    }

    /**
     * Compile payload to array for database storage.
     */
    public function toArray(): array
    {
        return array_merge($this->extraData, [
            'title'       => $this->title,
            'message'     => $this->message,
            'link'        => $this->link,
            'action_url'  => $this->link,
            'action_text' => $this->actionText,
            'level'       => $this->level,
            'icon'        => $this->icon,
        ]);
    }
}
