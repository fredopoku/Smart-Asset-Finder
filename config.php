<?php
ob_start();
ini_set('date.timezone', 'UTC');
date_default_timezone_set('UTC');

require_once('initialize.php'); // defines saf_env(), APP_ENV, base_url

// ── Session hardening (must happen before session_start) ─────────────────────
session_set_cookie_params([
    'lifetime' => 0,
    'path'     => '/',
    'secure'   => APP_ENV === 'production',
    'httponly' => true,
    'samesite' => 'Lax',
]);
ini_set('session.use_strict_mode', 1);
session_start();

require_once('classes/DBConnection.php');
require_once('classes/SystemSettings.php');
$db = new DBConnection;
$conn = $db->conn;

// ── Session helper (explicit cookie update for PHP/Apache builds where
//    session_regenerate_id() silently omits the Set-Cookie header) ────────────
function safe_session_regenerate(): void {
    session_regenerate_id(true);
    $p = session_get_cookie_params();
    setcookie(session_name(), session_id(), [
        'expires'  => $p['lifetime'] ? time() + $p['lifetime'] : 0,
        'path'     => $p['path'],
        'domain'   => $p['domain'],
        'secure'   => $p['secure'],
        'httponly' => $p['httponly'],
        'samesite' => $p['samesite'],
    ]);
}

// ── CSRF ─────────────────────────────────────────────────────────────────────
function csrf_token(): string {
    if(empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}
csrf_token(); // generate for every page load

function csrf_check(): void {
    if($_SERVER['REQUEST_METHOD'] !== 'POST') return;
    $token = $_SERVER['HTTP_X_CSRF_TOKEN'] ?? $_POST['csrf_token'] ?? '';
    if(empty($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $token)) {
        http_response_code(403);
        exit(json_encode(['status' => 'failed', 'msg' => 'Security token mismatch. Please refresh the page and try again.']));
    }
}

// ── Rate Limiting ─────────────────────────────────────────────────────────────
function rate_limit(string $action, string $identifier, int $max = 10, int $window_sec = 300): bool {
    global $conn;
    $key = hash('sha256', $action.'|'.$identifier);
    if(rand(1, 20) === 1) {
        $conn->query("DELETE FROM rate_limits WHERE last_attempt < DATE_SUB(NOW(), INTERVAL ".($window_sec * 2)." SECOND)");
    }
    $stmt = $conn->prepare("SELECT attempts, blocked_until FROM rate_limits WHERE key_hash=? LIMIT 1");
    $stmt->bind_param('s', $key);
    $stmt->execute();
    $row = $stmt->get_result()->fetch_assoc();
    $stmt->close();
    if($row) {
        if($row['blocked_until'] && strtotime($row['blocked_until']) > time()) return false;
        $new_attempts = (int)$row['attempts'] + 1;
        $blocked_until = $new_attempts > $max ? date('Y-m-d H:i:s', time() + $window_sec) : null;
        $stmt = $conn->prepare("UPDATE rate_limits SET attempts=?, blocked_until=?, last_attempt=NOW() WHERE key_hash=?");
        $stmt->bind_param('iss', $new_attempts, $blocked_until, $key);
        $stmt->execute();
        $stmt->close();
        return $new_attempts <= $max;
    }
    $stmt = $conn->prepare("INSERT INTO rate_limits (key_hash, action, attempts, last_attempt) VALUES (?,?,1,NOW())");
    $stmt->bind_param('ss', $key, $action);
    $stmt->execute();
    $stmt->close();
    return true;
}

// ── QR Tag Generator ─────────────────────────────────────────────────────────
function generate_qr_tags(int $user_id, int $count = 3): void {
    global $conn;
    $stmt = $conn->prepare("INSERT IGNORE INTO qr_tags (user_id, tag_code) VALUES (?, ?)");
    for($i = 0; $i < $count; $i++){
        do {
            $code = strtoupper(bin2hex(random_bytes(5))); // 10-char hex code
            $chk  = $conn->query("SELECT id FROM qr_tags WHERE tag_code='$code' LIMIT 1");
        } while($chk && $chk->num_rows > 0);
        $stmt->bind_param('is', $user_id, $code);
        $stmt->execute();
    }
    $stmt->close();
}

// ── Auth guards ───────────────────────────────────────────────────────────────
function require_admin(): void {
    if(empty($_SESSION['userdata']) || (int)$_SESSION['userdata']['login_type'] !== 1){
        http_response_code(403);
        exit(json_encode(['status'=>'failed','msg'=>'Unauthorized']));
    }
}

// ── Helpers ───────────────────────────────────────────────────────────────────
function redirect($url = '') {
    if(!empty($url))
        echo '<script>location.href="'.base_url.$url.'"</script>';
}
function validate_image($file) {
    global $_settings;
    if(!empty($file)) {
        $ex   = explode("?", $file);
        $file = $ex[0];
        $ts   = isset($ex[1]) ? "?".$ex[1] : '';
        if(is_file(base_app.$file)) {
            return base_url.$file.$ts;
        } else {
            return base_url.($_settings->info('logo'));
        }
    } else {
        return base_url.($_settings->info('logo'));
    }
}
function format_num($number = '', $decimal = '') {
    if(is_numeric($number)) {
        $ex     = explode(".", $number);
        $decLen = isset($ex[1]) ? strlen($ex[1]) : 0;
        return is_numeric($decimal) ? number_format($number, $decimal) : number_format($number, $decLen);
    }
    return "Invalid Input";
}
function isMobileDevice() {
    $aMobileUA = [
        '/iphone/i'     => 'iPhone',
        '/ipod/i'       => 'iPod',
        '/ipad/i'       => 'iPad',
        '/android/i'    => 'Android',
        '/blackberry/i' => 'BlackBerry',
        '/webos/i'      => 'Mobile',
    ];
    foreach($aMobileUA as $sMobileKey => $sMobileOS) {
        if(preg_match($sMobileKey, $_SERVER['HTTP_USER_AGENT'] ?? '')) return true;
    }
    return false;
}
// ── Rewards: award points and check badge thresholds ─────────────────────────
function award_points(int $user_id, int $points, string $action, string $description = '', int $ref_id = 0): void {
    global $conn;
    if($user_id <= 0 || $points === 0) return;

    // Log the transaction
    $stmt = $conn->prepare(
        "INSERT INTO point_transactions (user_id, points, action, description, reference_id)
         VALUES (?, ?, ?, ?, ?)"
    );
    $ref = $ref_id ?: null;
    $stmt->bind_param('iissi', $user_id, $points, $action, $description, $ref);
    $stmt->execute();
    $stmt->close();

    // Update running balance (using GREATEST to prevent going below 0 on deductions)
    if($points > 0) {
        $upd = $conn->prepare("UPDATE registered_users SET points = points + ? WHERE id = ?");
    } else {
        $abs = abs($points);
        $upd = $conn->prepare("UPDATE registered_users SET points = GREATEST(0, CAST(points AS SIGNED) - $abs) WHERE id = ?");
        $upd->bind_param('i', $user_id);
        $upd->execute();
        $upd->close();
        check_badges($user_id);
        return;
    }
    $upd->bind_param('ii', $points, $user_id);
    $upd->execute();
    $upd->close();

    // Notify user about points earned
    $notif = $conn->prepare(
        "INSERT INTO notifications (user_id, message, link, type, is_read, created_at)
         VALUES (?, ?, '?page=rewards', 'success', 0, NOW())"
    );
    $msg = "+{$points} points — {$description}";
    $notif->bind_param('is', $user_id, $msg);
    $notif->execute();
    $notif->close();

    check_badges($user_id);
}

function check_badges(int $user_id): void {
    global $conn;

    // Get user stats needed for badge checks
    $stats = $conn->prepare(
        "SELECT
           ru.points,
           ru.email_verified,
           (SELECT COUNT(*) FROM point_transactions WHERE user_id=ru.id AND action='register') as is_registered,
           (SELECT COUNT(*) FROM item_list WHERE user_id=ru.id) as item_count,
           (SELECT COUNT(*) FROM point_transactions WHERE user_id=ru.id AND action='item_returned') as returns,
           (SELECT COUNT(*) FROM point_transactions WHERE user_id=ru.id AND action='qr_scan_log') as scans,
           (SELECT COUNT(*) FROM point_transactions WHERE user_id=ru.id AND action='referral') as referrals
         FROM registered_users ru WHERE ru.id=? LIMIT 1"
    );
    $stats->bind_param('i', $user_id);
    $stats->execute();
    $s = $stats->get_result()->fetch_assoc();
    $stats->close();
    if(!$s) return;

    // Already-earned badge IDs
    $earned = [];
    $eq = $conn->prepare("SELECT badge_id FROM user_badges WHERE user_id=?");
    $eq->bind_param('i', $user_id);
    $eq->execute();
    $er = $eq->get_result();
    while($row = $er->fetch_assoc()) $earned[] = (int)$row['badge_id'];
    $eq->close();

    // Badge slug → condition
    $badge_rules = [
        'verified'        => (int)$s['email_verified'] === 1,
        'first_report'    => (int)$s['item_count'] >= 1,
        'good_samaritan'  => (int)$s['returns'] >= 1,
        'guardian'        => (int)$s['returns'] >= 5,
        'community_hero'  => (int)$s['returns'] >= 20,
        'tagger'          => (int)$s['item_count'] >= 5,
        'scanner'         => (int)$s['scans'] >= 10,
        'referrer'        => (int)$s['referrals'] >= 3,
    ];

    // Fetch all badge IDs by slug in one query
    $slugs    = implode("','", array_keys($badge_rules));
    $all_badges = $conn->query("SELECT id, slug FROM badges WHERE slug IN ('$slugs')")->fetch_all(MYSQLI_ASSOC);

    $ins = $conn->prepare("INSERT IGNORE INTO user_badges (user_id, badge_id) VALUES (?, ?)");
    $noti = $conn->prepare(
        "INSERT INTO notifications (user_id, message, link, type, is_read, created_at)
         VALUES (?, ?, '?page=rewards', 'info', 0, NOW())"
    );

    foreach($all_badges as $b) {
        $bid = (int)$b['id'];
        if(!in_array($bid, $earned) && ($badge_rules[$b['slug']] ?? false)) {
            $ins->bind_param('ii', $user_id, $bid);
            $ins->execute();
            if($ins->affected_rows > 0) {
                // Notify about new badge
                $bmsg = "You earned the \"{$b['slug']}\" badge! View your rewards.";
                $noti->bind_param('is', $user_id, $bmsg);
                $noti->execute();
            }
        }
    }
    $ins->close();
    $noti->close();
}

ob_end_flush();
?>
