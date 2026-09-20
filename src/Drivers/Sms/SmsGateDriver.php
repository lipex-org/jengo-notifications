<?php

declare(strict_types=1);

namespace Jengo\Notifications\Drivers\Sms;

use Config\Services;
use Jengo\Notifications\Contracts\SmsDriverInterface;
use Jengo\Notifications\Exceptions\CouldNotSendNotificationException;
use Jengo\Notifications\Messages\SmsMessage;
use Throwable;

/**
 * Driver for SMS Gateway for Android™ (SMSGate).
 *
 * Supports:
 * - Local Server Mode: Direct HTTP API running on an Android device or Android Emulator
 *   (e.g., http://localhost:8080/message or http://10.0.2.2:8080/message).
 * - Cloud / Private Server Mode: Relayed via SMSGate Cloud or self-hosted gateway instance
 *   (e.g., https://api.sms-gate.app/3rdparty/v1/messages).
 *
 * @see https://docs.sms-gate.app
 */
class SmsGateDriver implements SmsDriverInterface
{
    public function __construct(
        protected string $serverUrl = '',
        protected string $login = '',
        protected string $password = '',
        protected ?int $simNumber = null,
        protected string $defaultCountryCode = '+254',
        protected int $timeout = 15
    ) {
        if (empty($this->serverUrl) && function_exists('config')) {
            $config = config('Notifications');
            $gateConfig = $config->smsGate ?? [];

            $this->serverUrl          = $gateConfig['serverUrl'] ?? 'http://localhost:8080';
            $this->login              = $gateConfig['login'] ?? '';
            $this->password           = $gateConfig['password'] ?? '';
            $this->simNumber          = isset($gateConfig['simNumber']) ? (int) $gateConfig['simNumber'] : null;
            $this->defaultCountryCode = $gateConfig['defaultCountryCode'] ?? '+254';
            $this->timeout            = (int) ($gateConfig['timeout'] ?? 15);
        }

        if (empty($this->serverUrl)) {
            $this->serverUrl = 'http://localhost:8080';
        }
    }

    /**
     * Send an SMS message via the SMSGate REST API.
     */
    public function send(string $to, SmsMessage $message): bool|string
    {
        $endpoint = $this->resolveEndpoint();
        $formattedTo = $this->formatPhoneNumber($to);

        $payload = [
            'textMessage'  => [
                'text' => $message->getContent(),
            ],
            'phoneNumbers' => [$formattedTo],
        ];

        $sim = $message->metadata['simNumber'] ?? $this->simNumber;
        if ($sim !== null) {
            $payload['simNumber'] = (int) $sim;
        }

        $clientOptions = [
            'timeout'     => (float) $this->timeout,
            'http_errors' => false,
        ];

        if (!empty($this->login) || !empty($this->password)) {
            $clientOptions['auth'] = [$this->login, $this->password, 'basic'];
        }

        $client = Services::curlrequest($clientOptions);

        try {
            $response = $client->post($endpoint, [
                'headers' => [
                    'Content-Type' => 'application/json',
                    'Accept'       => 'application/json',
                ],
                'json' => $payload,
            ]);

            $httpCode = $response->getStatusCode();
            $rawBody  = (string) $response->getBody();
        } catch (Throwable $e) {
            throw CouldNotSendNotificationException::serviceRespondedWithError('sms_gate', $e->getMessage());
        }

        $result = json_decode($rawBody, true);

        if ($httpCode >= 200 && $httpCode < 300) {
            if (is_array($result) && isset($result['id'])) {
                return (string) $result['id'];
            }

            return true;
        }

        $msg = "HTTP error {$httpCode}";
        if (is_array($result)) {
            $msg = $result['message'] ?? $result['error'] ?? $result['description'] ?? $msg;
        } elseif (!empty($rawBody)) {
            $msg .= ": " . trim($rawBody);
        }

        throw CouldNotSendNotificationException::serviceRespondedWithError('sms_gate', (string) $msg);
    }

    /**
     * Resolve the target REST API endpoint URL.
     */
    public function resolveEndpoint(): string
    {
        $url = rtrim($this->serverUrl, '/');
        $path = parse_url($url, PHP_URL_PATH) ?? '';

        // If specific path already provided in config, use directly
        if (str_ends_with($path, '/message') || str_ends_with($path, '/messages')) {
            return $url;
        }

        // Cloud / Private Server mode
        if (str_contains($url, '3rdparty/v1')) {
            return $url . '/messages';
        }

        if (str_contains($url, 'api.sms-gate.app')) {
            return $url . '/3rdparty/v1/messages';
        }

        // Local Server mode (Android device or emulator on local network)
        return $url . '/message';
    }

    /**
     * Normalize local phone number to international E.164 format.
     */
    public function formatPhoneNumber(string $phone): string
    {
        $cleaned = preg_replace('/[^\d+]/', '', $phone);

        if (empty($cleaned)) {
            return $phone;
        }

        if (str_starts_with($cleaned, '+')) {
            return $cleaned;
        }

        if (str_starts_with($cleaned, '0')) {
            $countryCode = ltrim($this->defaultCountryCode, '+');
            return '+' . $countryCode . substr($cleaned, 1);
        }

        return '+' . $cleaned;
    }

    public function getServerUrl(): string
    {
        return $this->serverUrl;
    }

    public function getLogin(): string
    {
        return $this->login;
    }

    public function getSimNumber(): ?int
    {
        return $this->simNumber;
    }

    public function getDefaultCountryCode(): string
    {
        return $this->defaultCountryCode;
    }
}
