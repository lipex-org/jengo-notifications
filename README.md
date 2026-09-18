# Jengo Notifications

Multi-channel notification delivery engine for CodeIgniter 4 and the Jengo Framework.

Documentation: https://lipex-org.github.io/jengophp.com/packages/notifications

## Features

- Multi-Channel Architecture: Deliver notifications seamlessly across Email, SMS, In-App Database, WebPush, Firebase Cloud Messaging (FCM v1), Webhooks, Slack, and Real-Time SSE/WebSocket Broadcasts.
- First-Class SMS Providers: Native zero-dependency cURL drivers for Africa's Talking (with E.164 phone normalization) and Twilio, alongside local Log and Null drivers.
- In-App Database Alerts: Clean schema with actionable URL links, button labels, severity levels, and unread scopes integrated into `BaseEntity`.
- Responsive Email Layouts: Pre-designed HTML/plain-text responsive email themes (default, minimal, dark) featuring Outlook VML bulletproof action buttons and markdown support.
- Asynchronous Queuing: Native integration with official `codeigniter4/queue` via the `ShouldQueue` contract, automatically serializing models and notifiables.
- Security-First Webhooks: Outgoing webhook delivery with automated HMAC-SHA256 timestamp signature generation and replay protection headers.
- Slack Block Kit: Fluent layout builder for Slack webhooks with markdown sections, buttons, fields, and context blocks.
- On-Demand Notifications: Send notifications to non-persisted recipients via `Notification::route('channel', 'target')->notify(...)`.
- Zero-Cost Test Doubles: Comprehensive `Notification::fake()` with rich assertions (`assertSentTo`, `assertNotSentTo`, `assertSentOnChannel`, `assertSentTimes`, `assertNothingSent`).
- Spark CLI Tooling: `php spark make:notification`, `php spark notifications:table`, and `php spark notifications:test`.

## Installation

```bash
composer require jengo/notifications
php spark notifications:table
php spark migrate
```

## Quick Start

### 1. Generating a Notification

```bash
php spark make:notification InvoicePaid
```

### 2. Defining the Notification

```php
<?php

declare(strict_types=1);

namespace App\Notifications;

use Jengo\Notifications\Messages\DatabaseMessage;
use Jengo\Notifications\Messages\MailMessage;
use Jengo\Notifications\Messages\SmsMessage;
use Jengo\Notifications\Notification;

class InvoicePaid extends Notification
{
    public function __construct(public array $invoice)
    {
        parent::__construct();
    }

    public function via(object $notifiable): array
    {
        return ['mail', 'database', 'sms'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage())
            ->subject('Invoice Paid: #' . $this->invoice['number'])
            ->greeting('Hello ' . ($notifiable->name ?? 'Customer') . ',')
            ->line('We have received your payment of $' . number_format($this->invoice['amount'], 2) . '.')
            ->action('View Receipt', site_url('invoices/' . $this->invoice['id']))
            ->salutation('Best regards, The Team');
    }

    public function toDatabase(object $notifiable): DatabaseMessage
    {
        return (new DatabaseMessage())
            ->title('Invoice Paid')
            ->message('Payment received for invoice #' . $this->invoice['number'])
            ->link(site_url('invoices/' . $this->invoice['id']))
            ->actionText('View Receipt')
            ->level('success');
    }

    public function toSms(object $notifiable): SmsMessage
    {
        return (new SmsMessage())
            ->line('Your payment for #' . $this->invoice['number'] . ' was successful.')
            ->link(site_url('invoices/' . $this->invoice['id']), 'Receipt');
    }
}
```

### 3. Adding the Notifiable Trait to Your Model or Entity

```php
namespace App\Entities;

use Jengo\Base\Entities\BaseEntity;
use Jengo\Notifications\Concerns\Notifiable;

class User extends BaseEntity
{
    use Notifiable;

    public function routeNotificationForSms(): string
    {
        return $this->phone_number;
    }
}
```

### 4. Sending Notifications

```php
use App\Notifications\InvoicePaid;
use Jengo\Notifications\Notification;

// Direct dispatch via Notifiable recipient
$user->notify(new InvoicePaid($invoice));

// Static proxy dispatch
Notification::send([$user1, $user2], new InvoicePaid($invoice));

// On-demand notification to non-persisted recipient
Notification::route('mail', 'finance@partner.com')
    ->route('sms', '+254712345678')
    ->notify(new InvoicePaid($invoice));
```

### 5. In-App Notifications Management

```php
// Retrieve unread notifications
$unread = $user->unreadNotifications();

foreach ($unread as $notification) {
    echo $notification->title();
    echo $notification->message();
    echo $notification->link();

    // Mark as read
    $notification->markAsRead();
}

// Mark all as read
$user->markAsRead();
```

### 6. Testing

```php
use App\Notifications\InvoicePaid;
use Jengo\Notifications\Notification;

Notification::fake();

$user->notify(new InvoicePaid($invoice));

Notification::assertSentTo($user, InvoicePaid::class, function ($notification) use ($invoice) {
    return $notification->invoice['id'] === $invoice['id'];
});
```

## Documentation

For full guides on queue integration, push driver configuration (WebPush & FCM), Africa's Talking setup, Slack Block Kit, and custom drivers, visit https://lipex-org.github.io/jengophp.com/packages/notifications.

## License

Released under the MIT License.
