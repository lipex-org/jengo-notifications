<?php

declare(strict_types=1);

namespace Jengo\Notifications\Channels;

use Config\Services;
use Jengo\Notifications\Contracts\ChannelInterface;
use Jengo\Notifications\Exceptions\CouldNotSendNotificationException;
use Jengo\Notifications\Messages\WebhookMessage;
use Jengo\Notifications\Notification;
use Throwable;

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
            if (is_int($key)) {
                $parts = explode(':', (string) $val, 2);
                if (count($parts) === 2) {
                    $headers[trim($parts[0])] = trim($parts[1]);
                }
            } else {
                $headers[$key] = (string) $val;
            }
        }

        if (!isset($headers['Content-Type'])) {
            $headers['Content-Type'] = 'application/json';
        }

        if (!empty($message->secret)) {
            $signature = hash_hmac('sha256', (string) $payload, $message->secret);
            $headers['X-Jengo-Signature'] = $signature;
        }

        $client = Services::curlrequest([
            'timeout'     => (float) $message->timeout,
            'http_errors' => false,
        ]);

        try {
            $response = $client->post($url, [
                'headers' => $headers,
                'body'    => $payload,
            ]);

            $httpCode = $response->getStatusCode();
            $rawBody  = (string) $response->getBody();
        } catch (Throwable $e) {
            throw CouldNotSendNotificationException::serviceRespondedWithError('webhook', $e->getMessage());
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            return true;
        }

        throw CouldNotSendNotificationException::serviceRespondedWithError('webhook', "HTTP {$httpCode}: " . $rawBody);
    }
}
