<?php
require_once __DIR__ . '/../api/config.php';
if (!empty($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}
?>
<!DOCTYPE html>
<html lang="sk">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Prihlásenie do administrácie | KMS</title>
<meta name="robots" content="noindex, nofollow">
<link rel="icon" href="../assets/img/favicon.png" type="image/png">
<link rel="preconnect" href="https://fonts.googleapis.com">
<link href="https://fonts.googleapis.com/css2?family=IBM+Plex+Sans:wght@400;500;600;700;800&display=swap" rel="stylesheet">
<link rel="stylesheet" href="assets/css/admin.css">
</head>
<body>
<div class="a-login-wrap">
  <div class="a-login-card">
    <div class="brand"><img src="../assets/img/logo.jpg" alt="KMS logo"><span>KMS Administrácia</span></div>
    <h1>Prihlásenie</h1>
    <p class="sub">Zadajte prihlasovacie údaje pre prístup do administrácie.</p>
    <div id="err" class="a-error-box" style="display:none;"></div>
    <form id="login-form" data-testid="admin-login-form">
      <div class="a-form-row">
        <label for="u">Používateľské meno</label>
        <input class="a-input" type="text" id="u" name="username" autocomplete="username" required data-testid="admin-username-input">
      </div>
      <div class="a-form-row">
        <label for="p">Heslo</label>
        <input class="a-input" type="password" id="p" name="password" autocomplete="current-password" required data-testid="admin-password-input">
      </div>
      <button class="a-btn" type="submit" style="width:100%;justify-content:center;" data-testid="admin-login-submit">Prihlásiť sa</button>
    </form>
  </div>
</div>
<script>
document.getElementById('login-form').addEventListener('submit', function (e) {
  e.preventDefault();
  var errBox = document.getElementById('err');
  errBox.style.display = 'none';
  var btn = e.target.querySelector('button');
  btn.disabled = true;
  btn.textContent = 'Prihlasuje sa…';
  fetch('/api/admin_login.php', {
    method: 'POST',
    credentials: 'same-origin',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({
      username: document.getElementById('u').value,
      password: document.getElementById('p').value
    })
  }).then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
    .then(function (res) {
      if (res.ok && res.data.success) {
        window.location.href = 'dashboard.php';
      } else {
        errBox.textContent = (res.data && res.data.error) || 'Nesprávne prihlasovacie údaje.';
        errBox.style.display = 'block';
        btn.disabled = false;
        btn.textContent = 'Prihlásiť sa';
      }
    })
    .catch(function () {
      errBox.textContent = 'Nastala chyba. Skúste to znova.';
      errBox.style.display = 'block';
      btn.disabled = false;
      btn.textContent = 'Prihlásiť sa';
    });
});
</script>
</body>
</html>
