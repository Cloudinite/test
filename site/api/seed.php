<?php
/**
 * CLI seeding script. Run once: php seed.php
 * Creates tables (if not exist), seeds admin user + sample content.
 */
require_once __DIR__ . '/db.php';
$pdo = get_pdo();

$schema = file_get_contents(__DIR__ . '/schema.sql');
$schema = preg_replace('/^\s*--.*$/m', '', $schema); // strip full-line comments before splitting on ";"
foreach (array_filter(array_map('trim', explode(';', $schema))) as $stmt) {
    if ($stmt) { $pdo->exec($stmt); }
}
echo "[OK] Schema ensured.\n";

$username = 'admin';
$password = getenv('KMSSK_ADMIN_PASS') ?: 'CHANGE_ME_12345';
$hash = password_hash($password, PASSWORD_DEFAULT);

$stmt = $pdo->prepare("SELECT id FROM admin_users WHERE username = ?");
$stmt->execute([$username]);
if (!$stmt->fetch()) {
    $pdo->prepare("INSERT INTO admin_users (username, password_hash) VALUES (?, ?)")->execute([$username, $hash]);
    echo "[OK] Admin user created: $username\n";
} else {
    echo "[SKIP] Admin user already exists.\n";
}

// Seed sample announcement (site launch) if table is empty
$count = $pdo->query("SELECT COUNT(*) c FROM announcements")->fetch()['c'];
if ($count == 0) {
    $pdo->prepare("INSERT INTO announcements (title, body, audience, is_published, show_popup, published_at) VALUES (?, ?, 'public', 1, 1, NOW())")
        ->execute([
            'Spustenie novej stránky KMS',
            'Vitajte na obnovenej stránke Komunity muslimov na Slovensku. Nájdete tu aktuálne oznamy, kalendár podujatí a všetky dôležité informácie pre našu komunitu.'
        ]);
    echo "[OK] Sample announcement seeded.\n";
} else {
    echo "[SKIP] Announcements already present ($count).\n";
}

// Seed sample events if table is empty
$ecount = $pdo->query("SELECT COUNT(*) c FROM events")->fetch()['c'];
if ($ecount == 0) {
    $events = [
        ['Pozvánka na zasadnutie valného zhromaždenia KMS', 'Zasadnutie valného zhromaždenia Komunity muslimov na Slovensku (KMS) v priestoroch Centra AYA na Bojnickej ul. v Bratislave.', 'Centrum AYA, Bojnická ul., Bratislava', date('Y-m-d H:i:s', strtotime('+5 days 19:45')), date('Y-m-d H:i:s', strtotime('+5 days 21:30'))],
        ['Komunitný iftár pre rodiny', 'Spoločné stretnutie na podporu vzdelávania a dialógu medzi členmi komunity.', 'Centrum AYA, Bratislava', date('Y-m-d H:i:s', strtotime('+12 days 18:00')), date('Y-m-d H:i:s', strtotime('+12 days 21:00'))],
        ['Vzdelávací seminár o islamskej kultúre', 'Seminár zameraný na rozširovanie vedomostí o islamských tradíciách a hodnotách.', 'Centrum AYA, Bratislava', date('Y-m-d H:i:s', strtotime('+20 days 17:00')), date('Y-m-d H:i:s', strtotime('+20 days 19:30'))],
    ];
    $ins = $pdo->prepare("INSERT INTO events (title, description, location, start_dt, end_dt, is_published) VALUES (?, ?, ?, ?, ?, 1)");
    foreach ($events as $e) { $ins->execute($e); }
    echo "[OK] Sample events seeded (" . count($events) . ").\n";
} else {
    echo "[SKIP] Events already present ($ecount).\n";
}

echo "[DONE]\n";
