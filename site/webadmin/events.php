<?php require_once __DIR__ . '/includes/guard.php'; ?>
<!DOCTYPE html>
<html lang="sk">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Podujatia | KMS Administrácia</title>
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
        <h1>Podujatia</h1>
        <p>Správa kalendára podujatí (nadchádzajúce, prebiehajúce, uplynulé).</p>
      </div>
      <button class="a-btn" id="new-btn" data-testid="new-event-btn">+ Nové podujatie</button>
    </div>

    <div class="a-card">
      <div class="a-table-wrap">
        <table class="a-table" id="ev-table" data-testid="events-table">
          <thead><tr><th>Názov</th><th>Začiatok</th><th>Miesto</th><th>Stav</th><th>Publikované</th><th>Akcie</th></tr></thead>
          <tbody><tr><td colspan="6" class="a-empty">Načítava sa…</td></tr></tbody>
        </table>
      </div>
    </div>
  </main>
</div>

<div class="a-modal-scrim" id="modal">
  <div class="a-modal-box">
    <h2 id="modal-title">Nové podujatie</h2>
    <div id="form-err" class="a-error-box" style="display:none;"></div>
    <form id="ev-form" data-testid="event-form">
      <input type="hidden" name="id">
      <div class="a-form-row">
        <label>Názov *</label>
        <input class="a-input" name="title" required data-testid="event-title-input">
      </div>
      <div class="a-form-row">
        <label>Popis</label>
        <textarea class="a-textarea" name="description" data-testid="event-description-input"></textarea>
      </div>
      <div class="a-form-row">
        <label>Miesto</label>
        <input class="a-input" name="location" placeholder="Centrum AYA, Bratislava" data-testid="event-location-input">
      </div>
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px;">
        <div class="a-form-row">
          <label>Začiatok *</label>
          <input class="a-input" type="datetime-local" name="start_dt" required data-testid="event-start-input">
        </div>
        <div class="a-form-row">
          <label>Koniec</label>
          <input class="a-input" type="datetime-local" name="end_dt" data-testid="event-end-input">
        </div>
      </div>
      <div class="a-checkbox-row">
        <input type="checkbox" id="is_published" name="is_published" checked data-testid="event-published-checkbox">
        <label for="is_published" style="margin:0;">Publikované</label>
      </div>
      <div class="a-checkbox-row">
        <input type="checkbox" id="is_cancelled" name="is_cancelled" data-testid="event-cancelled-checkbox">
        <label for="is_cancelled" style="margin:0;">Zrušené podujatie</label>
      </div>
      <div style="display:flex;gap:10px;justify-content:flex-end;margin-top:18px;">
        <button type="button" class="a-btn a-btn-secondary" id="cancel-btn">Zrušiť</button>
        <button type="submit" class="a-btn" data-testid="event-save-btn">Uložiť</button>
      </div>
    </form>
  </div>
</div>

<script src="assets/js/admin.js"></script>
<script>
var tbody = document.querySelector('#ev-table tbody');
var modal = document.getElementById('modal');
var form = document.getElementById('ev-form');
var formErr = document.getElementById('form-err');

function statusOf(ev) {
  var now = new Date();
  var start = new Date(ev.start_dt.replace(' ', 'T'));
  var end = ev.end_dt ? new Date(ev.end_dt.replace(' ', 'T')) : new Date(start.getTime() + 2 * 3600 * 1000);
  if (ev.is_cancelled == 1) return { key: 'cancelled', label: 'Zrušené' };
  if (now < start) return { key: 'upcoming', label: 'Nadchádzajúce' };
  if (now >= start && now <= end) return { key: 'ongoing', label: 'Prebieha' };
  return { key: 'past', label: 'Uplynulo' };
}

function toLocalInput(dt) {
  if (!dt) return '';
  return dt.replace(' ', 'T').slice(0, 16);
}
function toSqlDt(local) {
  if (!local) return '';
  return local.replace('T', ' ') + ':00';
}

function loadList() {
  AdminAPI.apiFetch('/api/admin_events.php').then(function (res) {
    var items = res.data.items || [];
    if (!items.length) {
      tbody.innerHTML = '<tr><td colspan="6" class="a-empty">Zatiaľ žiadne podujatia.</td></tr>';
      return;
    }
    tbody.innerHTML = items.map(function (ev) {
      var st = statusOf(ev);
      return '<tr data-testid="ev-row-' + ev.id + '">' +
        '<td><strong>' + AdminAPI.escapeHtml(ev.title) + '</strong></td>' +
        '<td>' + AdminAPI.fmtDate(ev.start_dt) + '</td>' +
        '<td>' + AdminAPI.escapeHtml(ev.location || '—') + '</td>' +
        '<td><span class="a-pill">' + st.label + '</span></td>' +
        '<td><span class="a-pill ' + (ev.is_published == 1 ? 'on' : 'off') + '">' + (ev.is_published == 1 ? 'Áno' : 'Nie') + '</span></td>' +
        '<td class="actions">' +
        '<button class="a-btn a-btn-secondary a-btn-sm" data-edit="' + ev.id + '">Upraviť</button>' +
        '<button class="a-btn a-btn-danger a-btn-sm" data-delete="' + ev.id + '">Zmazať</button>' +
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
        if (!confirm('Naozaj chcete zmazať toto podujatie?')) return;
        AdminAPI.apiFetch('/api/admin_events.php', { method: 'POST', body: { action: 'delete', id: btn.getAttribute('data-delete') } })
          .then(function (res) {
            if (res.data.success) { AdminAPI.toast('Podujatie zmazané.'); loadList(); }
            else AdminAPI.toast(res.data.error || 'Chyba', 'error');
          });
      });
    });
  });
}

function openModal(item) {
  formErr.style.display = 'none';
  form.reset();
  document.getElementById('modal-title').textContent = item ? 'Upraviť podujatie' : 'Nové podujatie';
  form.id.value = item ? item.id : '';
  form.is_published.checked = true;
  if (item) {
    form.title.value = item.title;
    form.description.value = item.description || '';
    form.location.value = item.location || '';
    form.start_dt.value = toLocalInput(item.start_dt);
    form.end_dt.value = toLocalInput(item.end_dt);
    form.is_published.checked = item.is_published == 1;
    form.is_cancelled.checked = item.is_cancelled == 1;
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
    description: form.description.value.trim(),
    location: form.location.value.trim(),
    start_dt: toSqlDt(form.start_dt.value),
    end_dt: toSqlDt(form.end_dt.value),
    is_published: form.is_published.checked ? 1 : 0,
    is_cancelled: form.is_cancelled.checked ? 1 : 0
  };
  var saveBtn = form.querySelector('[data-testid="event-save-btn"]');
  saveBtn.disabled = true;
  AdminAPI.apiFetch('/api/admin_events.php', { method: 'POST', body: payload })
    .then(function (res) {
      if (res.data.success) {
        AdminAPI.toast('Podujatie uložené.');
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
