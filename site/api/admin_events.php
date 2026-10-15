<?php
/**
 * Admin CRUD for events.
 * GET -> list all
 * POST {action: create|update|delete|toggle_cancel, ...}
 */
require_once __DIR__ . '/helpers.php';
require_admin();

$pdo = get_pdo();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $rows = $pdo->query("SELECT * FROM events ORDER BY start_dt DESC")->fetchAll();
    json_response(['success' => true, 'items' => $rows]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $data = get_json_body();
    $action = $data['action'] ?? '';

    if ($action === 'create' || $action === 'update') {
        $title = sanitize_str($data['title'] ?? '');
        $description = trim((string)($data['description'] ?? ''));
        $location = sanitize_str($data['location'] ?? '');
        $start_dt = sanitize_str($data['start_dt'] ?? '');
        $end_dt = sanitize_str($data['end_dt'] ?? '');
        $is_published = !empty($data['is_published']) ? 1 : 0;
        $is_cancelled = !empty($data['is_cancelled']) ? 1 : 0;
        $image = sanitize_str($data['image'] ?? '');

        if ($title === '' || $start_dt === '') { json_response(['success' => false, 'error' => 'Title and start date/time are required'], 422); }
        $start_ts = strtotime($start_dt);
        if ($start_ts === false) { json_response(['success' => false, 'error' => 'Invalid start date/time'], 422); }
        $start_sql = date('Y-m-d H:i:s', $start_ts);
        $end_sql = null;
        if ($end_dt !== '') {
            $end_ts = strtotime($end_dt);
            if ($end_ts !== false) { $end_sql = date('Y-m-d H:i:s', $end_ts); }
        }

        if ($action === 'create') {
            $stmt = $pdo->prepare("INSERT INTO events (title, description, location, start_dt, end_dt, is_cancelled, is_published, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
            $stmt->execute([$title, $description, $location, $start_sql, $end_sql, $is_cancelled, $is_published, $image ?: null]);
            json_response(['success' => true, 'id' => (int)$pdo->lastInsertId()]);
        } else {
            $id = intval($data['id'] ?? 0);
            if (!$id) { json_response(['success' => false, 'error' => 'Missing id'], 422); }
            $stmt = $pdo->prepare("UPDATE events SET title=?, description=?, location=?, start_dt=?, end_dt=?, is_cancelled=?, is_published=?, image=? WHERE id=?");
            $stmt->execute([$title, $description, $location, $start_sql, $end_sql, $is_cancelled, $is_published, $image ?: null, $id]);
            json_response(['success' => true]);
        }
    }

    if ($action === 'delete') {
        $id = intval($data['id'] ?? 0);
        if (!$id) { json_response(['success' => false, 'error' => 'Missing id'], 422); }
        $pdo->prepare("DELETE FROM events WHERE id = ?")->execute([$id]);
        json_response(['success' => true]);
    }

    json_response(['success' => false, 'error' => 'Unknown action'], 400);
}

json_response(['success' => false, 'error' => 'Method not allowed'], 405);
