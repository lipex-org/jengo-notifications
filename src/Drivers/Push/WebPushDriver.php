<?php

declare(strict_types=1);

namespace Jengo\Notifications\Drivers\Push;

use Config\Services;
use Jengo\Notifications\Contracts\PushDriverInterface;
use Jengo\Notifications\Exceptions\CouldNotSendNotificationException;
use Jengo\Notifications\Messages\PushMessage;
use Throwable;

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
        $client = Services::curlrequest([
            'timeout'     => 10.0,
            'http_errors' => false,
        ]);

        foreach ($subscriptions as $sub) {
            $endpoint = is_array($sub) ? ($sub['endpoint'] ?? '') : (string) $sub;

            if (empty($endpoint)) {
                continue;
            }

            $payload = $message->toArray()['webpush']['notification'] ?? [];

            try {
                $response = $client->post($endpoint, [
                    'headers' => [
                        'Content-Type' => 'application/json',
                        'TTL'          => (string) $message->ttl,
                    ],
                    'json' => $payload,
                ]);

                $httpCode = $response->getStatusCode();
                $results[$endpoint] = ($httpCode >= 200 && $httpCode < 300);
            } catch (Throwable) {
                $results[$endpoint] = false;
            }
        }

        return count($subscriptions) === 1 ? reset($results) : $results;
    }
}
