<?php

declare(strict_types=1);

namespace Jengo\Notifications\Drivers\Sms;

use Jengo\Notifications\Contracts\SmsDriverInterface;
use Jengo\Notifications\Exceptions\CouldNotSendNotificationException;
use Jengo\Notifications\Messages\SmsMessage;

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

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_USERPWD        => "{$this->accountSid}:{$this->authToken}",
            CURLOPT_POSTFIELDS     => http_build_query($data),
            CURLOPT_TIMEOUT        => 15,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $error    = curl_error($ch);
        curl_close($ch);

        if ($error) {
            throw CouldNotSendNotificationException::serviceRespondedWithError('twilio', $error);
        }

        $result = json_decode((string) $response, true);

        if ($httpCode >= 200 && $httpCode < 300) {
            return $result['sid'] ?? true;
        }

        $msg = $result['message'] ?? "HTTP error {$httpCode}";
        throw CouldNotSendNotificationException::serviceRespondedWithError('twilio', $msg);
    }
}
