<?php

declare(strict_types=1);

namespace Jengo\Notifications\Jobs;

use Jengo\Notifications\Notification;
use Jengo\Notifications\NotificationManager;
use Throwable;

if (class_exists(\CodeIgniter\Queue\BaseJob::class)) {
    class SendQueuedNotification extends \CodeIgniter\Queue\BaseJob
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
} else {
    class SendQueuedNotification
    {
        public array $data = [];

        public function __construct(array $data = [])
        {
            $this->data = $data;
        }

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
}
