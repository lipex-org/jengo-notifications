<?php

declare(strict_types=1);

namespace Jengo\Notifications\Config;

use CodeIgniter\Config\BaseConfig;

class Notifications extends BaseConfig
{
    /**
     * The database table used to store in-app notifications.
     */
    public string $table = 'notifications';

    /**
     * Default SMS provider driver.
     * Supported: 'africas_talking', 'twilio', 'log', 'null'
     */
    public string $defaultSmsDriver = 'log';

    /**
     * Africa's Talking API configuration.
     */
    public array $africasTalking = [
        'username'           => 'sandbox',
        'apiKey'             => '',
        'senderId'           => '',
        'defaultCountryCode' => '+254',
    ];

    /**
     * Twilio API configuration.
     */
    public array $twilio = [
        'accountSid' => '',
        'authToken'  => '',
        'fromNumber' => '',
    ];

    /**
     * Push notification configuration.
     */
    public array $push = [
        'driver'  => 'log', // 'fcm', 'webpush', 'log', 'null'
        'fcm'     => [
            'projectId'          => '',
            'serviceAccountJson' => '',
            'accessToken'        => null,
        ],
        'webpush' => [
            'publicKey'  => '',
            'privateKey' => '',
            'subject'    => 'mailto:admin@example.com',
        ],
    ];

    /**
     * Slack notification configuration.
     */
    public array $slack = [
        'webhookUrl' => '',
    ];

    /**
     * Transactional Email theme and branding options.
     */
    public string $emailTheme = 'default';

    public array $branding = [
        'name'         => 'Jengo',
        'logo'         => '',
        'primaryColor' => '#2563eb',
        'supportEmail' => 'support@example.com',
    ];
}
