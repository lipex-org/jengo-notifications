<?php

declare(strict_types=1);

namespace Jengo\Notifications\Channels;

use Jengo\Notifications\Contracts\ChannelInterface;
use Jengo\Notifications\Contracts\SmsDriverInterface;
use Jengo\Notifications\Drivers\Sms\AfricasTalkingDriver;
use Jengo\Notifications\Drivers\Sms\LogSmsDriver;
use Jengo\Notifications\Drivers\Sms\NullSmsDriver;
use Jengo\Notifications\Drivers\Sms\SmsGateDriver;
use Jengo\Notifications\Drivers\Sms\TwilioDriver;
use Jengo\Notifications\Exceptions\CouldNotSendNotificationException;
use Jengo\Notifications\Messages\SmsMessage;
use Jengo\Notifications\Notification;

class SmsChannel implements ChannelInterface
{
    protected ?SmsDriverInterface $driver = null;

    public function __construct(?SmsDriverInterface $driver = null)
    {
        $this->driver = $driver;
    }

    public function send(object $notifiable, Notification $notification): mixed
    {
        if (!method_exists($notification, 'toSms')) {
            return null;
        }

        $message = $notification->toSms($notifiable);
        if (is_string($message)) {
            $message = new SmsMessage($message);
        }

        if (!$message instanceof SmsMessage) {
            return null;
        }

        $to = $message->to ?? (method_exists($notifiable, 'routeNotificationFor')
            ? $notifiable->routeNotificationFor('sms', $notification)
            : ($notifiable->phone_number ?? $notifiable->phone ?? null));
        if (empty($to)) {
            return null;
        }

        $driver = $this->resolveDriver();
        return $driver->send($to, $message);
    }

    /**
     * Resolve the active SMS driver.
     */
    public function resolveDriver(): SmsDriverInterface
    {
        if ($this->driver !== null) {
            return $this->driver;
        }

        $driverName = 'log';
        if (function_exists('config')) {
            $config = config('Notifications');
            $driverName = $config->defaultSmsDriver ?? 'log';
        }

        return match ($driverName) {
            'africas_talking', 'africastalking' => new AfricasTalkingDriver(),
            'twilio'                           => new TwilioDriver(),
            'sms_gate', 'smsgate', 'sms-gate'  => new SmsGateDriver(),
            'null'                             => new NullSmsDriver(),
            default                            => new LogSmsDriver(),
        };
    }
}
