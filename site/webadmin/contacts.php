<?php require_once __DIR__ . '/includes/guard.php'; ?>
<!DOCTYPE html>
<html lang="sk">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Správy | KMS Administrácia</title>
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
        <h1>Správy z kontaktného formulára</h1>
        <p>Prehľad a archív správ zaslaných cez stránku Kontakt.</p>
      </div>
      <div class="a-filter-tabs" style="display:flex;gap:8px;">
        <button class="a-btn a-btn-secondary a-btn-sm" data-filter="active" aria-pressed="true" data-testid="contacts-filter-active">Aktívne</button>
        <button class="a-btn a-btn-secondary a-btn-sm" data-filter="archived" aria-pressed="false" data-testid="contacts-filter-archived">Archivované</button>
      </div>
    </div>

    <div class="a-card">
      <div class="a-table-wrap">
        <table class="a-table" id="c-table" data-testid="contacts-table">
          <thead><tr><th>Meno</th><th>Kontakt</th><th>Správa</th><th>Dátum</th><th>E-mail</th><th>Akcie</th></tr></thead>
          <tbody><tr><td colspan="6" class="a-empty">Načítava sa…</td></tr></tbody>
        </table>
      </div>
    </div>
  </main>
</div>

<script src="assets/js/admin.js"></script>
<script>
var tbody = document.querySelector('#c-table tbody');
var currentFilter = 'active';
var allItems = [];

function render() {
  var items = allItems.filter(function (c) {
    return currentFilter === 'archived' ? c.is_archived == 1 : c.is_archived == 0;
  });
  if (!items.length) {
    tbody.innerHTML = '<tr><td colspan="6" class="a-empty">Žiadne správy v tejto sekcii.</td></tr>';
    return;
  }
  tbody.innerHTML = items.map(function (c) {
    return '<tr data-testid="contact-row-' + c.id + '">' +
      '<td><strong>' + AdminAPI.escapeHtml(c.name) + '</strong>' + (c.phone ? '<br><span style="color:var(--a-fg-muted);font-size:12px;">' + AdminAPI.escapeHtml(c.phone) + '</span>' : '') + '</td>' +
      '<td><a href="mailto:' + AdminAPI.escapeHtml(c.email) + '">' + AdminAPI.escapeHtml(c.email) + '</a></td>' +
      '<td style="max-width:320px;">' + AdminAPI.escapeHtml(c.message.length > 140 ? c.message.slice(0, 140) + '…' : c.message) + '</td>' +
      '<td>' + AdminAPI.fmtDate(c.created_at) + '</td>' +
      '<td><span class="a-pill ' + (c.mail_sent == 1 ? 'on' : 'off') + '"' + (c.mail_sent != 1 && c.mail_error ? ' title="' + AdminAPI.escapeHtml(c.mail_error) + '"' : '') + '>' + (c.mail_sent == 1 ? 'Odoslaný' : 'Neodoslaný') + '</span></td>' +
      '<td class="actions">' +
      (currentFilter === 'active'
        ? '<button class="a-btn a-btn-secondary a-btn-sm" data-archive="' + c.id + '">Archivovať</button>'
        : '<button class="a-btn a-btn-secondary a-btn-sm" data-unarchive="' + c.id + '">Obnoviť</button>') +
      '<button class="a-btn a-btn-danger a-btn-sm" data-delete="' + c.id + '">Zmazať</button>' +
      '</td></tr>';
  }).join('');

  tbody.querySelectorAll('[data-archive]').forEach(function (btn) {
    btn.addEventListener('click', function () { doAction('archive', btn.getAttribute('data-archive')); });
  });
  tbody.querySelectorAll('[data-unarchive]').forEach(function (btn) {
    btn.addEventListener('click', function () { doAction('unarchive', btn.getAttribute('data-unarchive')); });
  });
  tbody.querySelectorAll('[data-delete]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      if (!confirm('Naozaj chcete zmazať túto správu?')) return;
      doAction('delete', btn.getAttribute('data-delete'));
    });
  });
}

function doAction(action, id) {
  AdminAPI.apiFetch('/api/admin_contacts.php', { method: 'POST', body: { action: action, id: id } })
    .then(function (res) {
      if (res.data.success) { AdminAPI.toast('Hotovo.'); loadList(); }
      else AdminAPI.toast(res.data.error || 'Chyba', 'error');
    });
}

function loadList() {
  AdminAPI.apiFetch('/api/admin_contacts.php').then(function (res) {
    allItems = res.data.items || [];
    render();
  });
}

document.querySelectorAll('[data-filter]').forEach(function (btn) {
  btn.addEventListener('click', function () {
    document.querySelectorAll('[data-filter]').forEach(function (b) { b.setAttribute('aria-pressed', 'false'); });
    btn.setAttribute('aria-pressed', 'true');
    currentFilter = btn.getAttribute('data-filter');
    render();
  });
});

loadList();
</script>
</body>
</html>
