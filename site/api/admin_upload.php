<?php
/** POST multipart/form-data admin_upload.php with field 'file' -> saves to /uploads, returns URL */
require_once __DIR__ . '/helpers.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'error' => 'Method not allowed'], 405);
}
verify_csrf();

if (empty($_FILES['file'])) {
    json_response(['success' => false, 'error' => 'No file uploaded'], 422);
}

$file = $_FILES['file'];
if ($file['error'] !== UPLOAD_ERR_OK) {
    json_response(['success' => false, 'error' => 'Upload error code ' . $file['error']], 422);
}
if ($file['size'] > MAX_UPLOAD_BYTES) {
    json_response(['success' => false, 'error' => 'File too large (max 5MB)'], 422);
}

$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);

$allowed = [
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'image/gif' => 'gif',
];
if (!isset($allowed[$mime])) {
    json_response(['success' => false, 'error' => 'Only JPG, PNG, WEBP or GIF images are allowed'], 422);
}

if (!is_dir(UPLOAD_DIR)) { mkdir(UPLOAD_DIR, 0755, true); }

$ext = $allowed[$mime];
$name = bin2hex(random_bytes(12)) . '.' . $ext;
$dest = UPLOAD_DIR . $name;

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    json_response(['success' => false, 'error' => 'Failed to save file'], 500);
}

json_response(['success' => true, 'url' => UPLOAD_URL . $name]);
