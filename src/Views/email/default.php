<!DOCTYPE html>
<html lang="en" xmlns:v="urn:schemas-microsoft-com:vml" xmlns:o="urn:schemas-microsoft-com:office:office">
<head>
  <meta charset="utf-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <meta http-equiv="X-UA-Compatible" content="IE=edge">
  <title><?= htmlspecialchars($message->subject ?? 'Notification', ENT_QUOTES, 'UTF-8') ?></title>
  <style type="text/css">
    body, table, td, a { -webkit-text-size-adjust: 100%; -ms-text-size-adjust: 100%; }
    table, td { mso-table-lspace: 0pt; mso-table-rspace: 0pt; }
    img { -ms-interpolation-mode: bicubic; border: 0; height: auto; line-height: 100%; outline: none; text-decoration: none; }
    body { height: 100% !important; margin: 0 !important; padding: 0 !important; width: 100% !important; background-color: #f3f4f6; font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Helvetica, Arial, sans-serif; }
    @media screen and (max-width: 600px) {
      .email-container { width: 100% !important; margin: auto !important; }
      .fluid { max-width: 100% !important; height: auto !important; margin-left: auto !important; margin-right: auto !important; }
      .stack-column { display: block !important; width: 100% !important; max-width: 100% !important; direction: ltr !important; }
      .mobile-padding { padding-left: 20px !important; padding-right: 20px !important; }
    }
  </style>
</head>
<body style="margin: 0; padding: 0; background-color: #f3f4f6;">
  <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
    <tr>
      <td align="center" style="padding: 40px 15px;">
        <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="max-width: 600px;" class="email-container">
          <!-- Header -->
          <tr>
            <td align="center" style="padding-bottom: 24px;">
              <?php if (!empty($branding['logo'])): ?>
                <img src="<?= htmlspecialchars($branding['logo'], ENT_QUOTES, 'UTF-8') ?>" alt="<?= htmlspecialchars($branding['name'] ?? 'Jengo', ENT_QUOTES, 'UTF-8') ?>" width="120" style="display: block; margin: 0 auto;">
              <?php else: ?>
                <span style="font-size: 22px; font-weight: 700; color: #111827; letter-spacing: -0.5px;"><?= htmlspecialchars($branding['name'] ?? 'Jengo', ENT_QUOTES, 'UTF-8') ?></span>
              <?php endif; ?>
            </td>
          </tr>

          <!-- Main Card -->
          <tr>
            <td style="background-color: #ffffff; border-radius: 12px; padding: 40px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05), 0 2px 4px -1px rgba(0, 0, 0, 0.03);" class="mobile-padding">
              <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                <!-- Greeting -->
                <?php if (!empty($message->greeting)): ?>
                  <tr>
                    <td style="font-size: 18px; font-weight: 600; color: #111827; padding-bottom: 16px;">
                      <?= htmlspecialchars($message->greeting, ENT_QUOTES, 'UTF-8') ?>
                    </td>
                  </tr>
                <?php endif; ?>

                <!-- Intro Lines -->
                <?php foreach ($message->introLines as $line): ?>
                  <tr>
                    <td style="font-size: 15px; line-height: 24px; color: #374151; padding-bottom: 16px;">
                      <?= htmlspecialchars($line, ENT_QUOTES, 'UTF-8') ?>
                    </td>
                  </tr>
                <?php endforeach; ?>

                <!-- Highlight Panel -->
                <?php if (!empty($message->panel)): ?>
                  <tr>
                    <td style="padding: 16px 0;">
                      <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%">
                        <tr>
                          <td style="background-color: #f9fafb; border-left: 4px solid <?= htmlspecialchars($branding['primaryColor'] ?? '#2563eb', ENT_QUOTES, 'UTF-8') ?>; border-radius: 4px; padding: 16px 20px; font-size: 15px; font-weight: 500; color: #1f2937; line-height: 22px;">
                            <?= nl2br(htmlspecialchars($message->panel, ENT_QUOTES, 'UTF-8')) ?>
                          </td>
                        </tr>
                      </table>
                    </td>
                  </tr>
                <?php endif; ?>

                <!-- Table -->
                <?php if (!empty($message->table)): ?>
                  <tr>
                    <td style="padding: 20px 0;">
                      <table role="presentation" border="0" cellpadding="0" cellspacing="0" width="100%" style="border-collapse: collapse;">
                        <thead>
                          <tr style="border-bottom: 2px solid #e5e7eb;">
                            <?php foreach ($message->table['headers'] as $h): ?>
                              <th align="left" style="padding: 10px 12px; font-size: 13px; font-weight: 600; color: #4b5563; text-transform: uppercase; letter-spacing: 0.5px;">
                                <?= htmlspecialchars($h, ENT_QUOTES, 'UTF-8') ?>
                              </th>
                            <?php endforeach; ?>
                          </tr>
                        </thead>
                        <tbody>
                          <?php foreach ($message->table['rows'] as $r): ?>
                            <tr style="border-bottom: 1px solid #f3f4f6;">
                              <?php foreach ($r as $val): ?>
                                <td style="padding: 12px; font-size: 14px; color: #374151;">
                                  <?= htmlspecialchars((string) $val, ENT_QUOTES, 'UTF-8') ?>
                                </td>
                              <?php endforeach; ?>
                            </tr>
                          <?php endforeach; ?>
                        </tbody>
                      </table>
                    </td>
                  </tr>
                <?php endif; ?>

                <!-- Action Button -->
                <?php if (!empty($message->action)): ?>
                  <tr>
                    <td align="center" style="padding: 24px 0;">
                      <table role="presentation" border="0" cellpadding="0" cellspacing="0">
                        <tr>
                          <td align="center" style="border-radius: 8px; background-color: <?= htmlspecialchars($branding['primaryColor'] ?? '#2563eb', ENT_QUOTES, 'UTF-8') ?>;">
                            <a href="<?= htmlspecialchars($message->action['url'], ENT_QUOTES, 'UTF-8') ?>" target="_blank" style="font-size: 15px; font-weight: 600; color: #ffffff; text-decoration: none; padding: 14px 28px; display: inline-block; border-radius: 8px; letter-spacing: 0.2px;">
                              <?= htmlspecialchars($message->action['text'], ENT_QUOTES, 'UTF-8') ?>
                            </a>
                          </td>
                        </tr>
                      </table>
                    </td>
                  </tr>
                <?php endif; ?>

                <!-- Outro Lines -->
                <?php foreach ($message->outroLines as $line): ?>
                  <tr>
                    <td style="font-size: 15px; line-height: 24px; color: #374151; padding-bottom: 16px;">
                      <?= htmlspecialchars($line, ENT_QUOTES, 'UTF-8') ?>
                    </td>
                  </tr>
                <?php endforeach; ?>

                <!-- Salutation -->
                <?php if (!empty($message->salutation)): ?>
                  <tr>
                    <td style="font-size: 15px; line-height: 24px; color: #374151; padding-top: 8px;">
                      <?= nl2br(htmlspecialchars($message->salutation, ENT_QUOTES, 'UTF-8')) ?>
                    </td>
                  </tr>
                <?php endif; ?>
              </table>
            </td>
          </tr>

          <!-- Footer -->
          <tr>
            <td align="center" style="padding: 32px 20px 0; font-size: 13px; color: #9ca3af; line-height: 20px;">
              <p style="margin: 0 0 8px;">&copy; <?= date('Y') ?> <?= htmlspecialchars($branding['name'] ?? 'Jengo', ENT_QUOTES, 'UTF-8') ?>. All rights reserved.</p>
              <?php if (!empty($branding['supportEmail'])): ?>
                <p style="margin: 0;">Questions? Contact <a href="mailto:<?= htmlspecialchars($branding['supportEmail'], ENT_QUOTES, 'UTF-8') ?>" style="color: #6b7280; text-decoration: underline;"><?= htmlspecialchars($branding['supportEmail'], ENT_QUOTES, 'UTF-8') ?></a></p>
              <?php endif; ?>
            </td>
          </tr>
        </table>
      </td>
    </tr>
  </table>
</body>
</html>
