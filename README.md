<p align="center">
  <a href="https://lipex-org.github.io/jengophp.com/">
    <img src="https://raw.githubusercontent.com/lipex-org/jengophp.com/main/public/logo-full.png" width="220" alt="Jengo Logo">
  </a>
</p>

<h1 align="center">Jengo Notifications</h1>

<p align="center">
  <strong>A unified multi-channel notifications engine for CodeIgniter 4 and the Jengo Framework. Seamlessly delivers across Mail, SMS, In-App Database, Push, Webhooks, Slack, and Real-Time Broadcasts.</strong>
</p>

<p align="center">
  <a href="https://lipex-org.github.io/jengophp.com/packages/notifications"><strong>Documentation</strong></a> •
  <a href="https://github.com/lipex-org/notifications/blob/main/LICENSE"><strong>License</strong></a> •
  <a href="https://github.com/lipex-org/notifications/issues"><strong>Issues</strong></a>
</p>

---

## Installation

```bash
composer require jengo/notifications
php spark jengo:install notifications
```

## Quick Start

### 1. Define the Notification

```php
namespace App\Notifications;

use Jengo\Notifications\Messages\MailMessage;
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
            ->subject("Invoice Paid #{$this->invoice['id']}")
            ->line("We have received your payment of \${$this->invoice['amount']}.")
            ->action('View Receipt', site_url("invoices/{$this->invoice['id']}"));
    }
}
```

### 2. Dispatch Notifications

```php
use App\Notifications\InvoicePaid;
use Jengo\Notifications\Notification;

// Dispatch directly via any Notifiable entity
$user->notify(new InvoicePaid($invoice));

// Dispatch to multiple recipients
Notification::send([$admin, $customer], new InvoicePaid($invoice));

// On-demand notification to ad-hoc recipients
Notification::route('mail', 'billing@example.com')
    ->route('sms', '+254712345678')
    ->notify(new InvoicePaid($invoice));
```

## Documentation

For comprehensive guides on all delivery channels (Mail, SMS Gate, Africa's Talking, Twilio, WebPush, FCM, Slack, Webhooks), queue integration via `ShouldQueue`, in-app notifications management, and `Notification::fake()` testing doubles, visit https://lipex-org.github.io/jengophp.com/packages/notifications.

## License

Released under the MIT License.
