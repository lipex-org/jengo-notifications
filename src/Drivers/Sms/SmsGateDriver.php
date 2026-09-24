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
 *   (e.g., http://localhost:8080 or http://10.0.2.2:8080). Endpoint: /messages.
 * - Cloud / Private Server Mode: Relayed via SMSGate Cloud or self-hosted gateway instance
 *   (e.g., https://api.sms-gate.app/3rdparty/v1). Endpoint: /messages.
 *
 * Authentication:
 * - Basic Auth: provide 'login' and 'password'.
 * - Bearer / Server-Key token: provide 'token' (takes precedence over Basic Auth).
 *
 * @see https://docs.sms-gate.app
 * @see https://sms-gate.app/api/
 */
class SmsGateDriver implements SmsDriverInterface
{
    public function __construct(
        protected string $serverUrl = '',
        protected string $login = '',
        protected string $password = '',
        protected string $token = '',
        protected ?int $simNumber = null,
        protected ?string $deviceId = null,
        protected ?int $priority = null,
        protected ?bool $withDeliveryReport = null,
        protected bool $skipPhoneValidation = false,
        protected string $defaultCountryCode = '+254',
        protected int $timeout = 15
    ) {
        if (empty($this->serverUrl) && function_exists('config')) {
            $config     = config('Notifications');
            $gateConfig = $config->smsGate ?? [];

            $this->serverUrl           = $gateConfig['serverUrl'] ?? 'http://localhost:8080';
            $this->login               = $gateConfig['login'] ?? '';
            $this->password            = $gateConfig['password'] ?? '';
            $this->token               = $gateConfig['token'] ?? '';
            $this->simNumber           = isset($gateConfig['simNumber']) ? (int) $gateConfig['simNumber'] : null;
            $this->deviceId            = $gateConfig['deviceId'] ?? null;
            $this->priority            = isset($gateConfig['priority']) ? (int) $gateConfig['priority'] : null;
            $this->withDeliveryReport  = isset($gateConfig['withDeliveryReport']) ? (bool) $gateConfig['withDeliveryReport'] : null;
            $this->skipPhoneValidation = (bool) ($gateConfig['skipPhoneValidation'] ?? false);
            $this->defaultCountryCode  = $gateConfig['defaultCountryCode'] ?? '+254';
            $this->timeout             = (int) ($gateConfig['timeout'] ?? 15);
        }

        if (empty($this->serverUrl)) {
            $this->serverUrl = 'http://localhost:8080';
        }
    }

    /**
     * Send an SMS message via the SMSGate REST API.
     *
     * Returns the remote message ID string on success, or true if no ID was returned.
     *
     * @throws CouldNotSendNotificationException
     */
    public function send(string $to, SmsMessage $message): bool|string
    {
        $endpoint    = $this->resolveEndpoint();
        $formattedTo = $this->formatPhoneNumber($to);

        $payload = [
            'phoneNumbers' => [$formattedTo],
            'textMessage'  => [
                'text' => $message->getContent(),
            ],
        ];

        // SIM slot (1, 2, or 3)
        $sim = $message->metadata['simNumber'] ?? $this->simNumber;
        if ($sim !== null) {
            $payload['simNumber'] = (int) $sim;
        }

        // Explicit device selection (max 21 chars)
        $deviceId = $message->metadata['deviceId'] ?? $this->deviceId;
        if (!empty($deviceId)) {
            $payload['deviceId'] = (string) $deviceId;
        }

        // Priority (-128, 0, 100, 127; values > 99 bypass rate limits)
        $priority = $message->metadata['priority'] ?? $this->priority;
        if ($priority !== null) {
            $payload['priority'] = (int) $priority;
        }

        // Delivery report flag
        $withDeliveryReport = $message->metadata['withDeliveryReport'] ?? $this->withDeliveryReport;
        if ($withDeliveryReport !== null) {
            $payload['withDeliveryReport'] = (bool) $withDeliveryReport;
        }

        // Optional scheduling / TTL fields from message metadata
        if (!empty($message->metadata['scheduleAt'])) {
            $payload['scheduleAt'] = (string) $message->metadata['scheduleAt'];
        }

        if (!empty($message->metadata['ttl'])) {
            $payload['ttl'] = (int) $message->metadata['ttl'];
        }

        if (!empty($message->metadata['validUntil'])) {
            $payload['validUntil'] = (string) $message->metadata['validUntil'];
        }

        // Append query parameters to the endpoint URL
        $queryEndpoint = $endpoint;
        if ($this->skipPhoneValidation) {
            $queryEndpoint .= (str_contains($queryEndpoint, '?') ? '&' : '?') . 'skipPhoneValidation=true';
        }

        $clientOptions  = ['timeout' => (float) $this->timeout, 'http_errors' => false];
        $requestHeaders = [
            'Content-Type' => 'application/json',
            'Accept'       => 'application/json',
        ];

        // Token (Bearer / Server-Key) takes precedence over Basic Auth
        if (!empty($this->token)) {
            $requestHeaders['Authorization'] = 'Bearer ' . $this->token;
        } elseif (!empty($this->login) || !empty($this->password)) {
            $clientOptions['auth'] = [$this->login, $this->password, 'basic'];
        }

        $client = Services::curlrequest($clientOptions);

        try {
            $response = $client->post($queryEndpoint, [
                'headers' => $requestHeaders,
                'json'    => $payload,
            ]);

            $httpCode = $response->getStatusCode();
            $rawBody  = (string) $response->getBody();
        } catch (Throwable $e) {
            throw CouldNotSendNotificationException::serviceRespondedWithError('sms_gate', $e->getMessage());
        }

        $result = json_decode($rawBody, true);

        // SMSGate returns 202 Accepted on successful enqueue; 200/201 also treated as success
        if ($httpCode >= 200 && $httpCode < 300) {
            if (is_array($result) && isset($result['id'])) {
                return (string) $result['id'];
            }

            return true;
        }

        $errorMsg = "HTTP {$httpCode}";
        if (is_array($result)) {
            $errorMsg = $result['message'] ?? $result['error'] ?? $result['description'] ?? $errorMsg;
            if (isset($result['code'])) {
                $errorMsg .= ' (code: ' . $result['code'] . ')';
            }
        } elseif (!empty($rawBody)) {
            $errorMsg .= ': ' . trim($rawBody);
        }

        throw CouldNotSendNotificationException::serviceRespondedWithError('sms_gate', (string) $errorMsg);
    }

    /**
     * Retrieve the current state of a previously enqueued message.
     *
     * @return array The decoded response body from GET /messages/{id}.
     *
     * @throws CouldNotSendNotificationException
     */
    public function getMessageStatus(string $messageId): array
    {
        $url    = rtrim($this->resolveBaseApiUrl(), '/') . '/messages/' . rawurlencode($messageId);
        $client = Services::curlrequest(['timeout' => (float) $this->timeout, 'http_errors' => false]);

        try {
            $response = $client->get($url, ['headers' => $this->buildAuthHeaders()]);
            $httpCode = $response->getStatusCode();
            $result   = json_decode((string) $response->getBody(), true) ?? [];
        } catch (Throwable $e) {
            throw CouldNotSendNotificationException::serviceRespondedWithError('sms_gate', $e->getMessage());
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            return $result;
        }

        $msg = is_array($result) ? ($result['message'] ?? "HTTP {$httpCode}") : "HTTP {$httpCode}";
        throw CouldNotSendNotificationException::serviceRespondedWithError('sms_gate', (string) $msg);
    }

    /**
     * Cancel a previously enqueued message.
     *
     * @throws CouldNotSendNotificationException
     */
    public function cancelMessage(string $messageId): bool
    {
        $url    = rtrim($this->resolveBaseApiUrl(), '/') . '/messages/' . rawurlencode($messageId);
        $client = Services::curlrequest(['timeout' => (float) $this->timeout, 'http_errors' => false]);

        try {
            $response = $client->delete($url, ['headers' => $this->buildAuthHeaders()]);
            $httpCode = $response->getStatusCode();
        } catch (Throwable $e) {
            throw CouldNotSendNotificationException::serviceRespondedWithError('sms_gate', $e->getMessage());
        }

        return $httpCode >= 200 && $httpCode < 300;
    }

    /**
     * List all registered devices associated with the account.
     *
     * @return array The decoded array of device objects from GET /devices.
     *
     * @throws CouldNotSendNotificationException
     */
    public function listDevices(): array
    {
        $url    = rtrim($this->resolveBaseApiUrl(), '/') . '/devices';
        $client = Services::curlrequest(['timeout' => (float) $this->timeout, 'http_errors' => false]);

        try {
            $response = $client->get($url, ['headers' => $this->buildAuthHeaders()]);
            $httpCode = $response->getStatusCode();
            $result   = json_decode((string) $response->getBody(), true) ?? [];
        } catch (Throwable $e) {
            throw CouldNotSendNotificationException::serviceRespondedWithError('sms_gate', $e->getMessage());
        }

        if ($httpCode >= 200 && $httpCode < 300) {
            return $result;
        }

        $msg = (is_array($result) ? ($result['message'] ?? '') : '') ?: "HTTP {$httpCode}";
        throw CouldNotSendNotificationException::serviceRespondedWithError('sms_gate', (string) $msg);
    }

    /**
     * Check whether the SMSGate server is ready to accept requests.
     *
     * Uses GET /health/ready; returns false on any error or non-2xx response.
     */
    public function checkHealth(): bool
    {
        $url    = rtrim($this->resolveBaseApiUrl(), '/') . '/health/ready';
        $client = Services::curlrequest(['timeout' => (float) $this->timeout, 'http_errors' => false]);

        try {
            $response = $client->get($url, ['headers' => $this->buildAuthHeaders()]);

            return $response->getStatusCode() >= 200 && $response->getStatusCode() < 300;
        } catch (Throwable) {
            return false;
        }
    }

    /**
     * Resolve the full messages endpoint URL for POST requests.
     *
     * All SMSGate endpoints (local and cloud) use /messages (plural).
     */
    public function resolveEndpoint(): string
    {
        return rtrim($this->resolveBaseApiUrl(), '/') . '/messages';
    }

    /**
     * Resolve the base API URL (without the /messages segment).
     *
     * - If the configured URL already ends with /messages, strip it so the
     *   base is returned cleanly (avoids double-appending).
     * - Cloud API: https://api.sms-gate.app -> https://api.sms-gate.app/3rdparty/v1
     * - Private server with 3rdparty path: pass through as-is.
     * - Local / emulator: use the URL directly (e.g., http://localhost:8080).
     */
    public function resolveBaseApiUrl(): string
    {
        $url  = rtrim($this->serverUrl, '/');
        $path = parse_url($url, PHP_URL_PATH) ?? '';

        // Strip trailing /messages so helpers can re-append sub-paths safely
        if (str_ends_with($path, '/messages')) {
            return substr($url, 0, -strlen('/messages'));
        }

        // Cloud root domain without version path
        if (str_contains($url, 'api.sms-gate.app') && !str_contains($url, '3rdparty/v1')) {
            return $url . '/3rdparty/v1';
        }

        return $url;
    }

    /**
     * Normalize a local phone number to international E.164 format.
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

    // Accessors

    public function getServerUrl(): string
    {
        return $this->serverUrl;
    }

    public function getLogin(): string
    {
        return $this->login;
    }

    public function getToken(): string
    {
        return $this->token;
    }

    public function getSimNumber(): ?int
    {
        return $this->simNumber;
    }

    public function getDeviceId(): ?string
    {
        return $this->deviceId;
    }

    public function getPriority(): ?int
    {
        return $this->priority;
    }

    public function getDefaultCountryCode(): string
    {
        return $this->defaultCountryCode;
    }

    /**
     * Build the Authorization header array for management API calls.
     *
     * @return array<string, string>
     */
    protected function buildAuthHeaders(): array
    {
        $headers = ['Accept' => 'application/json'];

        if (!empty($this->token)) {
            $headers['Authorization'] = 'Bearer ' . $this->token;
        }

        return $headers;
    }
}
