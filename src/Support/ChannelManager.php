<?php

declare(strict_types=1);

namespace Jengo\Notifications\Support;

use Closure;
use Jengo\Notifications\Channels\BroadcastChannel;
use Jengo\Notifications\Channels\DatabaseChannel;
use Jengo\Notifications\Channels\MailChannel;
use Jengo\Notifications\Channels\PushChannel;
use Jengo\Notifications\Channels\SlackChannel;
use Jengo\Notifications\Channels\SmsChannel;
use Jengo\Notifications\Channels\WebhookChannel;
use Jengo\Notifications\Contracts\ChannelInterface;
use Jengo\Notifications\Exceptions\ChannelNotFoundException;

class ChannelManager
{
    /**
     * Cache of resolved channel instances.
     *
     * @var array<string, ChannelInterface>
     */
    protected array $channels = [];

    /**
     * Registered custom channel creators.
     *
     * @var array<string, Closure>
     */
    protected array $customCreators = [];

    /**
     * Resolve a channel instance by name.
     */
    public function channel(string $name): ChannelInterface
    {
        return $this->channels[$name] ??= $this->resolve($name);
    }

    /**
     * Resolve a new channel instance.
     */
    protected function resolve(string $name): ChannelInterface
    {
        if (isset($this->customCreators[$name])) {
            return ($this->customCreators[$name])();
        }

        return match ($name) {
            'mail'      => new MailChannel(),
            'database'  => new DatabaseChannel(),
            'sms'       => new SmsChannel(),
            'push'      => new PushChannel(),
            'slack'     => new SlackChannel(),
            'webhook'   => new WebhookChannel(),
            'broadcast' => new BroadcastChannel(),
            default     => throw ChannelNotFoundException::forChannel($name),
        };
    }

    /**
     * Register a custom channel creator.
     */
    public function extend(string $name, Closure $callback): static
    {
        $this->customCreators[$name] = $callback;
        unset($this->channels[$name]);
        return $this;
    }

    /**
     * Swap a channel with a mock or custom instance for testing.
     */
    public function set(string $name, ChannelInterface $channel): static
    {
        $this->channels[$name] = $channel;
        return $this;
    }

    /**
     * Reset resolved channel instances.
     */
    public function reset(): void
    {
        $this->channels = [];
    }
}
