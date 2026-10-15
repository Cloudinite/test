<?php
/**
 * Auth guard for webadmin pages. Include at the very top of every
 * protected page. Redirects to index.php (login) if not authenticated.
 */
require_once __DIR__ . '/../../api/config.php';
require_once __DIR__ . '/../../api/db.php';

if (empty($_SESSION['admin_id'])) {
    header('Location: index.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$CSRF_TOKEN = $_SESSION['csrf_token'];
$ADMIN_USERNAME = $_SESSION['admin_username'] ?? 'admin';
