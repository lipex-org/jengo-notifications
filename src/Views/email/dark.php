<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= htmlspecialchars($message->subject ?? 'Notification', ENT_QUOTES, 'UTF-8') ?></title>
  <style type="text/css">
    body { margin: 0; padding: 0; background-color: #0f172a; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; color: #f8fafc; }
    @media screen and (max-width: 600px) { .container { width: 100% !important; padding: 20px 10px !important; } }
  </style>
</head>
<body style="margin: 0; padding: 40px 15px; background-color: #0f172a;">
  <div style="max-width: 600px; margin: 0 auto;" class="container">
    <div style="text-align: center; margin-bottom: 24px;">
      <span style="font-size: 20px; font-weight: 700; color: #f8fafc; letter-spacing: -0.5px;"><?= htmlspecialchars($branding['name'] ?? 'Jengo', ENT_QUOTES, 'UTF-8') ?></span>
    </div>

    <div style="background-color: #1e293b; border: 1px solid #334155; border-radius: 12px; padding: 36px 32px; box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.3);">
      <?php if (!empty($message->greeting)): ?>
        <p style="font-size: 18px; font-weight: 600; color: #f8fafc; margin: 0 0 16px;"><?= htmlspecialchars($message->greeting, ENT_QUOTES, 'UTF-8') ?></p>
      <?php endif; ?>

      <?php foreach ($message->introLines as $line): ?>
        <p style="font-size: 15px; line-height: 24px; color: #cbd5e1; margin: 0 0 16px;"><?= htmlspecialchars($line, ENT_QUOTES, 'UTF-8') ?></p>
      <?php endforeach; ?>

      <?php if (!empty($message->panel)): ?>
        <div style="background-color: #0f172a; border-left: 4px solid #38bdf8; border-radius: 4px; padding: 16px; margin: 20px 0; color: #e2e8f0; font-size: 14px; line-height: 22px;">
          <?= nl2br(htmlspecialchars($message->panel, ENT_QUOTES, 'UTF-8')) ?>
        </div>
      <?php endif; ?>

      <?php if (!empty($message->action)): ?>
        <div style="text-align: center; margin: 32px 0;">
          <a href="<?= htmlspecialchars($message->action['url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" style="background-color: #38bdf8; color: #0f172a; text-decoration: none; padding: 14px 28px; border-radius: 8px; font-size: 15px; font-weight: 700; display: inline-block;">
            <?= htmlspecialchars($message->action['text'], ENT_QUOTES, 'UTF-8') ?>
          </a>
        </div>
      <?php endif; ?>

      <?php foreach ($message->outroLines as $line): ?>
        <p style="font-size: 15px; line-height: 24px; color: #cbd5e1; margin: 0 0 16px;"><?= htmlspecialchars($line, ENT_QUOTES, 'UTF-8') ?></p>
      <?php endforeach; ?>

      <?php if (!empty($message->salutation)): ?>
        <p style="font-size: 15px; line-height: 24px; color: #94a3b8; margin: 24px 0 0;"><?= nl2br(htmlspecialchars($message->salutation, ENT_QUOTES, 'UTF-8')) ?></p>
      <?php endif; ?>
    </div>

    <div style="text-align: center; margin-top: 32px; font-size: 12px; color: #64748b;">
      &copy; <?= date('Y') ?> <?= htmlspecialchars($branding['name'] ?? 'Jengo', ENT_QUOTES, 'UTF-8') ?>. All rights reserved.
    </div>
  </div>
</body>
</html>
