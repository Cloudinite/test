# KMSSK.SK — Deployment Guide (Websupport shared hosting)

This is a pure static **HTML / CSS / Vanilla JS** website with lightweight **PHP + MySQL**
endpoints under `/api/`. There is **no Node.js, no React, no build step** — everything in
this folder can be uploaded as-is to a standard Apache/PHP/MySQL shared hosting plan
(e.g. Websupport).

## 1. Upload the files

Upload the **entire contents** of this folder (not the folder itself) to your hosting
account's web root (usually `www/` or `public_html/`) via FTP, SFTP or the Websupport
File Manager.

## 2. Create a MySQL database

In the Websupport hosting administration panel:
1. Go to **Databases → MySQL databases** and create a new database.
2. Create a database user and assign it full privileges on that database.
3. Note down: **database host**, **port**, **database name**, **username**, **password**.

## 3. Configure database credentials

Open `api/config.php` and edit the 5 constants below with the credentials from step 2
(the site already ships with the current production values as defaults, so if those are
still correct you can skip this step entirely):

```php
define('DB_HOST', getenv('KMSSK_DB_HOST') ?: 'localhost');
define('DB_PORT', getenv('KMSSK_DB_PORT') ?: '3317');
define('DB_NAME', getenv('KMSSK_DB_NAME') ?: 'kmssk');
define('DB_USER', getenv('KMSSK_DB_USER') ?: 'kmssk');
define('DB_PASS', getenv('KMSSK_DB_PASS') ?: 'Kmssk.sk1');
```

Simply replace the fallback value after `?:` with your real host/port/name/user/password.
Note: `db.php` automatically treats the host `localhost` as a real TCP connection to
`127.0.0.1` on the configured port (rather than the Unix-socket default), so your custom
port is always respected even when the host is set to `localhost`.

## 4. Database tables + admin account — fully automatic, no SSH/phpMyAdmin needed

**You do not need to run any SQL manually.** The very first time any page or `/api/`
endpoint is opened after upload, the site automatically:

1. Detects that the tables don't exist yet and creates all of them (from `api/schema.sql`).
2. Creates the `admin` account with a random 12-character password.
3. Writes that one-time password to `api/admin_credentials.log` (this file is blocked
   from direct web access by `.htaccess`, but you can open it from the Websupport
   **File Manager**).
4. Seeds one starter announcement and a few sample events so the site isn't empty.

**To get your admin password:**
1. Upload the files and open the homepage once in a browser (this triggers the install).
2. In the Websupport File Manager, open `api/admin_credentials.log` and copy the password.
3. Log in at `/webadmin/` with username `admin` and that password.
4. Immediately change the password from the dashboard, then delete `admin_credentials.log`.

If you ever need to re-run this manually instead (e.g. via SSH), `api/seed.php` still
works the same way as before:

```bash
cd api
KMSSK_ADMIN_PASS="choose-a-strong-password" php seed.php
```

## 5. Contact form emails

The contact form (`kontakt.html`) saves every submission to the `contact_submissions`
MySQL table (visible in `/webadmin/contacts.php`) **and** attempts to deliver an email
notification. By default it uses PHP's built-in `mail()` to `kms@kmssk.sk`. For reliable
delivery to a real inbox (Websupport webmail, Gmail, etc.), open **`api/config.php`**
and fill in the SMTP block:

```php
define('SMTP_HOST', getenv('KMSSK_SMTP_HOST') ?: '');           // e.g. smtp.m1.websupport.sk
define('SMTP_PORT', getenv('KMSSK_SMTP_PORT') ?: '465');        // 465 (SSL) or 587 (STARTTLS)
define('SMTP_USERNAME', getenv('KMSSK_SMTP_USERNAME') ?: '');   // full mailbox address, e.g. kms@kmssk.sk
define('SMTP_PASSWORD', getenv('KMSSK_SMTP_PASSWORD') ?: '');   // that mailbox's password
define('SMTP_FROM_EMAIL', getenv('KMSSK_SMTP_FROM_EMAIL') ?: ''); // usually same as username
define('SMTP_FROM_NAME', getenv('KMSSK_SMTP_FROM_NAME') ?: 'KMS webova stranka');
define('SMTP_DELIVERY_EMAIL', getenv('KMSSK_SMTP_DELIVERY_EMAIL') ?: CONTACT_EMAIL);
```

Replace the empty `''` fallback values with your real host/username/password (find your
Websupport mailbox's SMTP host under **Websupport → E-maily → your mailbox →
"Nastavenie e-mailového klienta"**). For Gmail, `SMTP_USERNAME` is your Gmail address and
`SMTP_PASSWORD` must be a 16-character **App Password** (Google Account → Security →
2-Step Verification → App passwords) — not your normal Gmail password.

Leave `SMTP_HOST` empty to keep using PHP's `mail()` (no setup, but less reliable). Once
`SMTP_HOST` is filled in, the contact form automatically switches to SMTP, sent via the
bundled **PHPMailer** library (`api/lib/PHPMailer/` — plain files, no Composer needed,
already included in this zip) — the correct encryption (SSL vs STARTTLS) is derived
automatically from `SMTP_PORT`, no extra config needed.

To verify delivery, simply submit the real contact form on `/contact` and check the
destination inbox. If it doesn't arrive, open `/webadmin/contacts.php` — every submission
that failed to send shows a red **"Neodoslaný"** badge; hover over it to see the exact
SMTP error (wrong password, auth disabled, wrong host, etc.) without needing server log
access. There is no separate "send test email" button anymore — SMTP is configured only
through `config.php`, and the real contact form is the test.

## 6. Embedding the activities calendar on ayan.sk

Give the ayan.sk webmaster this snippet to embed the live events calendar under their
"Activities" tab. This code is **only visible to you** in `/webadmin/settings.php` (with
a copy button) — it is intentionally **not** shown anywhere on the public site, so regular
visitors never see it:

```html
<iframe src="https://kmssk.sk/activities?embed=1"
        width="100%" height="720"
        style="border:1px solid #D6C8B8;border-radius:14px;"
        loading="lazy" title="Kalendár podujatí KMS"></iframe>
```

The `.htaccess` file already allows `aktivity.html` to be framed by `ayan.sk` specifically
(via a `Content-Security-Policy: frame-ancestors` header). This is intentionally
restricted to ayan.sk only — there is no admin UI to add other domains; if another site
ever needs it, add its domain to that same `.htaccess` line manually. If ayan.sk's domain
changes, update that line in `.htaccess`.

## 7. Clean URLs

All pages are reachable at clean URLs (handled by `.htaccess` mod_rewrite; the old
`*.html` paths still work but 301-redirect to the clean URL for SEO):

| Clean URL         | Underlying file                  |
|--------------------|-----------------------------------|
| `/`                | `index.html`                      |
| `/about`           | `o-nas.html`                      |
| `/activities`      | `aktivity.html`                   |
| `/announcements`   | `oznamy.html`                     |
| `/education`       | `vzdelavanie.html`                |
| `/faq`             | `faq.html`                        |
| `/contact`         | `kontakt.html`                    |
| `/privacy`         | `ochrana-osobnych-udajov.html`    |

`router.php` in this folder is a **dev-only** shim used by this project's local preview
(PHP's built-in server doesn't read `.htaccess`) — Apache/Websupport ignores it and uses
`.htaccess` instead. Safe to leave it, or delete it after deploying.

## 8. Editing page content live (Editor stránok)

`/webadmin/editor.php` shows the real site in a live preview. Click any text or image
inside the page to edit it in the panel on the right - text supports basic rich
formatting and has a separate tab per language (sk/en/de/ru/ar); images can be replaced
or reverted to the original. Every block also has a "Pôvodný text / obrázok" button to
undo a customization and fall back to the original shipped content. Menu/navigation
labels and buttons are intentionally not editable here (they stay controlled by the
`/i18n/*.json` files) - only page body content and images are.

## 9. Post-launch checklist

- [ ] Open the homepage once to trigger the automatic database install, then read the
      generated password from `api/admin_credentials.log` (Websupport File Manager).
- [ ] Log into `/webadmin/`, change the admin password (Nastavenia), then delete
      `admin_credentials.log`.
- [ ] Fill in the SMTP block in `api/config.php` and submit the real contact form once to
      confirm delivery lands in your inbox.
- [ ] Update `robots.txt` / `sitemap.xml` if you add or rename pages.
- [ ] Uncomment the HTTPS-redirect block in `.htaccess` once your SSL certificate is active.
- [ ] Publish your first real announcement + events from `/webadmin/`.

## Folder structure

```
/api/            PHP endpoints (public read + admin CRUD, session-protected)
/api/lib/PHPMailer/  Bundled PHPMailer library (plain files, no Composer needed)
/assets/         CSS, JS, images, fonts
/i18n/           sk.json, en.json, ru.json, de.json, ar.json (translations)
/uploads/        Admin-uploaded images (PHP execution disabled here for security)
/webadmin/       Password-protected PHP admin panel (Správy, Oznamy, Kalendár,
                 Editor stránok, Nastavenia)
index.html, o-nas.html, aktivity.html, oznamy.html, vzdelavanie.html,
faq.html, kontakt.html, ochrana-osobnych-udajov.html, 404.html
.htaccess, robots.txt, sitemap.xml, router.php (dev-only, see section 7)
```
