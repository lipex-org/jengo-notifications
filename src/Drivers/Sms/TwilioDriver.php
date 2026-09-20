<?php

declare(strict_types=1);

namespace Jengo\Notifications\Drivers\Sms;

use Config\Services;
use Jengo\Notifications\Contracts\SmsDriverInterface;
use Jengo\Notifications\Exceptions\CouldNotSendNotificationException;
use Jengo\Notifications\Messages\SmsMessage;
use Throwable;

class TwilioDriver implements SmsDriverInterface
{
    public function __construct(
        protected string $accountSid = '',
        protected string $authToken = '',
        protected string $fromNumber = ''
    ) {
        if (empty($this->accountSid) && function_exists('config')) {
            $config = config('Notifications');
            $this->accountSid = $config->twilio['accountSid'] ?? '';
            $this->authToken  = $config->twilio['authToken'] ?? '';
            $this->fromNumber = $config->twilio['fromNumber'] ?? '';
        }
    }

    public function send(string $to, SmsMessage $message): bool|string
    {
        if (empty($this->accountSid) || empty($this->authToken)) {
            throw CouldNotSendNotificationException::serviceRespondedWithError(
                'twilio',
                'Account SID or Auth Token is not configured.'
            );
        }

        $from = $message->senderId ?? $message->from ?? $this->fromNumber;
        if (empty($from)) {
            throw CouldNotSendNotificationException::serviceRespondedWithError(
                'twilio',
                'From number or Alphanumeric Sender ID is required.'
            );
        }

        $url = "https://api.twilio.com/2010-04-01/Accounts/{$this->accountSid}/Messages.json";
        $data = [
            'To'   => $to,
            'From' => $from,
            'Body' => $message->getContent(),
        ];

        $client = Services::curlrequest([
            'timeout'     => 15.0,
            'http_errors' => false,
            'auth'        => [$this->accountSid, $this->authToken, 'basic'],
        ]);

        try {
            $response = $client->post($url, [
                'form_params' => $data,
            ]);

            $httpCode = $response->getStatusCode();
            $rawBody  = (string) $response->getBody();
        } catch (Throwable $e) {
            throw CouldNotSendNotificationException::serviceRespondedWithError('twilio', $e->getMessage());
        }

        $result = json_decode($rawBody, true);

        if ($httpCode >= 200 && $httpCode < 300) {
            return $result['sid'] ?? true;
        }

        $msg = $result['message'] ?? "HTTP error {$httpCode}";
        throw CouldNotSendNotificationException::serviceRespondedWithError('twilio', (string) $msg);
    }
}
