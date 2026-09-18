<?php

declare(strict_types=1);

namespace Jengo\Notifications\Messages;

class MailMessage
{
    public ?string $subject = null;
    public ?string $greeting = null;
    public array $introLines = [];
    public ?array $action = null; // ['text' => ..., 'url' => ..., 'color' => ...]
    public array $outroLines = [];
    public ?string $salutation = null;
    public ?string $panel = null;
    public ?array $table = null; // ['headers' => [...], 'rows' => [...]]
    public ?array $from = null; // ['address' => ..., 'name' => ...]
    public ?array $replyTo = null; // ['address' => ..., 'name' => ...]
    public array $cc = [];
    public array $bcc = [];
    public array $attachments = [];
    public array $rawAttachments = [];
    public int $priority = 3; // 1 = highest, 3 = normal, 5 = lowest
    public string $theme = 'default';
    public ?string $view = null;
    public array $viewData = [];

    /**
     * Set the email subject line.
     */
    public function subject(string $subject): static
    {
        $this->subject = $subject;
        return $this;
    }

    /**
     * Set the greeting for the email.
     */
    public function greeting(string $greeting): static
    {
        $this->greeting = $greeting;
        return $this;
    }

    /**
     * Add a line of text to the email body.
     */
    public function line(string $line): static
    {
        if ($this->action === null) {
            $this->introLines[] = $line;
        } else {
            $this->outroLines[] = $line;
        }

        return $this;
    }

    /**
     * Add a prominent call-to-action button.
     */
    public function action(string $text, string $url, string $color = 'primary'): static
    {
        $this->action = [
            'text' => $text,
            'url' => $url,
            'color' => $color,
        ];

        return $this;
    }

    /**
     * Add a highlighted notice / panel box.
     */
    public function panel(string $text): static
    {
        $this->panel = $text;
        return $this;
    }

    /**
     * Add a data table to the email.
     *
     * @param array<int, string> $headers
     * @param array<int, array<int, mixed>> $rows
     */
    public function table(array $headers, array $rows): static
    {
        $this->table = [
            'headers' => $headers,
            'rows' => $rows,
        ];

        return $this;
    }

    /**
     * Set the closing salutation (e.g. 'Regards, Team').
     */
    public function salutation(string $salutation): static
    {
        $this->salutation = $salutation;
        return $this;
    }

    /**
     * Override the sender address and name.
     */
    public function from(string $address, ?string $name = null): static
    {
        $this->from = ['address' => $address, 'name' => $name];
        return $this;
    }

    /**
     * Set the reply-to address.
     */
    public function replyTo(string $address, ?string $name = null): static
    {
        $this->replyTo = ['address' => $address, 'name' => $name];
        return $this;
    }

    /**
     * Add CC recipients.
     */
    public function cc(string|array $address): static
    {
        $addresses = is_array($address) ? $address : [$address];
        $this->cc = array_merge($this->cc, $addresses);
        return $this;
    }

    /**
     * Add BCC recipients.
     */
    public function bcc(string|array $address): static
    {
        $addresses = is_array($address) ? $address : [$address];
        $this->bcc = array_merge($this->bcc, $addresses);
        return $this;
    }

    /**
     * Attach a local file to the email.
     */
    public function attach(string $file, array $options = []): static
    {
        $this->attachments[] = [
            'file' => $file,
            'options' => $options,
        ];

        return $this;
    }

    /**
     * Attach in-memory raw binary data as a file.
     */
    public function attachData(string $data, string $name, array $options = []): static
    {
        $this->rawAttachments[] = [
            'data' => $data,
            'name' => $name,
            'options' => $options,
        ];

        return $this;
    }

    /**
     * Set the priority of the message.
     */
    public function priority(int $priority): static
    {
        $this->priority = max(1, min(5, $priority));
        return $this;
    }

    /**
     * Set the template theme name (e.g. 'default', 'minimal', 'dark').
     */
    public function theme(string $theme): static
    {
        $this->theme = $theme;
        return $this;
    }

    /**
     * Render the email using a custom view instead of standard layout.
     */
    public function view(string $view, array $data = []): static
    {
        $this->view = $view;
        $this->viewData = $data;
        return $this;
    }

    /**
     * Render plain text representation.
     */
    public function toPlainText(): string
    {
        $lines = [];

        if (!empty($this->subject)) {
            $lines[] = strtoupper($this->subject);
            $lines[] = str_repeat('=', strlen($this->subject));
            $lines[] = '';
        }

        if (!empty($this->greeting)) {
            $lines[] = $this->greeting;
            $lines[] = '';
        }

        foreach ($this->introLines as $line) {
            $lines[] = $line;
        }

        if ($this->panel !== null) {
            $lines[] = '';
            $lines[] = '--- NOTICE ---';
            $lines[] = $this->panel;
            $lines[] = '--------------';
            $lines[] = '';
        }

        if ($this->table !== null) {
            $lines[] = '';
            $lines[] = implode(' | ', $this->table['headers']);
            $lines[] = str_repeat('-', 40);
            foreach ($this->table['rows'] as $row) {
                $lines[] = implode(' | ', array_map('strval', $row));
            }
            $lines[] = '';
        }

        if ($this->action !== null) {
            $lines[] = '';
            $lines[] = $this->action['text'] . ': ' . $this->action['url'];
            $lines[] = '';
        }

        foreach ($this->outroLines as $line) {
            $lines[] = $line;
        }

        if (!empty($this->salutation)) {
            $lines[] = '';
            $lines[] = $this->salutation;
        }

        return trim(implode("\n", $lines));
    }
}
