<?php

declare(strict_types=1);

namespace Jengo\Notifications\Jobs;

use CodeIgniter\Queue\BaseJob;
use Jengo\Notifications\Notification;
use Jengo\Notifications\NotificationManager;
use Throwable;

class SendQueuedNotification extends BaseJob
{
    /**
     * Process the queued notification.
     */
    public function process(): bool
    {
        $payload = $this->data ?? [];

        if (empty($payload['notification']) || empty($payload['notifiables'])) {
            return false;
        }

        try {
            /** @var Notification $notification */
            $notification = unserialize($payload['notification']);
            $notifiables  = unserialize($payload['notifiables']);

            NotificationManager::getInstance()->sendNow($notifiables, $notification);
            return true;
        } catch (Throwable $e) {
            log_message('error', '[Queue] Failed to process queued notification: ' . $e->getMessage());
            return false;
        }
    }
}
