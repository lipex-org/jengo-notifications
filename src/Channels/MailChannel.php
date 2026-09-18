<?php

declare(strict_types=1);

namespace Jengo\Notifications\Channels;

use Config\Services;
use Jengo\Notifications\Contracts\ChannelInterface;
use Jengo\Notifications\Exceptions\CouldNotSendNotificationException;
use Jengo\Notifications\Messages\MailMessage;
use Jengo\Notifications\Notification;

class MailChannel implements ChannelInterface
{
    public function send(object $notifiable, Notification $notification): mixed
    {
        if (!method_exists($notification, 'toMail')) {
            return null;
        }

        /** @var MailMessage $message */
        $message = $notification->toMail($notifiable);
        if (!$message instanceof MailMessage) {
            return null;
        }

        $to = null;
        if (method_exists($notifiable, 'routeNotificationFor')) {
            $to = $notifiable->routeNotificationFor('mail', $notification);
        } elseif (method_exists($notifiable, 'routeNotificationForMail')) {
            $to = $notifiable->routeNotificationForMail($notification);
        } else {
            $to = $notifiable->email ?? null;
        }

        if (empty($to)) {
            return null;
        }

        $email = Services::email();

        // Sender
        if (!empty($message->from)) {
            $email->setFrom($message->from['address'], $message->from['name'] ?? '');
        }

        // Reply To
        if (!empty($message->replyTo)) {
            $email->setReplyTo($message->replyTo['address'], $message->replyTo['name'] ?? '');
        }

        // Recipients
        $email->setTo($to);

        if (!empty($message->cc)) {
            $email->setCC($message->cc);
        }

        if (!empty($message->bcc)) {
            $email->setBCC($message->bcc);
        }

        // Subject & Priority
        if (!empty($message->subject)) {
            $email->setSubject($message->subject);
        }
        $email->setPriority($message->priority);

        // Render HTML and Plain Text
        $html = $this->renderHtml($message);
        $email->setMessage($html);
        $email->setAltMessage($message->toPlainText());

        // Attachments
        foreach ($message->attachments as $att) {
            $email->attach($att['file'], $att['options']['disposition'] ?? '', $att['options']['newname'] ?? null, $att['options']['mime'] ?? '');
        }

        foreach ($message->rawAttachments as $raw) {
            $email->attach(
                $raw['data'],
                $raw['options']['disposition'] ?? 'attachment',
                $raw['name'],
                $raw['options']['mime'] ?? 'application/octet-stream',
                true
            );
        }

        if (!$email->send(false)) {
            $error = $email->printDebugger(['headers', 'subject', 'body']);
            throw CouldNotSendNotificationException::serviceRespondedWithError('mail', $error);
        }

        return true;
    }

    /**
     * Render the email HTML body.
     */
    protected function renderHtml(MailMessage $message): string
    {
        if (!empty($message->view)) {
            return view($message->view, array_merge(['message' => $message], $message->viewData));
        }

        $branding = [
            'name'         => 'Jengo',
            'logo'         => '',
            'primaryColor' => '#2563eb',
            'supportEmail' => 'support@example.com',
        ];

        if (function_exists('config')) {
            $config = config('Notifications');
            if (isset($config->branding)) {
                $branding = array_merge($branding, $config->branding);
            }
        }

        $theme = $message->theme;
        $viewFile = __DIR__ . "/../Views/email/{$theme}.php";
        if (!file_exists($viewFile)) {
            $viewFile = __DIR__ . '/../Views/email/default.php';
        }

        ob_start();
        extract([
            'message'  => $message,
            'branding' => $branding,
        ]);
        include $viewFile;
        return (string) ob_get_clean();
    }
}
