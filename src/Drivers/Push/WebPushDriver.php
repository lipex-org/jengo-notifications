<?php

declare(strict_types=1);

namespace Jengo\Notifications\Drivers\Push;

use Jengo\Notifications\Contracts\PushDriverInterface;
use Jengo\Notifications\Exceptions\CouldNotSendNotificationException;
use Jengo\Notifications\Messages\PushMessage;

class WebPushDriver implements PushDriverInterface
{
    public function __construct(
        protected string $publicKey = '',
        protected string $privateKey = '',
        protected string $subject = 'mailto:admin@example.com'
    ) {
        if (empty($this->publicKey) && function_exists('config')) {
            $config = config('Notifications');
            $wpConfig = $config->push['webpush'] ?? [];
            $this->publicKey  = $wpConfig['publicKey'] ?? '';
            $this->privateKey = $wpConfig['privateKey'] ?? '';
            $this->subject    = $wpConfig['subject'] ?? 'mailto:admin@example.com';
        }
    }

    public function send(string|array $target, PushMessage $message): bool|array
    {
        $subscriptions = is_array($target) && isset($target['endpoint']) ? [$target] : (is_array($target) ? $target : [['endpoint' => $target]]);

        $results = [];

        foreach ($subscriptions as $sub) {
            $endpoint = is_array($sub) ? ($sub['endpoint'] ?? '') : (string) $sub;

            if (empty($endpoint)) {
                continue;
            }

            $payload = json_encode($message->toArray()['webpush']['notification'] ?? []);

            $ch = curl_init($endpoint);
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_HTTPHEADER     => [
                    'Content-Type: application/json',
                    "TTL: {$message->ttl}",
                ],
                CURLOPT_POSTFIELDS     => $payload,
                CURLOPT_TIMEOUT        => 10,
            ]);

            $response = curl_exec($ch);
            $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $error    = curl_error($ch);
            curl_close($ch);

            if ($error) {
                $results[$endpoint] = false;
                continue;
            }

            $results[$endpoint] = ($httpCode >= 200 && $httpCode < 300);
        }

        return count($subscriptions) === 1 ? reset($results) : $results;
    }
}
