<?php require_once __DIR__ . '/includes/guard.php'; ?>
<!DOCTYPE html>
<html lang="sk">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Prehľad | KMS Administrácia</title>
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
        <h1>Prehľad</h1>
        <p>Vitajte späť, <?php echo htmlspecialchars($ADMIN_USERNAME); ?>.</p>
      </div>
      <a class="a-btn a-btn-secondary" href="../index.html" target="_blank">Zobraziť stránku &#8599;</a>
    </div>

    <div class="a-grid-stats" id="stats">
      <div class="a-card a-stat"><div class="num">—</div><div class="label">Publikované oznamy</div></div>
      <div class="a-card a-stat"><div class="num">—</div><div class="label">Nadchádzajúce podujatia</div></div>
      <div class="a-card a-stat"><div class="num">—</div><div class="label">Nové správy</div></div>
    </div>

    <div class="a-card a-card-pad">
      <h2>Rýchle akcie</h2>
      <div style="display:flex;gap:10px;flex-wrap:wrap;margin-top:10px;">
        <a class="a-btn" href="announcements.php">+ Nový oznam</a>
        <a class="a-btn a-btn-secondary" href="events.php">+ Nové podujatie</a>
        <a class="a-btn a-btn-secondary" href="contacts.php">Zobraziť správy</a>
      </div>
    </div>
  </main>
</div>
<script src="assets/js/admin.js"></script>
<script>
Promise.all([
  AdminAPI.apiFetch('/api/admin_announcements.php'),
  AdminAPI.apiFetch('/api/admin_events.php'),
  AdminAPI.apiFetch('/api/admin_contacts.php')
]).then(function (results) {
  var ann = results[0].data.items || [];
  var ev = results[1].data.items || [];
  var contacts = results[2].data.items || [];
  var now = new Date();
  var publishedAnn = ann.filter(function (a) { return a.is_published == 1; }).length;
  var upcomingEv = ev.filter(function (e) { return new Date(e.start_dt.replace(' ', 'T')) >= now && !e.is_cancelled; }).length;
  var newMsgs = contacts.filter(function (c) { return c.is_archived == 0; }).length;
  var stats = document.getElementById('stats').querySelectorAll('.num');
  stats[0].textContent = publishedAnn;
  stats[1].textContent = upcomingEv;
  stats[2].textContent = newMsgs;
});
</script>
</body>
</html>
