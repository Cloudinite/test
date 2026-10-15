<?php
/** POST /api/admin_change_password.php {current_password, new_password} */
require_once __DIR__ . '/helpers.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'error' => 'Method not allowed'], 405);
}
verify_csrf();

if (!rate_limit('admin_change_password', 6, 300)) {
    json_response(['success' => false, 'error' => 'Príliš mnoho pokusov. Skúste to neskôr.'], 429);
}

$data = get_json_body();
$current = (string)($data['current_password'] ?? '');
$new = (string)($data['new_password'] ?? '');

if ($current === '' || $new === '') {
    json_response(['success' => false, 'error' => 'Vyplňte obe polia.'], 422);
}
if (mb_strlen($new) < 8) {
    json_response(['success' => false, 'error' => 'Nové heslo musí mať aspoň 8 znakov.'], 422);
}

$pdo = get_pdo();
$stmt = $pdo->prepare('SELECT id, password_hash FROM admin_users WHERE id = ?');
$stmt->execute([$_SESSION['admin_id']]);
$user = $stmt->fetch();

if (!$user || !password_verify($current, $user['password_hash'])) {
    json_response(['success' => false, 'error' => 'Súčasné heslo je nesprávne.'], 401);
}

$newHash = password_hash($new, PASSWORD_DEFAULT);
$pdo->prepare('UPDATE admin_users SET password_hash = ? WHERE id = ?')->execute([$newHash, $user['id']]);

json_response(['success' => true]);
