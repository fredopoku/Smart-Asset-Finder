<?php
/**
 * Whatsapp — outbound WhatsApp messages via UltraMsg API.
 *
 * Configure in .env:
 *   ULTRAMSG_INSTANCE=instance12345
 *   ULTRAMSG_TOKEN=your_token_here
 *
 * If credentials are missing the class silently skips — no errors thrown.
 */
class Whatsapp {

    private static function send(string $phone, string $message): bool {
        $instance = saf_env('ULTRAMSG_INSTANCE', '');
        $token    = saf_env('ULTRAMSG_TOKEN', '');
        if(!$instance || !$token) return false;

        // Normalise number — strip everything except digits and leading +
        $phone = preg_replace('/[^\d+]/', '', $phone);
        if($phone && $phone[0] !== '+') $phone = '+'.$phone;
        if(strlen($phone) < 7) return false;

        $ch = curl_init("https://api.ultramsg.com/{$instance}/messages/chat");
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 12,
            CURLOPT_POSTFIELDS     => http_build_query([
                'token' => $token,
                'to'    => $phone,
                'body'  => $message,
            ]),
        ]);
        $res = curl_exec($ch);
        $err = curl_errno($ch);
        curl_close($ch);
        if($err) { error_log("[SAF WhatsApp] cURL error {$err}"); return false; }
        $decoded = json_decode($res, true);
        return isset($decoded['sent']) && $decoded['sent'] === 'true';
    }

    // ── Message templates ────────────────────────────────────────────────────

    public static function welcome(string $phone, string $name): bool {
        $appUrl = saf_env('APP_URL', 'http://localhost/Smart-Asset-Finder/');
        $msg = "👋 *Welcome to Smart Asset Finder, {$name}!*\n\n"
             . "Your account is ready. Here's how it works:\n\n"
             . "🔍 *Search* — Browse lost & found items in our database.\n"
             . "📝 *Report* — Lost something? Report it and our AI scans all found reports for a match instantly.\n"
             . "🏷️ *Tag it* — Order a SAF tag for your valuables. Any finder can scan it to reach you — no app needed.\n\n"
             . "Start here 👇\n{$appUrl}\n\n"
             . "_Smart Asset Finder — Find what you lost._";
        return self::send($phone, $msg);
    }

    public static function qrScanned(
        string $phone, string $ownerName, string $itemLabel,
        string $finderName, string $finderContact,
        string $location, string $finderMsg, string $dashUrl
    ): bool {
        $item = $itemLabel ?: 'your tagged item';
        $loc  = $location  ? "\n📍 *Location:* {$location}" : '';
        $note = $finderMsg ? "\n💬 *Message:* {$finderMsg}"  : '';
        $msg  = "🔔 *Your SAF tag was just scanned!*\n\n"
              . "Hi {$ownerName}, someone found *{$item}* and wants to return it.\n\n"
              . "👤 *Finder:* {$finderName}\n"
              . "📞 *Contact:* {$finderContact}"
              . $loc . $note . "\n\n"
              . "Respond here 👇\n{$dashUrl}";
        return self::send($phone, $msg);
    }

    public static function itemMatched(
        string $phone, string $ownerName,
        string $lostTitle, string $foundTitle, string $itemUrl
    ): bool {
        $msg = "🤖 *AI Match Found!*\n\n"
             . "Hi {$ownerName}, our system found a possible match for your lost item.\n\n"
             . "📦 *You reported lost:* {$lostTitle}\n"
             . "✅ *Possible match:* {$foundTitle}\n\n"
             . "View the item and submit a claim 👇\n{$itemUrl}";
        return self::send($phone, $msg);
    }

    public static function newClaim(
        string $phone, string $ownerName,
        string $itemTitle, string $claimantName, string $dashUrl
    ): bool {
        $msg = "📋 *Someone claimed your item!*\n\n"
             . "Hi {$ownerName}, *{$claimantName}* just submitted a claim for:\n\n"
             . "🏷️ *{$itemTitle}*\n\n"
             . "Review the claim and their proof of ownership 👇\n{$dashUrl}";
        return self::send($phone, $msg);
    }

    public static function claimApproved(
        string $phone, string $name, string $itemTitle, string $dashUrl
    ): bool {
        $msg = "✅ *Your claim was approved!*\n\n"
             . "Great news, {$name}! Your claim for *{$itemTitle}* has been approved.\n\n"
             . "Please contact the finder or admin to arrange the return of your item.\n\n"
             . "View details 👇\n{$dashUrl}";
        return self::send($phone, $msg);
    }

    public static function claimRejected(
        string $phone, string $name, string $itemTitle, string $dashUrl
    ): bool {
        $msg = "❌ *Claim not approved*\n\n"
             . "Hi {$name}, your claim for *{$itemTitle}* was not approved at this time.\n\n"
             . "If you have additional proof of ownership, please contact us.\n\n"
             . "View details 👇\n{$dashUrl}";
        return self::send($phone, $msg);
    }

    public static function broadcast(string $phone, string $name, string $message): bool {
        $body = str_replace('{{name}}', $name, $message);
        return self::send($phone, $body);
    }
}
