<?php
/**
 * POST /api/admin_content.php — Page Editor mutations (admin only, CSRF).
 * Actions:
 *   save_text   { key, lang, value }  - upsert a text/HTML override
 *   revert_text { key, lang }         - delete override, fall back to i18n default
 *   save_image  { key, url }          - upsert an image override
 *   revert_image{ key }               - delete image override, fall back to default src
 */
require_once __DIR__ . '/helpers.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    json_response(['success' => false, 'error' => 'Method not allowed'], 405);
}
verify_csrf();

$data = get_json_body();
$action = $data['action'] ?? '';
$key = sanitize_str($data['key'] ?? '');

if ($key === '') {
    json_response(['success' => false, 'error' => 'Missing content key'], 422);
}

$pdo = get_pdo();

switch ($action) {
    case 'save_text': {
        $lang = sanitize_str($data['lang'] ?? '');
        $allowed = ['sk', 'en', 'ru', 'de', 'ar'];
        if (!in_array($lang, $allowed, true)) {
            json_response(['success' => false, 'error' => 'Invalid language'], 422);
        }
        $value = (string)($data['value'] ?? '');
        if (mb_strlen($value) > 20000) {
            json_response(['success' => false, 'error' => 'Content too long'], 422);
        }
        $stmt = $pdo->prepare(
            'INSERT INTO content_overrides (content_key, lang, value, type) VALUES (?, ?, ?, "text")
             ON DUPLICATE KEY UPDATE value = VALUES(value)'
        );
        $stmt->execute([$key, $lang, $value]);
        json_response(['success' => true]);
        break;
    }
    case 'revert_text': {
        $lang = sanitize_str($data['lang'] ?? '');
        $pdo->prepare('DELETE FROM content_overrides WHERE content_key = ? AND lang = ? AND type = "text"')
            ->execute([$key, $lang]);
        json_response(['success' => true]);
        break;
    }
    case 'save_image': {
        $url = sanitize_str($data['url'] ?? '');
        if ($url === '') { json_response(['success' => false, 'error' => 'Missing image url'], 422); }
        $stmt = $pdo->prepare(
            'INSERT INTO content_overrides (content_key, lang, value, type) VALUES (?, "*", ?, "image")
             ON DUPLICATE KEY UPDATE value = VALUES(value)'
        );
        $stmt->execute([$key, $url]);
        json_response(['success' => true, 'url' => $url]);
        break;
    }
    case 'revert_image': {
        $pdo->prepare('DELETE FROM content_overrides WHERE content_key = ? AND type = "image"')->execute([$key]);
        json_response(['success' => true]);
        break;
    }
    default:
        json_response(['success' => false, 'error' => 'Unknown action'], 422);
}
