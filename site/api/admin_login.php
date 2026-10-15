<?php
/** POST /api/admin_login.php {username, password} */
require_once __DIR__ . '/helpers.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'error' => 'Method not allowed'], 405);
}

if (!rate_limit('admin_login', 10, 300)) {
    json_response(['success' => false, 'error' => 'Too many login attempts. Try again later.'], 429);
}

$data = get_json_body();
$username = sanitize_str($data['username'] ?? '');
$password = (string)($data['password'] ?? '');

if ($username === '' || $password === '') {
    json_response(['success' => false, 'error' => 'Missing credentials'], 422);
}

$pdo = get_pdo();
$stmt = $pdo->prepare("SELECT id, username, password_hash FROM admin_users WHERE username = ?");
$stmt->execute([$username]);
$user = $stmt->fetch();

if (!$user || !password_verify($password, $user['password_hash'])) {
    json_response(['success' => false, 'error' => 'Nesprávne prihlasovacie údaje'], 401);
}

session_regenerate_id(true);
$_SESSION['admin_id'] = $user['id'];
$_SESSION['admin_username'] = $user['username'];

json_response(['success' => true, 'username' => $user['username'], 'csrf_token' => csrf_token()]);
