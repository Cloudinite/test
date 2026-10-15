<?php
/** GET /api/announcements_popup.php -> latest published popup-worthy announcement */
require_once __DIR__ . '/helpers.php';

$pdo = get_pdo();
$stmt = $pdo->query("SELECT id, title, body, image, published_at, updated_at FROM announcements WHERE is_published = 1 AND show_popup = 1 ORDER BY published_at DESC, id DESC LIMIT 1");
$row = $stmt->fetch();

if (!$row) {
    json_response(['success' => true, 'announcement' => null]);
}

$version = md5($row['id'] . '|' . $row['updated_at']);
$row['popup_version'] = $version;

json_response(['success' => true, 'announcement' => $row]);
