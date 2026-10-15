<?php require_once __DIR__ . '/includes/guard.php'; ?>
<!DOCTYPE html>
<html lang="sk">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Oznamy | KMS Administrácia</title>
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
        <h1>Oznamy</h1>
        <p>Správa oznamov zobrazovaných na hlavnej stránke a v pop-upe.</p>
      </div>
      <button class="a-btn" id="new-btn" data-testid="new-announcement-btn">+ Nový oznam</button>
    </div>

    <div class="a-card">
      <div class="a-table-wrap">
        <table class="a-table" id="ann-table" data-testid="announcements-table">
          <thead><tr><th>Názov</th><th>Publikum</th><th>Publikované</th><th>Pop-up</th><th>Dátum</th><th>Akcie</th></tr></thead>
          <tbody><tr><td colspan="6" class="a-empty">Načítava sa…</td></tr></tbody>
        </table>
      </div>
    </div>
  </main>
</div>

<div class="a-modal-scrim" id="modal">
  <div class="a-modal-box">
    <h2 id="modal-title">Nový oznam</h2>
    <div id="form-err" class="a-error-box" style="display:none;"></div>
    <form id="ann-form" data-testid="announcement-form">
      <input type="hidden" name="id">
      <div class="a-form-row">
        <label>Názov *</label>
        <input class="a-input" name="title" required data-testid="announcement-title-input">
      </div>
      <div class="a-form-row">
        <label>Text oznamu *</label>
        <textarea class="a-textarea" name="body" required data-testid="announcement-body-input"></textarea>
      </div>
      <div class="a-form-row">
        <label>Publikum</label>
        <select class="a-select" name="audience" data-testid="announcement-audience-select">
          <option value="public">Verejnosť</option>
          <option value="members">Iba členovia</option>
        </select>
      </div>
      <div class="a-checkbox-row">
        <input type="checkbox" id="is_published" name="is_published" data-testid="announcement-published-checkbox">
        <label for="is_published" style="margin:0;">Publikovať hneď</label>
      </div>
      <div class="a-checkbox-row">
        <input type="checkbox" id="show_popup" name="show_popup" data-testid="announcement-popup-checkbox">
        <label for="show_popup" style="margin:0;">Zobraziť ako pop-up na hlavnej stránke</label>
      </div>
      <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:18px;">
        <button type="button" class="a-btn a-btn-secondary" id="cancel-btn">Zrušiť</button>
        <button type="submit" class="a-btn" data-testid="announcement-save-btn">Uložiť</button>
      </div>
    </form>
  </div>
</div>

<script src="assets/js/admin.js"></script>
<script>
var tbody = document.querySelector('#ann-table tbody');
var modal = document.getElementById('modal');
var form = document.getElementById('ann-form');
var formErr = document.getElementById('form-err');

function loadList() {
  AdminAPI.apiFetch('/api/admin_announcements.php').then(function (res) {
    var items = res.data.items || [];
    if (!items.length) {
      tbody.innerHTML = '<tr><td colspan="6" class="a-empty">Zatiaľ žiadne oznamy.</td></tr>';
      return;
    }
    tbody.innerHTML = items.map(function (a) {
      return '<tr data-testid="ann-row-' + a.id + '">' +
        '<td><strong>' + AdminAPI.escapeHtml(a.title) + '</strong></td>' +
        '<td>' + (a.audience === 'members' ? 'Členovia' : 'Verejnosť') + '</td>' +
        '<td><span class="a-pill ' + (a.is_published == 1 ? 'on' : 'off') + '">' + (a.is_published == 1 ? 'Áno' : 'Nie') + '</span></td>' +
        '<td><span class="a-pill ' + (a.show_popup == 1 ? 'on' : 'off') + '">' + (a.show_popup == 1 ? 'Áno' : 'Nie') + '</span></td>' +
        '<td>' + AdminAPI.fmtDate(a.published_at || a.created_at) + '</td>' +
        '<td class="actions">' +
        '<button class="a-btn a-btn-secondary a-btn-sm" data-edit="' + a.id + '">Upraviť</button>' +
        '<button class="a-btn a-btn-danger a-btn-sm" data-delete="' + a.id + '">Zmazať</button>' +
        '</td></tr>';
    }).join('');

    tbody.querySelectorAll('[data-edit]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        var item = items.find(function (i) { return String(i.id) === btn.getAttribute('data-edit'); });
        openModal(item);
      });
    });
    tbody.querySelectorAll('[data-delete]').forEach(function (btn) {
      btn.addEventListener('click', function () {
        if (!confirm('Naozaj chcete zmazať tento oznam?')) return;
        AdminAPI.apiFetch('/api/admin_announcements.php', { method: 'POST', body: { action: 'delete', id: btn.getAttribute('data-delete') } })
          .then(function (res) {
            if (res.data.success) { AdminAPI.toast('Oznam zmazaný.'); loadList(); }
            else AdminAPI.toast(res.data.error || 'Chyba', 'error');
          });
      });
    });
  });
}

function openModal(item) {
  formErr.style.display = 'none';
  form.reset();
  document.getElementById('modal-title').textContent = item ? 'Upraviť oznam' : 'Nový oznam';
  form.id.value = item ? item.id : '';
  if (item) {
    form.title.value = item.title;
    form.body.value = item.body;
    form.audience.value = item.audience;
    form.is_published.checked = item.is_published == 1;
    form.show_popup.checked = item.show_popup == 1;
  }
  modal.classList.add('open');
}
function closeModal() { modal.classList.remove('open'); }

document.getElementById('new-btn').addEventListener('click', function () { openModal(null); });
document.getElementById('cancel-btn').addEventListener('click', closeModal);
modal.addEventListener('click', function (e) { if (e.target === modal) closeModal(); });

form.addEventListener('submit', function (e) {
  e.preventDefault();
  formErr.style.display = 'none';
  var payload = {
    action: form.id.value ? 'update' : 'create',
    id: form.id.value,
    title: form.title.value.trim(),
    body: form.body.value.trim(),
    audience: form.audience.value,
    is_published: form.is_published.checked ? 1 : 0,
    show_popup: form.show_popup.checked ? 1 : 0
  };
  var saveBtn = form.querySelector('[data-testid="announcement-save-btn"]');
  saveBtn.disabled = true;
  AdminAPI.apiFetch('/api/admin_announcements.php', { method: 'POST', body: payload })
    .then(function (res) {
      if (res.data.success) {
        AdminAPI.toast('Oznam uložený.');
        closeModal();
        loadList();
      } else {
        formErr.textContent = res.data.error || 'Nastala chyba.';
        formErr.style.display = 'block';
      }
    })
    .finally(function () { saveBtn.disabled = false; });
});

loadList();
</script>
</body>
</html>
