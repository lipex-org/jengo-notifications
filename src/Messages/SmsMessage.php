<?php

declare(strict_types=1);

namespace Jengo\Notifications\Messages;

class SmsMessage
{
    public string $content = '';
    public ?string $link = null;
    public ?string $linkLabel = null;
    public ?string $senderId = null;
    public ?string $from = null;
    public ?string $to = null;
    public array $metadata = [];

    public function __construct(string $content = '')
    {
        $this->content = $content;
    }

    /**
     * Set the primary SMS text content.
     */
    public function content(string $content): static
    {
        $this->content = $content;
        return $this;
    }

    /**
     * Attach a URL link to the SMS message.
     * Appends to the message body cleanly (e.g. "View details: https://...").
     */
    public function link(string $url, ?string $label = null): static
    {
        $this->link = $url;
        $this->linkLabel = $label;
        return $this;
    }

    /**
     * Set alphanumeric sender ID (e.g. 'JENGO', 'MYAPP').
     */
    public function sender(string $senderId): static
    {
        $this->senderId = $senderId;
        return $this;
    }

    /**
     * Set the originating phone number or sender ID.
     */
    public function from(string $from): static
    {
        $this->from = $from;
        return $this;
    }

    /**
     * Override recipient phone number.
     */
    public function to(string $to): static
    {
        $this->to = $to;
        return $this;
    }

    /**
     * Attach client metadata.
     */
    public function metadata(array $metadata): static
    {
        $this->metadata = $metadata;
        return $this;
    }

    /**
     * Compile final message text including any embedded links.
     */
    public function getContent(): string
    {
        $text = trim($this->content);

        if (!empty($this->link)) {
            $prefix = !empty($this->linkLabel) ? ' ' . trim($this->linkLabel) . ': ' : ' ';
            $text = rtrim($text) . $prefix . trim($this->link);
        }

        return $text;
    }

    public function __toString(): string
    {
        return $this->getContent();
    }
}
