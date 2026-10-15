<?php
require_once __DIR__ . '/helpers.php';
if (empty($_SESSION['admin_id'])) {
    json_response(['success' => true, 'authenticated' => false]);
}
json_response(['success' => true, 'authenticated' => true, 'username' => $_SESSION['admin_username'] ?? '', 'csrf_token' => csrf_token()]);
