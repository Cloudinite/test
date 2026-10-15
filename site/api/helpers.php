<?php
require_once __DIR__ . '/db.php';

header('Content-Type: application/json; charset=utf-8');
header('X-Content-Type-Options: nosniff');

// Guarantee the client always receives valid JSON, even if an uncaught
// fatal error happens somewhere downstream (never leave an empty/broken
// response body that breaks response.json() on the frontend).
ob_start();
register_shutdown_function(function () {
    $error = error_get_last();
    $fatalTypes = [E_ERROR, E_PARSE, E_CORE_ERROR, E_COMPILE_ERROR, E_USER_ERROR];
    if ($error && in_array($error['type'], $fatalTypes, true)) {
        $logLine = '[' . date('Y-m-d H:i:s') . '] FATAL in ' . ($error['file'] ?? '?') . ':' . ($error['line'] ?? '?') . ' - ' . $error['message'];
        error_log($logLine);
        @file_put_contents(__DIR__ . '/debug.log', $logLine . "\n", FILE_APPEND);
        while (ob_get_level() > 0) { ob_end_clean(); }
        if (!headers_sent()) {
            http_response_code(500);
            header('Content-Type: application/json; charset=utf-8');
        }
        echo json_encode(['success' => false, 'error' => 'Internal server error. Please try again.']);
    } else {
        if (ob_get_level() > 0) { ob_end_flush(); }
    }
});

function json_response($data, $code = 200) {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
    exit;
}

function get_json_body() {
    $raw = file_get_contents('php://input');
    $data = json_decode($raw, true);
    return is_array($data) ? $data : [];
}

function require_admin() {
    if (empty($_SESSION['admin_id'])) {
        json_response(['success' => false, 'error' => 'Unauthorized'], 401);
    }
}

function csrf_token() {
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

function verify_csrf() {
    $headers = function_exists('getallheaders') ? getallheaders() : [];
    $normalized = [];
    foreach ($headers as $k => $v) { $normalized[strtolower($k)] = $v; }
    $token = $normalized['x-csrf-token'] ?? ($_POST['csrf_token'] ?? '');
    if (empty($_SESSION['csrf_token']) || !$token || !hash_equals($_SESSION['csrf_token'], $token)) {
        json_response(['success' => false, 'error' => 'Invalid or missing CSRF token'], 403);
    }
}

function sanitize_str($v) {
    return trim(strip_tags((string)$v));
}

function client_ip() {
    return $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';
}

function rate_limit($key, $max_attempts = 8, $window_secs = 300) {
    if (empty($_SESSION['rl'])) { $_SESSION['rl'] = []; }
    $now = time();
    $bucket = $_SESSION['rl'][$key] ?? ['count' => 0, 'start' => $now];
    if ($now - $bucket['start'] > $window_secs) {
        $bucket = ['count' => 0, 'start' => $now];
    }
    $bucket['count']++;
    $_SESSION['rl'][$key] = $bucket;
    return $bucket['count'] <= $max_attempts;
}

/** Read a single setting value from the `settings` key/value table. */
function get_setting($key, $default = null) {
    $pdo = get_pdo();
    $stmt = $pdo->prepare('SELECT setting_value FROM settings WHERE setting_key = ?');
    $stmt->execute([$key]);
    $row = $stmt->fetch();
    return $row ? $row['setting_value'] : $default;
}

/** Upsert a single setting value. */
function set_setting($key, $value) {
    $pdo = get_pdo();
    $stmt = $pdo->prepare(
        'INSERT INTO settings (setting_key, setting_value) VALUES (?, ?)
         ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)'
    );
    $stmt->execute([$key, $value]);
}

/**
 * Map an SMTP port to the correct transport encryption.
 * 465 = implicit SSL/TLS (wrap socket in TLS immediately).
 * 587 = STARTTLS (plain connect, then upgrade).
 * 25  = usually unencrypted.
 * The Nastavenia UI only lets the admin pick a port (with the encryption
 * type shown in the option label), so the backend always derives the
 * correct encryption from the port instead of trusting a separately
 * submitted value - this prevents a mismatched port/encryption pair
 * (e.g. port 465 sent with STARTTLS) from silently breaking delivery.
 */
function smtp_encryption_for_port($port) {
    $port = (int)$port;
    if ($port === 465) { return 'ssl'; }
    if ($port === 25) { return 'none'; }
    return 'tls'; // 587 and any other/custom port default to STARTTLS
}

/** Read several settings at once as an associative array. */
function get_settings(array $keys) {
    $pdo = get_pdo();
    $placeholders = implode(',', array_fill(0, count($keys), '?'));
    $stmt = $pdo->prepare("SELECT setting_key, setting_value FROM settings WHERE setting_key IN ($placeholders)");
    $stmt->execute($keys);
    $out = [];
    foreach ($stmt->fetchAll() as $row) { $out[$row['setting_key']] = $row['setting_value']; }
    return $out;
}
