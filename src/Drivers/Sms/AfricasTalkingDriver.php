<?php

declare(strict_types=1);

namespace Jengo\Notifications\Drivers\Sms;

use Jengo\Notifications\Contracts\SmsDriverInterface;
use Jengo\Notifications\Exceptions\CouldNotSendNotificationException;
use Jengo\Notifications\Messages\SmsMessage;

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

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER     => [
                "apiKey: {$this->apiKey}",
                'Accept: application/json',
                'Content-Type: application/x-www-form-urlencoded',
            ],
            CURLOPT_POSTFIELDS     => http_build_query($data),
            CURLOPT_TIMEOUT        => 15,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw CouldNotSendNotificationException::serviceRespondedWithError('africas_talking', $error);
        }

        $result = json_decode((string) $response, true);

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

        $msg = $result['errorMessage'] ?? "HTTP error {$httpCode}: " . (string) $response;
        throw CouldNotSendNotificationException::serviceRespondedWithError('africas_talking', $msg);
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
