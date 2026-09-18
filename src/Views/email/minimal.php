<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($message->subject ?? 'Notification', ENT_QUOTES, 'UTF-8') ?></title>
  <style type="text/css">
    body { margin: 0; padding: 0; background-color: #ffffff; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #111827; }
    a { color: #111827; }
    @media screen and (max-width: 600px) { .container { width: 100% !important; padding: 20px !important; } }
  </style>
</head>
<body style="margin: 0; padding: 40px 20px; background-color: #ffffff;">
  <div style="max-width: 560px; margin: 0 auto;" class="container">
    <div style="padding-bottom: 24px; border-bottom: 1px solid #e5e7eb; margin-bottom: 28px;">
      <span style="font-size: 18px; font-weight: 700; letter-spacing: -0.3px;"><?= htmlspecialchars($branding['name'] ?? 'Jengo', ENT_QUOTES, 'UTF-8') ?></span>
    </div>

    <?php if (!empty($message->greeting)): ?>
      <p style="font-size: 16px; font-weight: 600; margin: 0 0 16px;"><?= htmlspecialchars($message->greeting, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endif; ?>

    <?php foreach ($message->introLines as $line): ?>
      <p style="font-size: 15px; line-height: 24px; color: #374151; margin: 0 0 16px;"><?= htmlspecialchars($line, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endforeach; ?>

    <?php if (!empty($message->panel)): ?>
      <div style="border: 1px solid #e5e7eb; border-radius: 6px; padding: 16px; margin: 20px 0; background-color: #f9fafb; font-size: 14px; line-height: 22px;">
        <?= nl2br(htmlspecialchars($message->panel, ENT_QUOTES, 'UTF-8')) ?>
      </div>
    <?php endif; ?>

    <?php if (!empty($message->action)): ?>
      <div style="margin: 28px 0;">
        <a href="<?= htmlspecialchars($message->action['url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" style="background-color: #111827; color: #ffffff; text-decoration: none; padding: 12px 24px; border-radius: 6px; font-size: 14px; font-weight: 600; display: inline-block;">
          <?= htmlspecialchars($message->action['text'], ENT_QUOTES, 'UTF-8') ?> &rarr;
        </a>
      </div>
    <?php endif; ?>

    <?php foreach ($message->outroLines as $line): ?>
      <p style="font-size: 15px; line-height: 24px; color: #374151; margin: 0 0 16px;"><?= htmlspecialchars($line, ENT_QUOTES, 'UTF-8') ?></p>
    <?php endforeach; ?>

    <?php if (!empty($message->salutation)): ?>
      <p style="font-size: 15px; line-height: 24px; color: #374151; margin: 20px 0 0;"><?= nl2br(htmlspecialchars($message->salutation, ENT_QUOTES, 'UTF-8')) ?></p>
    <?php endif; ?>

    <div style="margin-top: 48px; padding-top: 20px; border-top: 1px solid #f3f4f6; font-size: 12px; color: #9ca3af;">
      &copy; <?= date('Y') ?> <?= htmlspecialchars($branding['name'] ?? 'Jengo', ENT_QUOTES, 'UTF-8') ?>
    </div>
  </div>
</body>
</html>
