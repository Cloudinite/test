<?php
/**
 * DEV-ONLY router for PHP's built-in server (`php -S host:port -t docroot router.php`).
 * This file mimics the clean-URL mapping defined in .htaccess (mod_rewrite)
 * purely so this preview/sandbox environment - which uses `php -S` and does
 * NOT process .htaccess - can also serve /announcements, /about, etc.
 *
 * On real Apache/Websupport hosting this file is NEVER invoked; .htaccess
 * handles the exact same mapping there. Safe to leave in the upload (no
 * effect on Apache), can also be deleted after deploying if desired.
 */

$cleanMap = [
    '/announcements' => '/oznamy.html',
    '/activities' => '/aktivity.html',
    '/about' => '/o-nas.html',
    '/faq' => '/faq.html',
    '/contact' => '/kontakt.html',
    '/privacy' => '/ochrana-osobnych-udajov.html',
];

$legacyRedirects = [
    '/index.html' => '/',
    '/oznamy.html' => '/announcements',
    '/aktivity.html' => '/activities',
    '/o-nas.html' => '/about',
    '/vzdelavanie.html' => '/activities#vzdelavanie',
    '/education' => '/activities#vzdelavanie',
    '/faq.html' => '/faq',
    '/kontakt.html' => '/contact',
    '/ochrana-osobnych-udajov.html' => '/privacy',
];

$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$qs = parse_url($_SERVER['REQUEST_URI'], PHP_URL_QUERY);
$path = rtrim($uri, '/');
if ($path === '') { $path = '/'; }

if (isset($legacyRedirects[$path])) {
    // Keep any #fragment after the query string (e.g. /activities?x=1#vzdelavanie).
    $loc = explode('#', $legacyRedirects[$path], 2);
    header('Location: ' . $loc[0] . ($qs ? '?' . $qs : '') . (isset($loc[1]) ? '#' . $loc[1] : ''), true, 301);
    return true;
}

if (isset($cleanMap[$path])) {
    $target = __DIR__ . $cleanMap[$path];
    if (file_exists($target)) {
        header('Content-Type: text/html; charset=UTF-8');
        readfile($target);
        return true;
    }
}

return false; // real file (asset/.php/.html) or "/" - let the built-in server handle it normally
