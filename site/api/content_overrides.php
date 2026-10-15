<?php
/**
 * GET /api/content_overrides.php
 * Public, read-only. Returns every Page-Editor override currently stored,
 * so the public site's i18n.js can merge them on top of the shipped
 * /i18n/{lang}.json defaults at render time.
 *
 * Response shape:
 * { success: true,
 *   text: { sk: { "about.founding_p1": "<b>...</b>" }, en: {...}, ... },
 *   images: { "home.hero_image": "/uploads/xxx.jpg" } }
 */
require_once __DIR__ . '/helpers.php';

$pdo = get_pdo();
$rows = $pdo->query('SELECT content_key, lang, value, type FROM content_overrides')->fetchAll();

$text = [];
$images = [];
foreach ($rows as $r) {
    if ($r['type'] === 'image') {
        $images[$r['content_key']] = $r['value'];
    } else {
        $lang = $r['lang'] ?: '*';
        if (!isset($text[$lang])) { $text[$lang] = []; }
        $text[$lang][$r['content_key']] = $r['value'];
    }
}

// Cache lightly at the edge/browser (content changes are infrequent, and the
// admin editor always bypasses cache via cache: 'no-store' on its own fetches).
header('Cache-Control: public, max-age=30');
json_response(['success' => true, 'text' => (object)$text, 'images' => (object)$images]);
