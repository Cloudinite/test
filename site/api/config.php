<?php
/**
 * ============================================================
 * KMSSK.SK - CONFIGURATION
 * ------------------------------------------------------------
 * When deploying to Websupport shared hosting, edit the 4
 * database constants below with the credentials you create in
 * the Websupport hosting control panel (Databases -> MySQL).
 * ============================================================
 */

define('DB_HOST', getenv('KMSSK_DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('KMSSK_DB_PORT') ?: '3317');
define('DB_NAME', getenv('KMSSK_DB_NAME') ?: 'kmssk');
define('DB_USER', getenv('KMSSK_DB_USER') ?: 'kmssk');
define('DB_PASS', getenv('KMSSK_DB_PASS') ?: 'Kmssk.sk1');

define('SITE_URL', getenv('KMSSK_SITE_URL') ?: 'https://kmssk.sk');
define('CONTACT_EMAIL', 'kms@kmssk.sk');
define('MAIL_FROM', 'noreply@kmssk.sk');

/**
 * ------------------------------------------------------------
 * SMTP (contact form email delivery)
 * ------------------------------------------------------------
 * Fill these in directly to have the contact form deliver real
 * emails instead of falling back to PHP's built-in mail(). Leave
 * SMTP_HOST empty to keep using mail() (no setup needed, but less
 * reliable on shared hosting).
 *
 * For a Websupport webmail mailbox:
 *   SMTP_HOST     -> e.g. smtp.m1.websupport.sk (see Websupport ->
 *                    E-maily -> your mailbox -> "Nastavenie e-mailového klienta")
 *   SMTP_PORT     -> 465 (SSL, most common) or 587 (STARTTLS)
 *   SMTP_USERNAME -> the FULL mailbox address, e.g. kms@kmssk.sk
 *   SMTP_PASSWORD -> that mailbox's password
 *
 * For Gmail:
 *   SMTP_HOST     -> smtp.gmail.com
 *   SMTP_PORT     -> 587
 *   SMTP_USERNAME -> your.address@gmail.com
 *   SMTP_PASSWORD -> a 16-character Google "App Password"
 *                    (Google Account -> Security -> 2-Step Verification -> App passwords),
 *                    NOT your normal Google account password.
 *
 * The encryption method (SSL vs STARTTLS) is derived automatically
 * from SMTP_PORT - you never need to configure it separately.
 */
define('SMTP_HOST', getenv('KMSSK_SMTP_HOST') ?: '');
define('SMTP_PORT', getenv('KMSSK_SMTP_PORT') ?: '465');
define('SMTP_USERNAME', getenv('KMSSK_SMTP_USERNAME') ?: '');
define('SMTP_PASSWORD', getenv('KMSSK_SMTP_PASSWORD') ?: '');
define('SMTP_FROM_EMAIL', getenv('KMSSK_SMTP_FROM_EMAIL') ?: '');
define('SMTP_FROM_NAME', getenv('KMSSK_SMTP_FROM_NAME') ?: 'KMS webova stranka');
define('SMTP_DELIVERY_EMAIL', getenv('KMSSK_SMTP_DELIVERY_EMAIL') ?: CONTACT_EMAIL);

define('UPLOAD_DIR', __DIR__ . '/../uploads/');
define('UPLOAD_URL', '/uploads/');

define('MAX_UPLOAD_BYTES', 5 * 1024 * 1024); // 5MB

if (session_status() === PHP_SESSION_NONE) {
    session_set_cookie_params([
        'lifetime' => 60 * 60 * 24 * 7,
        'path' => '/',
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_name('kmssk_admin_sess');
    session_start();
}

date_default_timezone_set('Europe/Bratislava');
error_reporting(E_ALL & ~E_DEPRECATED & ~E_NOTICE & ~E_WARNING);
ini_set('display_errors', '0');
