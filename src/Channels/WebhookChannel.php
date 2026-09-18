<?php

declare(strict_types=1);

namespace Jengo\Notifications\Channels;

use Jengo\Notifications\Contracts\ChannelInterface;
use Jengo\Notifications\Exceptions\CouldNotSendNotificationException;
use Jengo\Notifications\Messages\WebhookMessage;
use Jengo\Notifications\Notification;

class WebhookChannel implements ChannelInterface
{
    public function send(object $notifiable, Notification $notification): mixed
    {
        if (!method_exists($notification, 'toWebhook')) {
            return null;
        }

        $message = $notification->toWebhook($notifiable);
        if (is_array($message)) {
            $message = new WebhookMessage($message);
        }

        if (!$message instanceof WebhookMessage) {
            return null;
        }

        $url = $message->url ?? $notifiable->routeNotificationFor('webhook', $notification);
        if (empty($url)) {
            return null;
        }

        $payload = json_encode($message->data, JSON_UNESCAPED_SLASHES);
        $headers = [];

        foreach ($message->headers as $key => $val) {
            $headers[] = "{$key}: {$val}";
        }

        if (!empty($message->secret)) {
            $signature = hash_hmac('sha256', (string) $payload, $message->secret);
            $headers[] = "X-Jengo-Signature: {$signature}";
        }

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => $headers,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_TIMEOUT        => $message->timeout,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw CouldNotSendNotificationException::serviceRespondedWithError('webhook', $error);
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        }

        throw CouldNotSendNotificationException::serviceRespondedWithError('webhook', "HTTP {$httpCode}: " . (string) $response);
    }
}
