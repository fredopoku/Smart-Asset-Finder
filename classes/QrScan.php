<?php
// QR scan location logging endpoint
// Called client-side when someone views an item page via a QR code scan
require_once __DIR__.'/../config.php';

header('Content-Type: application/json');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    exit(json_encode(['status' => 'error']));
}

$item_id = (int)($_POST['item_id'] ?? 0);
$lat     = isset($_POST['lat']) && is_numeric($_POST['lat']) ? round((float)$_POST['lat'], 7) : null;
$lng     = isset($_POST['lng']) && is_numeric($_POST['lng']) ? round((float)$_POST['lng'], 7) : null;
$label   = isset($_POST['label']) ? substr(trim($_POST['label']), 0, 255) : null;

if (!$item_id) {
    exit(json_encode(['status' => 'error', 'msg' => 'Invalid item']));
}

$ip = $_SERVER['HTTP_CF_CONNECTING_IP']
   ?? $_SERVER['HTTP_X_FORWARDED_FOR']
   ?? $_SERVER['REMOTE_ADDR']
   ?? null;
$ip = $ip ? substr($ip, 0, 45) : null;
$ua = substr($_SERVER['HTTP_USER_AGENT'] ?? '', 0, 255);

$stmt = $conn->prepare(
    "INSERT INTO qr_scans (item_id, lat, lng, location_label, ip_address, user_agent)
     VALUES (?, ?, ?, ?, ?, ?)"
);
$stmt->bind_param('iddsss', $item_id, $lat, $lng, $label, $ip, $ua);
$stmt->execute();
$stmt->close();

// Award 5 points to the scanner if they're logged in
if(isset($_SESSION['pub_userdata']) && !empty($_SESSION['pub_userdata']['id'])){
    $scanner_id = (int)$_SESSION['pub_userdata']['id'];
    award_points($scanner_id, 5, 'qr_scan_log', 'QR tag scanned and sighting reported', $item_id);
}

// Notify item owner if the item belongs to a registered user
$owner = $conn->prepare(
    "SELECT il.title, il.user_id, ru.firstname
     FROM item_list il
     LEFT JOIN registered_users ru ON ru.id = il.user_id
     WHERE il.id = ? LIMIT 1"
);
$owner->bind_param('i', $item_id);
$owner->execute();
$row = $owner->get_result()->fetch_assoc();
$owner->close();

if ($row && !empty($row['user_id'])) {
    $loc_info = ($lat && $lng)
        ? ($label ?: round($lat, 4).', '.round($lng, 4))
        : 'unknown location';

    $msg = 'Your item "<strong>'.htmlspecialchars($row['title']).'</strong>" QR tag was just scanned near <em>'
         . htmlspecialchars($loc_info) . '</em>. Someone may be trying to return it.';

    $link = base_url . '?page=items/view&id=' . $item_id;

    $notif = $conn->prepare(
        "INSERT INTO notifications (user_id, message, link, type, is_read, created_at)
         VALUES (?, ?, ?, 'info', 0, NOW())"
    );
    $notif->bind_param('iss', $row['user_id'], $msg, $link);
    $notif->execute();
    $notif->close();
}

exit(json_encode(['status' => 'success']));
