<?php

declare(strict_types=1);

namespace Jengo\Notifications\Config;

use CodeIgniter\Config\BaseService;
use Jengo\Notifications\NotificationManager;

class Services extends BaseService
{
    /**
     * Return the NotificationManager instance.
     */
    public static function notifications(bool $getShared = true): NotificationManager
    {
        if ($getShared) {
            return static::getSharedInstance('notifications');
        }

        return new NotificationManager();
    }
}
