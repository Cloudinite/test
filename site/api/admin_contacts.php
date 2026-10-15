<?php
/**
 * Admin viewer for contact form submissions.
 * GET -> list (optionally ?archived=1)
 * POST {action: archive|unarchive|delete, id}
 */
require_once __DIR__ . '/helpers.php';
require_admin();

$pdo = get_pdo();

if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $rows = $pdo->query("SELECT * FROM contact_submissions ORDER BY created_at DESC")->fetchAll();
    json_response(['success' => true, 'items' => $rows]);
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    verify_csrf();
    $data = get_json_body();
    $action = $data['action'] ?? '';
    $id = intval($data['id'] ?? 0);
    if (!$id) { json_response(['success' => false, 'error' => 'Missing id'], 422); }

    if ($action === 'archive') {
        $pdo->prepare("UPDATE contact_submissions SET is_archived = 1 WHERE id = ?")->execute([$id]);
        json_response(['success' => true]);
    }
    if ($action === 'unarchive') {
        $pdo->prepare("UPDATE contact_submissions SET is_archived = 0 WHERE id = ?")->execute([$id]);
        json_response(['success' => true]);
    }
    if ($action === 'delete') {
        $pdo->prepare("DELETE FROM contact_submissions WHERE id = ?")->execute([$id]);
        json_response(['success' => true]);
    }

    json_response(['success' => false, 'error' => 'Unknown action'], 400);
}

json_response(['success' => false, 'error' => 'Method not allowed'], 405);
