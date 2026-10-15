<?php
require_once __DIR__ . '/config.php';

function get_pdo() {
    static $pdo = null;
    static $schemaChecked = false;

    if ($pdo === null) {
        // NOTE: the MySQL/PDO client treats the literal host "localhost" as
        // "connect via local Unix socket" and silently IGNORES any port you
        // pass alongside it. Many hosting panels (incl. Websupport) hand out
        // a "localhost" + custom port (e.g. 3317) pair meant for a real TCP
        // connection. To honour the configured port reliably we translate
        // "localhost" -> "127.0.0.1" internally so the port is always used.
        $connectHost = (strtolower(DB_HOST) === 'localhost') ? '127.0.0.1' : DB_HOST;
        $dsn = "mysql:host=" . $connectHost . ";port=" . DB_PORT . ";dbname=" . DB_NAME . ";charset=utf8mb4";
        $attempts = 0;
        $maxAttempts = 10;
        $lastError = null;
        while ($attempts < $maxAttempts) {
            try {
                $pdo = new PDO($dsn, DB_USER, DB_PASS, [
                    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES => false,
                    PDO::ATTR_TIMEOUT => 3,
                ]);
                $pdo->exec("SET time_zone = '+02:00'");
                break;
            } catch (\Throwable $e) {
                $lastError = $e;
                $attempts++;
                // MySQL/MariaDB may still be starting up right after a
                // container/service restart - briefly retry before failing.
                usleep(500000); // 0.5s
            }
        }
        if ($pdo === null) {
            if ($lastError) {
                error_log('[kmssk db] connection failed after ' . $maxAttempts . ' attempts: ' . $lastError->getMessage());
            }
            http_response_code(503);
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode([
                'success' => false,
                'error' => 'Database connection failed. Please check config.php credentials (DB_HOST/DB_PORT/DB_NAME/DB_USER/DB_PASS) or try again in a few seconds.'
            ]);
            exit;
        }
    }

    // Auto-install: on the very first query of this process, make sure the
    // required tables (and the admin account) exist. This means a fresh
    // Websupport database only ever needs its 4 credentials filled in -
    // no manual SQL import or SSH step required.
    if (!$schemaChecked) {
        $schemaChecked = true;
        ensure_schema($pdo);
    }

    return $pdo;
}

function ensure_schema(PDO $pdo) {
    // Cheap fast-path: if the core table already exists AND a newer table
    // introduced later (e.g. content_overrides) also exists, nothing to do.
    $isFreshInstall = false;
    try {
        $pdo->query('SELECT 1 FROM admin_users LIMIT 1');
    } catch (\Throwable $e) {
        $isFreshInstall = true; // whole schema missing - brand new database
    }

    try {
        // Always (re-)run the idempotent CREATE TABLE IF NOT EXISTS
        // statements so that tables added in later updates (e.g.
        // content_overrides for the Page Editor) get created automatically
        // on an already-running site too, without requiring a manual
        // migration step.
        $schemaSql = file_get_contents(__DIR__ . '/schema.sql');
        // Strip full-line SQL comments FIRST (so a comment block that sits
        // directly above a CREATE TABLE, with no separating semicolon,
        // never gets glued to - and accidentally skipped with - real SQL).
        $schemaSql = preg_replace('/^\s*--.*$/m', '', $schemaSql);
        foreach (array_filter(array_map('trim', explode(';', $schemaSql))) as $stmt) {
            if ($stmt === '') { continue; }
            $pdo->exec($stmt);
        }
    } catch (\Throwable $e) {
        error_log('[kmssk db] Schema ensure failed: ' . $e->getMessage());
        return;
    }

    // Migration: older installs created contact_submissions before the
    // mail_error column existed. Add it if missing so failed-send reasons
    // can be surfaced in the webadmin Contacts page for easier debugging.
    try {
        $col = $pdo->query("SHOW COLUMNS FROM contact_submissions LIKE 'mail_error'")->fetch();
        if (!$col) {
            $pdo->exec('ALTER TABLE contact_submissions ADD COLUMN mail_error TEXT DEFAULT NULL');
        }
    } catch (\Throwable $e) {
        error_log('[kmssk db] mail_error column migration failed: ' . $e->getMessage());
    }

    if (!$isFreshInstall) {
        return; // tables already existed - skip admin/sample-data seeding
    }

    try {
        // Auto-create the admin account (once) with a random password and
        // record it to a log-protected file (denied by .htaccess) so the
        // owner can retrieve it once via the hosting File Manager.
        $stmt = $pdo->prepare("SELECT id FROM admin_users WHERE username = 'admin'");
        $stmt->execute();
        if (!$stmt->fetch()) {
            $password = generate_random_password(12);
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $pdo->prepare("INSERT INTO admin_users (username, password_hash) VALUES ('admin', ?)")->execute([$hash]);
            $line = '[' . date('Y-m-d H:i:s') . "] Auto-created admin account on first run.\n"
                . "Username: admin\nPassword: " . $password . "\n"
                . "Log in at /webadmin/, change this password, then delete this file.\n\n";
            @file_put_contents(__DIR__ . '/admin_credentials.log', $line, FILE_APPEND);
            error_log('[kmssk db] Admin account auto-created (see api/admin_credentials.log).');
        }

        // Seed a starter announcement + a few sample events only if those
        // tables are completely empty, so the public site never looks blank
        // right after install.
        $count = (int)($pdo->query('SELECT COUNT(*) c FROM announcements')->fetch()['c'] ?? 0);
        if ($count === 0) {
            $pdo->prepare("INSERT INTO announcements (title, body, audience, is_published, show_popup, published_at) VALUES (?, ?, 'public', 1, 1, NOW())")
                ->execute([
                    'Spustenie novej stránky KMS',
                    'Vitajte na obnovenej stránke Komunity moslimov na Slovensku. Nájdete tu aktuálne oznamy, kalendár podujatí a všetky dôležité informácie pre našu komunitu.'
                ]);
        }

        $ecount = (int)($pdo->query('SELECT COUNT(*) c FROM events')->fetch()['c'] ?? 0);
        if ($ecount === 0) {
            $events = [
                ['Pozvánka na zasadnutie valného zhromaždenia KMS', 'Zasadnutie valného zhromaždenia Komunity moslimov na Slovensku (KMS) v priestoroch Centra AYA na Bojnickej ul. v Bratislave.', 'Centrum AYA, Bojnická ul., Bratislava', date('Y-m-d H:i:s', strtotime('+5 days 19:45')), date('Y-m-d H:i:s', strtotime('+5 days 21:30'))],
                ['Komunitný iftár pre rodiny', 'Spoločné stretnutie na podporu vzdelávania a dialógu medzi členmi komunity.', 'Centrum AYA, Bratislava', date('Y-m-d H:i:s', strtotime('+12 days 18:00')), date('Y-m-d H:i:s', strtotime('+12 days 21:00'))],
                ['Vzdelávací seminár o islamskej kultúre', 'Seminár zameraný na rozširovanie vedomostí o islamských tradíciách a hodnotách.', 'Centrum AYA, Bratislava', date('Y-m-d H:i:s', strtotime('+20 days 17:00')), date('Y-m-d H:i:s', strtotime('+20 days 19:30'))],
            ];
            $ins = $pdo->prepare('INSERT INTO events (title, description, location, start_dt, end_dt, is_published) VALUES (?, ?, ?, ?, ?, 1)');
            foreach ($events as $e) { $ins->execute($e); }
        }

        error_log('[kmssk db] Database schema auto-installed successfully.');
    } catch (\Throwable $e) {
        error_log('[kmssk db] Auto schema install failed: ' . $e->getMessage());
    }
}

function generate_random_password($length = 12) {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnpqrstuvwxyz23456789';
    $max = strlen($chars) - 1;
    $out = '';
    for ($i = 0; $i < $length; $i++) {
        $out .= $chars[random_int(0, $max)];
    }
    return $out;
}
