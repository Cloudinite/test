<?php
/**
 * Admin CRUD for announcements.
 * GET  -> list all (incl unpublished)
 * POST {action: create|update|delete|toggle_popup|toggle_publish, ...}
 */
require_once __DIR__ . '/helpers.php';
require_admin();

$pdo = get_pdo();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $rows = $pdo->query("SELECT * FROM announcements ORDER BY created_at DESC")->fetchAll();
    json_response(['success' => true, 'items' => $rows]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $data = get_json_body();
    $action = $data['action'] ?? '';

    if ($action === 'create') {
        $title = sanitize_str($data['title'] ?? '');
        $body = trim((string)($data['body'] ?? ''));
        $audience = ($data['audience'] ?? 'public') === 'members' ? 'members' : 'public';
        $is_published = !empty($data['is_published']) ? 1 : 0;
        $show_popup = !empty($data['show_popup']) ? 1 : 0;
        $image = sanitize_str($data['image'] ?? '');
        if ($title === '' || $body === '') { json_response(['success' => false, 'error' => 'Title and body are required'], 422); }
        $published_at = $is_published ? date('Y-m-d H:i:s') : null;
        $stmt = $pdo->prepare("INSERT INTO announcements (title, body, audience, is_published, show_popup, image, published_at) VALUES (?, ?, ?, ?, ?, ?, ?)");
        $stmt->execute([$title, $body, $audience, $is_published, $show_popup, $image ?: null, $published_at]);
        json_response(['success' => true, 'id' => (int)$pdo->lastInsertId()]);
    }

    if ($action === 'update') {
        $id = intval($data['id'] ?? 0);
        if (!$id) { json_response(['success' => false, 'error' => 'Missing id'], 422); }
        $title = sanitize_str($data['title'] ?? '');
        $body = trim((string)($data['body'] ?? ''));
        $audience = ($data['audience'] ?? 'public') === 'members' ? 'members' : 'public';
        $is_published = !empty($data['is_published']) ? 1 : 0;
        $show_popup = !empty($data['show_popup']) ? 1 : 0;
        $image = sanitize_str($data['image'] ?? '');
        $cur = $pdo->prepare("SELECT is_published, published_at FROM announcements WHERE id = ?");
        $cur->execute([$id]);
        $row = $cur->fetch();
        if (!$row) { json_response(['success' => false, 'error' => 'Not found'], 404); }
        $published_at = $row['published_at'];
        if ($is_published && !$row['is_published']) { $published_at = date('Y-m-d H:i:s'); }
        $stmt = $pdo->prepare("UPDATE announcements SET title=?, body=?, audience=?, is_published=?, show_popup=?, image=?, published_at=? WHERE id=?");
        $stmt->execute([$title, $body, $audience, $is_published, $show_popup, $image ?: null, $published_at, $id]);
        json_response(['success' => true]);
    }

    if ($action === 'delete') {
        $id = intval($data['id'] ?? 0);
        if (!$id) { json_response(['success' => false, 'error' => 'Missing id'], 422); }
        $pdo->prepare("DELETE FROM announcements WHERE id = ?")->execute([$id]);
        json_response(['success' => true]);
    }

    json_response(['success' => false, 'error' => 'Unknown action'], 400);
}

json_response(['success' => false, 'error' => 'Method not allowed'], 405);
