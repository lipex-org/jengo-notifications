<?php

declare(strict_types=1);

namespace Jengo\Notifications\Channels;

use Jengo\Notifications\Contracts\ChannelInterface;
use Jengo\Notifications\Exceptions\CouldNotSendNotificationException;
use Jengo\Notifications\Messages\SlackMessage;
use Jengo\Notifications\Notification;

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

        $payload = json_encode($message->toArray(), JSON_UNESCAPED_SLASHES);

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json'],
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_TIMEOUT        => 10,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw CouldNotSendNotificationException::serviceRespondedWithError('slack', $error);
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        }

        throw CouldNotSendNotificationException::serviceRespondedWithError('slack', "HTTP {$httpCode}: " . (string) $response);
    }
}
