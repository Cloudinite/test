<?php require_once __DIR__ . '/includes/guard.php'; require_once __DIR__ . '/includes/icons.php'; ?>
<!DOCTYPE html>
<html lang="sk">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Nastavenia | KMS Administrácia</title>
<meta name="robots" content="noindex, nofollow">
<link rel="icon" href="../assets/img/favicon.png" type="image/png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
<script>window.CSRF_TOKEN = <?php echo json_encode($CSRF_TOKEN); ?>;</script>
<div class="a-shell">
  <?php include __DIR__ . '/includes/sidebar.php'; ?>
  <main class="a-main">
    <button class="a-hamburger" data-admin-burger aria-label="Menu">&#9776;</button>
    <div class="a-topbar">
      <div>
        <h1>Nastavenia</h1>
        <p>Heslo administrátora a vloženie kalendára.</p>
      </div>
    </div>

    <div class="a-settings-grid">

      <div class="a-card a-card-pad">
        <div class="a-card-head">
          <h2><?php echo admin_icon('lock', 16); ?> Zmena hesla</h2>
          <p>Zmeňte si prihlasovacie heslo do administrácie.</p>
        </div>
        <div id="pw-err" class="a-error-box" style="display:none;"></div>
        <form id="pw-form" data-testid="change-password-form">
          <div class="a-form-row">
            <label>Súčasné heslo</label>
            <input class="a-input" type="password" name="current_password" autocomplete="current-password" required data-testid="current-password-input">
          </div>
          <div class="a-form-row">
            <label>Nové heslo (min. 8 znakov)</label>
            <input class="a-input" type="password" name="new_password" autocomplete="new-password" minlength="8" required data-testid="new-password-input">
          </div>
          <button type="submit" class="a-btn" data-testid="change-password-submit">Zmeniť heslo</button>
        </form>
      </div>

      <div class="a-card a-card-pad">
        <div class="a-card-head">
          <h2><?php echo admin_icon('mail', 16); ?> E-mailové doručovanie kontaktného formulára</h2>
          <p>SMTP prihlasovacie údaje sa už nezadávajú tu - nastavujú sa priamo v súbore <code>api/config.php</code> (konštanty <code>SMTP_HOST</code>, <code>SMTP_USERNAME</code>, <code>SMTP_PASSWORD</code>, ...). Ak sú vyplnené, kontaktný formulár automaticky odosiela e-maily cez SMTP; inak použije záložné PHP <code>mail()</code>. Zmenu overíte jednoducho odoslaním správy z verejného kontaktného formulára na <code>/contact</code>.</p>
        </div>
      </div>

      <div class="a-card a-card-pad">
        <div class="a-card-head">
          <h2><?php echo admin_icon('calendar', 16); ?> Vloženie kalendára na ayan.sk</h2>
          <p>Kalendár podujatí je možné vložiť výhradne na <strong>ayan.sk</strong> (povolenie rámca je obmedzené len na túto doménu v <code>.htaccess</code>). Túto funkciu nemožno pridať na iné stránky priamo z administrácie — dajte tento kód webmasterovi ayan.sk, aby ho vložil manuálne na svoju stránku.</p>
        </div>
        <div class="a-code-box" id="embed-code">&lt;iframe src="https://kmssk.sk/activities?embed=1"
  width="100%" height="720"
  style="border:1px solid #D6C8B8;border-radius:14px;"
  loading="lazy" title="Kalendár podujatí KMS"&gt;&lt;/iframe&gt;</div>
        <div class="a-inline-actions">
          <button type="button" class="a-btn a-btn-secondary a-btn-sm" id="embed-copy-btn"><?php echo admin_icon('copy', 14); ?> Kopírovať kód</button>
        </div>
      </div>

    </div>
  </main>
</div>

<script src="assets/js/admin.js"></script>
<script>
var pwForm = document.getElementById('pw-form');
var pwErr = document.getElementById('pw-err');
pwForm.addEventListener('submit', function (e) {
  e.preventDefault();
  pwErr.style.display = 'none';
  var btn = pwForm.querySelector('button[type="submit"]');
  btn.disabled = true;
  AdminAPI.apiFetch('/api/admin_change_password.php', {
    method: 'POST',
    body: { current_password: pwForm.current_password.value, new_password: pwForm.new_password.value }
  }).then(function (res) {
    if (res.data.success) {
      AdminAPI.toast('Heslo bolo zmenené.');
      pwForm.reset();
    } else {
      pwErr.textContent = res.data.error || 'Nastala chyba.';
      pwErr.style.display = 'block';
    }
  }).finally(function () { btn.disabled = false; });
});

document.getElementById('embed-copy-btn').addEventListener('click', function () {
  var text = document.getElementById('embed-code').textContent;
  var done = function () { AdminAPI.toast('Kód skopírovaný.'); };
  if (navigator.clipboard && navigator.clipboard.writeText) {
    navigator.clipboard.writeText(text).then(done);
  } else {
    var ta = document.createElement('textarea');
    ta.value = text; document.body.appendChild(ta); ta.select();
    try { document.execCommand('copy'); } catch (e) {}
    document.body.removeChild(ta); done();
  }
});
</script>
</body>
</html>
