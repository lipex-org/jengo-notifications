<?php

declare(strict_types=1);

namespace Jengo\Notifications\Drivers\Sms;

use Config\Services;
use Jengo\Notifications\Contracts\SmsDriverInterface;
use Jengo\Notifications\Exceptions\CouldNotSendNotificationException;
use Jengo\Notifications\Messages\SmsMessage;
use Throwable;

class AfricasTalkingDriver implements SmsDriverInterface
{
    public function __construct(
        protected string $username = 'sandbox',
        protected string $apiKey = '',
        protected string $senderId = '',
        protected string $defaultCountryCode = '+254'
    ) {
        if (empty($this->apiKey) && function_exists('config')) {
            $config = config('Notifications');
            $atConfig = $config->africasTalking ?? [];
            $this->username           = $atConfig['username'] ?? 'sandbox';
            $this->apiKey             = $atConfig['apiKey'] ?? '';
            $this->senderId           = $atConfig['senderId'] ?? '';
            $this->defaultCountryCode = $atConfig['defaultCountryCode'] ?? '+254';
        }
    }

    public function send(string $to, SmsMessage $message): bool|string
    {
        if (empty($this->apiKey)) {
            throw CouldNotSendNotificationException::serviceRespondedWithError(
                'africas_talking',
                'API key is not configured.'
            );
        }

        $formattedTo = $this->formatPhoneNumber($to);
        $from = $message->senderId ?? (!empty($this->senderId) ? $this->senderId : null);

        $isSandbox = strtolower($this->username) === 'sandbox';
        $url = $isSandbox
            ? 'https://api.sandbox.africastalking.com/version1/messaging'
            : 'https://api.africastalking.com/version1/messaging';

        $data = [
            'username' => $this->username,
            'to'       => $formattedTo,
            'message'  => $message->getContent(),
        ];

        if (!empty($from) && !$isSandbox) {
            $data['from'] = $from;
        }

        $client = Services::curlrequest([
            'timeout'     => 15.0,
            'http_errors' => false,
        ]);

        try {
            $response = $client->post($url, [
                'headers' => [
                    'apiKey' => $this->apiKey,
                    'Accept' => 'application/json',
                ],
                'form_params' => $data,
            ]);

            $httpCode = $response->getStatusCode();
            $rawBody  = (string) $response->getBody();
        } catch (Throwable $e) {
            throw CouldNotSendNotificationException::serviceRespondedWithError('africas_talking', $e->getMessage());
        }

        $result = json_decode($rawBody, true);

        if ($httpCode >= 200 && $httpCode < 300 && isset($result['SMSMessageData'])) {
            $recipients = $result['SMSMessageData']['Recipients'] ?? [];
            if (!empty($recipients)) {
                $status = $recipients[0]['status'] ?? '';
                if ($status === 'Success') {
                    return $recipients[0]['messageId'] ?? true;
                }

                throw CouldNotSendNotificationException::serviceRespondedWithError(
                    'africas_talking',
                    "Delivery failed with status [{$status}]"
                );
            }

            return true;
        }

        $msg = $result['errorMessage'] ?? "HTTP error {$httpCode}: " . $rawBody;
        throw CouldNotSendNotificationException::serviceRespondedWithError('africas_talking', (string) $msg);
    }

    /**
     * Normalize local phone number to international E.164 format.
     */
    protected function formatPhoneNumber(string $phone): string
    {
        $cleaned = preg_replace('/[^\d+]/', '', $phone);

        if (str_starts_with($cleaned, '+')) {
            return $cleaned;
        }

        if (str_starts_with($cleaned, '0')) {
            $countryCode = ltrim($this->defaultCountryCode, '+');
            return '+' . $countryCode . substr($cleaned, 1);
        }

        if (!str_starts_with($cleaned, '+')) {
            return '+' . $cleaned;
        }

        return $cleaned;
    }
}
