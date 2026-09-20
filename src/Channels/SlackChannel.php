<?php

declare(strict_types=1);

namespace Jengo\Notifications\Channels;

use Config\Services;
use Jengo\Notifications\Contracts\ChannelInterface;
use Jengo\Notifications\Exceptions\CouldNotSendNotificationException;
use Jengo\Notifications\Messages\SlackMessage;
use Jengo\Notifications\Notification;
use Throwable;

class SlackChannel implements ChannelInterface
{
    public function send(object $notifiable, Notification $notification): mixed
    {
        if (!method_exists($notification, 'toSlack')) {
            return null;
        }

        $message = $notification->toSlack($notifiable);
        if (is_string($message)) {
            $message = new SlackMessage($message);
        }

        if (!$message instanceof SlackMessage) {
            return null;
        }

        $url = $notifiable->routeNotificationFor('slack', $notification);
        if (empty($url) && function_exists('config')) {
            $config = config('Notifications');
            $url = $config->slack['webhookUrl'] ?? null;
        }

        if (empty($url)) {
            return null;
        }

        $client = Services::curlrequest([
            'timeout'     => 10.0,
            'http_errors' => false,
        ]);

        try {
            $response = $client->post($url, [
                'json' => $message->toArray(),
            ]);

            $httpCode = $response->getStatusCode();
            $rawBody  = (string) $response->getBody();
        } catch (Throwable $e) {
            throw CouldNotSendNotificationException::serviceRespondedWithError('slack', $e->getMessage());
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        }

        throw CouldNotSendNotificationException::serviceRespondedWithError('slack', "HTTP {$httpCode}: " . $rawBody);
    }
}
