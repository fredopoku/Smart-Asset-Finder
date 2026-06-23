<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\SMTP;
use PHPMailer\PHPMailer\Exception as MailerException;

require_once __DIR__.'/../vendor/phpmailer/phpmailer/src/Exception.php';
require_once __DIR__.'/../vendor/phpmailer/phpmailer/src/PHPMailer.php';
require_once __DIR__.'/../vendor/phpmailer/phpmailer/src/SMTP.php';

class Mailer {
    private static function make(): PHPMailer {
        $mail = new PHPMailer(true);

        if(saf_env('MAIL_DRIVER', 'smtp') === 'smtp') {
            $mail->isSMTP();
            $mail->Host       = saf_env('MAIL_HOST', 'smtp.gmail.com');
            $mail->Port       = (int) saf_env('MAIL_PORT', 587);
            $mail->SMTPSecure = saf_env('MAIL_ENCRYPTION', 'tls') === 'ssl' ? PHPMailer::ENCRYPTION_SMTPS : PHPMailer::ENCRYPTION_STARTTLS;
            $mail->SMTPAuth   = true;
            $mail->Username   = saf_env('MAIL_USERNAME', '');
            $mail->Password   = saf_env('MAIL_PASSWORD', '');
        } else {
            $mail->isMail();
        }

        $mail->setFrom(
            saf_env('MAIL_FROM_ADDRESS', 'no-reply@smartassetfinder.com'),
            saf_env('MAIL_FROM_NAME',    'Smart Asset Finder')
        );
        $mail->isHTML(true);
        $mail->CharSet = 'UTF-8';
        return $mail;
    }

    private static function wrap(string $title, string $body, string $preheader = ''): string {
        $year    = date('Y');
        $appName = saf_env('APP_NAME', 'Smart Asset Finder');
        $appUrl  = saf_env('APP_URL',  'http://localhost/Smart-Asset-Finder/');
        $pre     = $preheader ? "<div style='display:none;max-height:0;overflow:hidden;mso-hide:all'>{$preheader}&nbsp;&zwnj;&nbsp;&zwnj;&nbsp;&zwnj;</div>" : '';
        return "<!DOCTYPE html>
<html lang='en'><head><meta charset='UTF-8'>
<meta name='viewport' content='width=device-width,initial-scale=1'>
<title>{$title}</title>
</head>
<body style='margin:0;padding:0;background:#f0f4ff;font-family:Inter,system-ui,-apple-system,sans-serif;-webkit-font-smoothing:antialiased'>
{$pre}
  <table width='100%' cellpadding='0' cellspacing='0' style='padding:48px 16px'>
    <tr><td align='center'>
      <table width='100%' style='max-width:600px;background:#ffffff;border-radius:20px;overflow:hidden;box-shadow:0 4px 32px rgba(79,70,229,.10)'>

        <!-- Header -->
        <tr>
          <td style='padding:36px 48px 28px;background:linear-gradient(135deg,#4f46e5 0%,#7c3aed 100%);text-align:center'>
            <div style='font-family:\"Space Grotesk\",system-ui,sans-serif;font-size:26px;font-weight:800;color:#fff;letter-spacing:-.5px;margin-bottom:4px'>{$appName}</div>
            <div style='font-size:11px;font-weight:600;color:rgba(255,255,255,.65);letter-spacing:3px;text-transform:uppercase'>SMART LOST &amp; FOUND</div>
          </td>
        </tr>

        <!-- Body -->
        <tr>
          <td style='padding:44px 48px'>
            {$body}
          </td>
        </tr>

        <!-- Divider -->
        <tr><td style='padding:0 48px'><div style='border-top:1px solid #e2e8f0'></div></td></tr>

        <!-- Footer -->
        <tr>
          <td style='padding:28px 48px;text-align:center'>
            <p style='margin:0 0 6px;font-size:12px;color:#94a3b8'>
              &copy; {$year} {$appName} &bull;
              <a href='{$appUrl}' style='color:#6366f1;text-decoration:none'>smartassetfinder.com</a>
            </p>
            <p style='margin:0;font-size:11px;color:#cbd5e1'>
              You received this because you have an account with us.<br>
              <a href='{$appUrl}?page=profile' style='color:#94a3b8;text-decoration:underline'>Manage email preferences</a>
            </p>
          </td>
        </tr>

      </table>
    </td></tr>
  </table>
</body></html>";
    }

    private static function btn(string $url, string $label, string $color = '#4f46e5'): string {
        return "<div style='text-align:center;margin:32px 0'>
          <a href='{$url}' style='background:{$color};color:#fff;padding:15px 38px;border-radius:50px;text-decoration:none;font-weight:700;font-size:15px;letter-spacing:-.2px;display:inline-block'>{$label}</a>
        </div>";
    }

    private static function infoBox(string $html, string $border = '#4f46e5', string $bg = '#f5f3ff'): string {
        return "<div style='background:{$bg};border-left:4px solid {$border};padding:18px 22px;border-radius:10px;margin:20px 0'>{$html}</div>";
    }

    public static function send(string $to, string $toName, string $subject, string $htmlBody, string $preheader = ''): bool {
        // In development mode with no SMTP configured — skip silently
        if(APP_ENV !== 'production' && !saf_env('MAIL_USERNAME')) {
            error_log("[SAF Mailer] Email skipped (no SMTP config). To: {$to} | Subject: {$subject}");
            return true;
        }
        try {
            $mail = self::make();
            $mail->addAddress($to, $toName);
            $mail->Subject = $subject;
            $mail->Body    = self::wrap($subject, $htmlBody, $preheader);
            $mail->AltBody = strip_tags(str_replace(['<br>', '<br/>', '<p>', '</p>'], ["\n", "\n", "\n", "\n"], $htmlBody));
            $mail->send();
            return true;
        } catch(MailerException $e) {
            error_log('[SAF Mailer] '.$e->getMessage());
            return false;
        }
    }

    // ── Public email methods ────────────────────────────────────────────────

    public static function welcome(string $to, string $name, string $verifyUrl = ''): bool {
        $appUrl  = saf_env('APP_URL', 'http://localhost/Smart-Asset-Finder/');
        $first   = htmlspecialchars(explode(' ', $name)[0]);
        $verifyBtn = $verifyUrl ? self::btn($verifyUrl, 'Verify My Email →') : '';
        $body = "
          <h1 style='margin:0 0 6px;color:#0f172a;font-size:26px;font-weight:800;letter-spacing:-.5px'>Welcome, {$first}! 👋</h1>
          <p style='color:#64748b;font-size:15px;line-height:1.7;margin:0 0 28px'>Your Smart Asset Finder account is live. Here's what you can do right now:</p>

          <table width='100%' cellpadding='0' cellspacing='0'>
            <tr>
              <td style='padding:14px 0;border-bottom:1px solid #f1f5f9;vertical-align:top;width:36px'>
                <span style='font-size:20px'>🔍</span>
              </td>
              <td style='padding:14px 0 14px 14px;border-bottom:1px solid #f1f5f9'>
                <strong style='color:#0f172a;font-size:14px'>Search for your lost item</strong><br>
                <span style='color:#64748b;font-size:13px'>Our database is searched by real finders worldwide.</span>
              </td>
            </tr>
            <tr>
              <td style='padding:14px 0;border-bottom:1px solid #f1f5f9;vertical-align:top'>
                <span style='font-size:20px'>📝</span>
              </td>
              <td style='padding:14px 0 14px 14px;border-bottom:1px solid #f1f5f9'>
                <strong style='color:#0f172a;font-size:14px'>Report what you lost or found</strong><br>
                <span style='color:#64748b;font-size:13px'>Our AI instantly scans for matches and notifies you.</span>
              </td>
            </tr>
            <tr>
              <td style='padding:14px 0;vertical-align:top'>
                <span style='font-size:20px'>🏷️</span>
              </td>
              <td style='padding:14px 0 14px 14px'>
                <strong style='color:#0f172a;font-size:14px'>Tag your valuables</strong><br>
                <span style='color:#64748b;font-size:13px'>Order a SAF tag — any finder scans it and reaches you instantly. No app needed.</span>
              </td>
            </tr>
          </table>

          {$verifyBtn}
          ".($verifyBtn ? '' : self::btn($appUrl.'?page=items', 'Start Searching →'))."

          <p style='color:#94a3b8;font-size:12px;margin:28px 0 0;text-align:center'>
            Have a question? Reply to this email — we read every one.
          </p>";
        return self::send($to, $name, "Welcome to Smart Asset Finder, {$first}!", $body, "Your account is ready. Here's how to get started.");
    }

    public static function qrScanned(
        string $to, string $ownerName, string $itemLabel,
        string $finderName, string $finderContact,
        string $location, string $finderMsg, string $dashUrl
    ): bool {
        $item  = htmlspecialchars($itemLabel ?: 'your tagged item');
        $first = htmlspecialchars(explode(' ', $ownerName)[0]);
        $locRow = $location ? "<tr><td style='padding:8px 0;color:#64748b;width:40%'>Found at</td><td style='padding:8px 0;font-weight:600;color:#0f172a'>".htmlspecialchars($location)."</td></tr>" : '';
        $msgRow = $finderMsg ? "<tr><td style='padding:8px 0;color:#64748b;vertical-align:top'>Message</td><td style='padding:8px 0;color:#0f172a'>".htmlspecialchars($finderMsg)."</td></tr>" : '';
        $body = "
          <div style='text-align:center;margin-bottom:28px'>
            <div style='width:64px;height:64px;background:linear-gradient(135deg,#4f46e5,#7c3aed);border-radius:50%;margin:0 auto 14px;display:flex;align-items:center;justify-content:center;font-size:28px'>🔔</div>
            <h1 style='margin:0;color:#0f172a;font-size:24px;font-weight:800;letter-spacing:-.5px'>Your SAF tag was scanned!</h1>
          </div>
          <p style='color:#475569;font-size:15px;line-height:1.7;margin:0 0 24px'>Hi <strong>{$first}</strong>, good news — someone found <strong>{$item}</strong> and scanned your tag to return it to you.</p>
          ".self::infoBox("
            <table width='100%' cellpadding='0' cellspacing='0' style='border-collapse:collapse'>
              <tr><td style='padding:8px 0;color:#64748b;width:40%'>Finder</td><td style='padding:8px 0;font-weight:700;color:#0f172a;font-size:15px'>".htmlspecialchars($finderName)."</td></tr>
              <tr><td style='padding:8px 0;border-top:1px solid #e2e8f0;color:#64748b'>Contact</td><td style='padding:8px 0;border-top:1px solid #e2e8f0;font-weight:600;color:#0f172a'>".htmlspecialchars($finderContact)."</td></tr>
              {$locRow}{$msgRow}
            </table>
          ", '#4f46e5', '#f5f3ff')."
          <p style='color:#475569;font-size:14px;line-height:1.7;margin:0 0 8px'>Reach out directly or log in to your dashboard to respond.</p>
          ".self::btn($dashUrl, 'Go to My Dashboard →')."
          <p style='color:#94a3b8;font-size:12px;margin:20px 0 0;text-align:center'>If this tag doesn't belong to you, please ignore this email.</p>";
        return self::send($to, $ownerName, "Your SAF tag was scanned — {$item} found!", $body, "Someone found {$item} and wants to return it.");
    }

    public static function itemMatched(
        string $to, string $ownerName,
        string $lostTitle, string $foundTitle, string $itemUrl
    ): bool {
        $first = htmlspecialchars(explode(' ', $ownerName)[0]);
        $body = "
          <div style='text-align:center;margin-bottom:28px'>
            <div style='font-size:48px;margin-bottom:12px'>🤖</div>
            <h1 style='margin:0;color:#0f172a;font-size:24px;font-weight:800;letter-spacing:-.5px'>AI found a possible match!</h1>
          </div>
          <p style='color:#475569;font-size:15px;line-height:1.7;margin:0 0 24px'>Hi <strong>{$first}</strong>, our AI just scanned all recent found reports and found something that might be yours.</p>
          <table width='100%' cellpadding='0' cellspacing='0' style='margin:0 0 24px'>
            <tr>
              <td style='background:#fef2f2;border-radius:10px;padding:18px 20px;width:48%'>
                <div style='font-size:11px;font-weight:700;color:#dc2626;text-transform:uppercase;letter-spacing:1px;margin-bottom:6px'>You reported lost</div>
                <div style='font-weight:700;color:#0f172a;font-size:14px'>".htmlspecialchars($lostTitle)."</div>
              </td>
              <td style='text-align:center;padding:0 10px;font-size:22px;color:#64748b'>→</td>
              <td style='background:#f0fdf4;border-radius:10px;padding:18px 20px;width:48%'>
                <div style='font-size:11px;font-weight:700;color:#059669;text-transform:uppercase;letter-spacing:1px;margin-bottom:6px'>Possible match found</div>
                <div style='font-weight:700;color:#0f172a;font-size:14px'>".htmlspecialchars($foundTitle)."</div>
              </td>
            </tr>
          </table>
          <p style='color:#64748b;font-size:14px;line-height:1.7;margin:0 0 8px'>View the found item and submit a claim if it's yours. Claims are verified — your item is protected.</p>
          ".self::btn($itemUrl, 'View the Match →', '#059669');
        return self::send($to, $ownerName, "Possible match found for your lost item", $body, "Our AI matched your lost report to a found item.");
    }

    public static function newClaimOnItem(
        string $to, string $ownerName,
        string $itemTitle, string $claimantName,
        float $aiScore, string $dashUrl
    ): bool {
        $first    = htmlspecialchars(explode(' ', $ownerName)[0]);
        $scoreColor = $aiScore >= 70 ? '#059669' : ($aiScore >= 40 ? '#d97706' : '#dc2626');
        $scoreLabel = $aiScore >= 70 ? 'Strong match' : ($aiScore >= 40 ? 'Partial match' : 'Low confidence');
        $scoreRow = $aiScore > 0
            ? "<tr><td style='padding:8px 0;border-top:1px solid #e2e8f0;color:#64748b'>AI Score</td><td style='padding:8px 0;border-top:1px solid #e2e8f0'><strong style='color:{$scoreColor}'>".number_format($aiScore, 1)."%</strong> <span style='color:#94a3b8;font-size:12px'>({$scoreLabel})</span></td></tr>"
            : '';
        $body = "
          <h1 style='margin:0 0 6px;color:#0f172a;font-size:24px;font-weight:800;letter-spacing:-.5px'>📋 New claim on your item</h1>
          <p style='color:#475569;font-size:15px;line-height:1.7;margin:0 0 24px'>Hi <strong>{$first}</strong>, someone just submitted a claim of ownership for one of your reports.</p>
          ".self::infoBox("
            <table width='100%' cellpadding='0' cellspacing='0' style='border-collapse:collapse'>
              <tr><td style='padding:8px 0;color:#64748b;width:40%'>Item</td><td style='padding:8px 0;font-weight:700;color:#0f172a'>".htmlspecialchars($itemTitle)."</td></tr>
              <tr><td style='padding:8px 0;border-top:1px solid #e2e8f0;color:#64748b'>Claimant</td><td style='padding:8px 0;border-top:1px solid #e2e8f0;font-weight:600;color:#0f172a'>".htmlspecialchars($claimantName)."</td></tr>
              {$scoreRow}
            </table>
          ", '#f59e0b', '#fffbeb')."
          <p style='color:#475569;font-size:14px;line-height:1.7;margin:0 0 8px'>Review their proof of ownership and approve or reject the claim from your dashboard.</p>
          ".self::btn($dashUrl, 'Review the Claim →', '#f59e0b');
        return self::send($to, $ownerName, "New claim on: ".htmlspecialchars($itemTitle), $body, htmlspecialchars($claimantName)." just claimed your item.");
    }

    public static function broadcast(string $to, string $name, string $subject, string $htmlContent): bool {
        $first = htmlspecialchars(explode(' ', $name)[0]);
        $body  = str_replace(['{{name}}', '{{first}}'], [$name, $first], $htmlContent);
        return self::send($to, $name, $subject, $body, '');
    }

    public static function claimReceived(string $to, string $name, string $itemTitle): bool {
        $appUrl = saf_env('APP_URL', 'http://localhost/Smart-Asset-Finder/');
        $body = "
            <h2 style='margin:0 0 8px;color:#0f172a;font-size:22px'>Claim Submitted</h2>
            <p style='color:#475569;line-height:1.7;margin:0 0 16px'>Hi <strong>".htmlspecialchars($name)."</strong>, your claim for the following item has been received and is under review:</p>
            <div style='background:#f8faff;border-left:4px solid #1a56db;padding:16px 20px;border-radius:8px;margin:0 0 24px'>
              <strong style='color:#0f172a'>".htmlspecialchars($itemTitle)."</strong>
            </div>
            <p style='color:#475569;line-height:1.7;margin:0 0 24px'>Our team will review your claim and get back to you by email once a decision has been made. You can also check your claim status anytime in <strong>My Claims</strong>.</p>
            <div style='text-align:center'>
              <a href='{$appUrl}?page=my-items' style='background:linear-gradient(135deg,#1a56db,#0ea5e9);color:#fff;padding:12px 28px;border-radius:50px;text-decoration:none;font-weight:600;font-size:14px'>View My Claims</a>
            </div>";
        return self::send($to, $name, 'Claim received: '.htmlspecialchars($itemTitle), $body);
    }

    public static function claimApproved(string $to, string $name, string $itemTitle, string $adminNote = ''): bool {
        $appUrl = saf_env('APP_URL', 'http://localhost/Smart-Asset-Finder/');
        $noteHtml = $adminNote ? "<p style='color:#475569;line-height:1.7;margin:16px 0 0'><strong>Note from admin:</strong> ".htmlspecialchars($adminNote)."</p>" : '';
        $body = "
            <div style='text-align:center;margin-bottom:24px'>
              <div style='width:64px;height:64px;background:linear-gradient(135deg,#059669,#10b981);border-radius:50%;margin:0 auto 12px;display:flex;align-items:center;justify-content:center;font-size:28px'>✓</div>
            </div>
            <h2 style='margin:0 0 8px;color:#059669;font-size:22px;text-align:center'>Claim Approved!</h2>
            <p style='color:#475569;line-height:1.7;margin:0 0 16px;text-align:center'>Great news, <strong>".htmlspecialchars($name)."</strong>! Your claim has been <strong style='color:#059669'>approved</strong>:</p>
            <div style='background:#f0fdf4;border-left:4px solid #059669;padding:16px 20px;border-radius:8px;margin:0 0 20px'>
              <strong style='color:#0f172a'>".htmlspecialchars($itemTitle)."</strong>
            </div>
            <p style='color:#475569;line-height:1.7;margin:0'>Please contact the finder or admin to arrange the return of your item.</p>
            {$noteHtml}";
        return self::send($to, $name, '✓ Claim approved: '.htmlspecialchars($itemTitle), $body);
    }

    public static function claimRejected(string $to, string $name, string $itemTitle, string $adminNote = ''): bool {
        $noteHtml = $adminNote ? "<div style='background:#fef2f2;border-left:4px solid #dc2626;padding:16px 20px;border-radius:8px;margin:16px 0'><strong>Reason:</strong> ".htmlspecialchars($adminNote)."</div>" : '';
        $appUrl   = saf_env('APP_URL', 'http://localhost/Smart-Asset-Finder/');
        $body = "
            <h2 style='margin:0 0 8px;color:#dc2626;font-size:22px'>Claim Not Approved</h2>
            <p style='color:#475569;line-height:1.7;margin:0 0 16px'>Hi <strong>".htmlspecialchars($name)."</strong>, unfortunately your claim for the following item was not approved at this time:</p>
            <div style='background:#fef2f2;border-left:4px solid #dc2626;padding:16px 20px;border-radius:8px;margin:0 0 16px'>
              <strong style='color:#0f172a'>".htmlspecialchars($itemTitle)."</strong>
            </div>
            {$noteHtml}
            <p style='color:#475569;line-height:1.7;margin:16px 0 24px'>If you believe this is an error or have additional proof of ownership, please contact us.</p>
            <div style='text-align:center'>
              <a href='{$appUrl}?page=items' style='background:#f1f5f9;color:#1a56db;padding:12px 28px;border-radius:50px;text-decoration:none;font-weight:600;font-size:14px'>Browse Other Items</a>
            </div>";
        return self::send($to, $name, 'Claim update: '.htmlspecialchars($itemTitle), $body);
    }

    public static function newClaimAdmin(string $adminEmail, string $claimantName, string $itemTitle, string $claimUrl): bool {
        $appName = saf_env('APP_NAME', 'Smart Asset Finder');
        $body = "
            <h2 style='margin:0 0 8px;color:#0f172a;font-size:22px'>New Claim Submitted</h2>
            <p style='color:#475569;line-height:1.7;margin:0 0 16px'>A new ownership claim requires your review:</p>
            <table style='width:100%;border-collapse:collapse;margin:0 0 24px'>
              <tr><td style='padding:10px 0;border-bottom:1px solid #e2e8f0;color:#64748b;width:40%'>Claimant</td><td style='padding:10px 0;border-bottom:1px solid #e2e8f0;font-weight:600;color:#0f172a'>".htmlspecialchars($claimantName)."</td></tr>
              <tr><td style='padding:10px 0;color:#64748b'>Item</td><td style='padding:10px 0;font-weight:600;color:#0f172a'>".htmlspecialchars($itemTitle)."</td></tr>
            </table>
            <div style='text-align:center'>
              <a href='{$claimUrl}' style='background:linear-gradient(135deg,#1a56db,#0ea5e9);color:#fff;padding:14px 36px;border-radius:50px;text-decoration:none;font-weight:700;font-size:15px'>Review Claim</a>
            </div>";
        return self::send($adminEmail, $appName.' Admin', 'New claim: '.htmlspecialchars($itemTitle), $body);
    }

    public static function verifyEmail(string $to, string $name, string $verifyUrl): bool {
        $body = "
            <h2 style='margin:0 0 8px;color:#0f172a;font-size:22px'>Verify Your Email Address</h2>
            <p style='color:#475569;line-height:1.7;margin:0 0 24px'>Hi <strong>".htmlspecialchars($name)."</strong>, thanks for signing up! Click the button below to confirm your email address and activate your account.</p>
            <div style='text-align:center;margin:28px 0'>
              <a href='".htmlspecialchars($verifyUrl)."' style='background:linear-gradient(135deg,#1a56db,#0ea5e9);color:#fff;padding:14px 36px;border-radius:50px;text-decoration:none;font-weight:700;font-size:15px'>Verify My Email</a>
            </div>
            <p style='color:#475569;line-height:1.7;margin:0 0 12px'>Or copy and paste this link into your browser:</p>
            <p style='background:#f1f5f9;padding:10px 14px;border-radius:8px;word-break:break-all;font-size:13px;color:#1a56db;margin:0 0 24px'>".htmlspecialchars($verifyUrl)."</p>
            <p style='color:#94a3b8;font-size:13px;margin:0'>If you didn't create this account, you can safely ignore this email.</p>";
        return self::send($to, $name, 'Please verify your email – Smart Asset Finder', $body);
    }

    public static function passwordReset(string $to, string $name, string $resetUrl): bool {
        $body = "
            <h2 style='margin:0 0 8px;color:#0f172a;font-size:22px'>Reset Your Password</h2>
            <p style='color:#475569;line-height:1.7;margin:0 0 24px'>Hi <strong>".htmlspecialchars($name)."</strong>, we received a request to reset your password. Click the button below — this link expires in <strong>60 minutes</strong>.</p>
            <div style='text-align:center;margin:28px 0'>
              <a href='".htmlspecialchars($resetUrl)."' style='background:linear-gradient(135deg,#1a56db,#0ea5e9);color:#fff;padding:14px 36px;border-radius:50px;text-decoration:none;font-weight:700;font-size:15px'>Reset Password</a>
            </div>
            <p style='color:#94a3b8;font-size:13px;margin:0'>If you didn't request this, you can safely ignore this email. Your password will not change.</p>";
        return self::send($to, $name, 'Reset your Smart Asset Finder password', $body);
    }
}
