<?php
/** GET /api/announcements_list.php?audience=public|members|all&limit=10 */
require_once __DIR__ . '/helpers.php';

$audience = $_GET['audience'] ?? 'public';
$limit = min(50, max(1, intval($_GET['limit'] ?? 20)));

$pdo = get_pdo();
if ($audience === 'all') {
    $sql = "SELECT id, title, body, audience, image, published_at FROM announcements WHERE is_published = 1 ORDER BY published_at DESC, id DESC LIMIT :lim";
    $stmt = $pdo->prepare($sql);
} else {
    $sql = "SELECT id, title, body, audience, image, published_at FROM announcements WHERE is_published = 1 AND audience = :aud ORDER BY published_at DESC, id DESC LIMIT :lim";
    $stmt = $pdo->prepare($sql);
    $stmt->bindValue(':aud', $audience === 'members' ? 'members' : 'public');
}
$stmt->bindValue(':lim', $limit, PDO::PARAM_INT);
$stmt->execute();
$rows = $stmt->fetchAll();

json_response(['success' => true, 'items' => $rows]);
