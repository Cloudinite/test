<?php require_once __DIR__ . '/includes/guard.php'; require_once __DIR__ . '/includes/icons.php'; ?>
<!DOCTYPE html>
<html lang="sk">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Editor stránok | KMS Administrácia</title>
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
    <div class="a-editor-topbar">
      <div>
        <h1>Editor stránok</h1>
        <p class="a-editor-hint">Kliknite priamo na text alebo obrázok v náhľade a upravte ho v paneli vpravo. Zmeny sa po uložení hneď zobrazia na verejnej stránke.</p>
      </div>
      <select class="a-select a-editor-page-select" id="page-select" data-testid="editor-page-select">
        <option value="index.html">Domov</option>
        <option value="o-nas.html">O nás</option>
        <option value="aktivity.html">Aktivity</option>
        <option value="oznamy.html">Oznamy</option>
        <option value="faq.html">FAQ</option>
        <option value="kontakt.html">Kontakt</option>
        <option value="ochrana-osobnych-udajov.html">Ochrana osobných údajov</option>
      </select>
    </div>

    <div class="a-editor-shell">
      <div class="a-editor-preview">
        <div class="a-editor-loading" id="editor-loading">Načítava náhľad…</div>
        <iframe id="editor-iframe" data-testid="editor-preview-iframe" title="Náhľad stránky"></iframe>
      </div>
      <div class="a-editor-panel" id="editor-panel" data-testid="editor-panel">
        <div class="a-editor-empty" id="panel-empty">Kliknite na text alebo obrázok v náhľade vľavo, aby ste ho mohli upraviť.</div>
      </div>
    </div>
  </main>
</div>

<script src="assets/js/admin.js"></script>
<script>
var LANGS = [
  { code: 'sk', label: 'Slovenčina' },
  { code: 'en', label: 'English' },
  { code: 'de', label: 'Deutsch' },
  { code: 'ru', label: 'Русский' },
  { code: 'ar', label: 'العربية' }
];

var pageSelect = document.getElementById('page-select');
var iframe = document.getElementById('editor-iframe');
var loadingEl = document.getElementById('editor-loading');
var panel = document.getElementById('editor-panel');

var i18nCache = {};
var overridesCache = null;

function loadIframe() {
  loadingEl.style.display = 'flex';
  iframe.src = '../' + pageSelect.value + '?admin_edit=1&t=' + Date.now();
}
iframe.addEventListener('load', function () { loadingEl.style.display = 'none'; });
pageSelect.addEventListener('change', loadIframe);
loadIframe();

function fetchLocale(lang) {
  if (i18nCache[lang]) return Promise.resolve(i18nCache[lang]);
  return fetch('../i18n/' + lang + '.json', { cache: 'no-store' }).then(function (r) { return r.json(); })
    .then(function (d) { i18nCache[lang] = d; return d; });
}
function fetchOverrides(force) {
  if (overridesCache && !force) return Promise.resolve(overridesCache);
  return AdminAPI.apiFetch('/api/content_overrides.php').then(function (res) {
    overridesCache = res.data || { text: {}, images: {} };
    return overridesCache;
  });
}
function getByPath(obj, path) {
  var parts = path.split('.');
  var cur = obj;
  for (var i = 0; i < parts.length; i++) { if (cur == null) return null; cur = cur[parts[i]]; }
  return cur;
}

window.addEventListener('message', function (e) {
  var d = e.data;
  if (!d || d.source !== 'kmssk-edit-bridge') return;
  if (d.type === 'select') {
    if (d.kind === 'image') openImagePanel(d.key, d.currentSrc);
    else openTextPanel(d.key, d.lang);
  }
});

function panelHeader(key) {
  return '<div class="a-editor-key" data-testid="editor-selected-key">' + AdminAPI.escapeHtml(key) + '</div>';
}

/* ---------- Text panel ---------- */
function openTextPanel(key, activeLang) {
  fetchOverrides().then(function (overrides) {
    Promise.all(LANGS.map(function (l) { return fetchLocale(l.code); })).then(function () {
      renderTextPanel(key, activeLang || 'sk', overrides);
    });
  });
}

function renderTextPanel(key, activeLang, overrides) {
  var pillsHtml = LANGS.map(function (l) {
    var isCustom = overrides.text[l.code] && overrides.text[l.code][key] != null;
    return '<button type="button" class="a-lang-pill' + (isCustom ? ' is-custom' : '') + '" data-lang="' + l.code + '" aria-pressed="' + (l.code === activeLang ? 'true' : 'false') + '" data-testid="editor-lang-' + l.code + '">' + l.label + '</button>';
  }).join('');

  panel.innerHTML =
    panelHeader(key) +
    '<div class="a-rte-toolbar">' +
      '<button type="button" data-cmd="bold" title="Hrubé" data-testid="rte-bold"><?php echo admin_icon("bold", 16); ?></button>' +
      '<button type="button" data-cmd="italic" title="Kurzíva" data-testid="rte-italic"><?php echo admin_icon("italic", 16); ?></button>' +
      '<button type="button" data-cmd="underline" title="Podčiarknuté" data-testid="rte-underline"><?php echo admin_icon("underline", 16); ?></button>' +
      '<button type="button" data-cmd="insertUnorderedList" title="Zoznam" data-testid="rte-ul"><?php echo admin_icon("list", 16); ?></button>' +
      '<button type="button" data-cmd="insertOrderedList" title="Číslovaný zoznam" data-testid="rte-ol"><?php echo admin_icon("list-ordered", 16); ?></button>' +
      '<button type="button" data-cmd="removeFormat" title="Vyčistiť formátovanie" data-testid="rte-clear"><?php echo admin_icon("eraser", 16); ?></button>' +
    '</div>' +
    '<div class="a-lang-pills" data-testid="editor-lang-pills">' + pillsHtml + '</div>' +
    '<div class="a-rte-box" contenteditable="true" id="rte-box" data-testid="editor-rte-box"></div>' +
    '<div class="a-inline-actions">' +
      '<button type="button" class="a-btn a-btn-sm" id="rte-save" data-testid="editor-save-btn"><?php echo admin_icon("upload", 14); ?> Uložiť</button>' +
      '<button type="button" class="a-btn a-btn-secondary a-btn-sm" id="rte-revert" data-testid="editor-revert-btn"><?php echo admin_icon("revert", 14); ?> Pôvodný text</button>' +
    '</div>';

  var curLang = activeLang;
  var rteBox = document.getElementById('rte-box');

  function loadLangValue(lang) {
    var override = overrides.text[lang] && overrides.text[lang][key];
    var val = override != null ? override : getByPath(i18nCache[lang], key);
    rteBox.innerHTML = val != null ? val : '';
    rteBox.setAttribute('dir', lang === 'ar' ? 'rtl' : 'ltr');
  }
  loadLangValue(curLang);

  panel.querySelectorAll('[data-cmd]').forEach(function (btn) {
    btn.addEventListener('click', function () {
      rteBox.focus();
      document.execCommand(btn.getAttribute('data-cmd'), false, null);
    });
  });

  panel.querySelectorAll('.a-lang-pill').forEach(function (pill) {
    pill.addEventListener('click', function () {
      panel.querySelectorAll('.a-lang-pill').forEach(function (p) { p.setAttribute('aria-pressed', 'false'); });
      pill.setAttribute('aria-pressed', 'true');
      curLang = pill.getAttribute('data-lang');
      loadLangValue(curLang);
    });
  });

  document.getElementById('rte-save').addEventListener('click', function () {
    var btn = this;
    btn.disabled = true;
    AdminAPI.apiFetch('/api/admin_content.php', { method: 'POST', body: { action: 'save_text', key: key, lang: curLang, value: rteBox.innerHTML } })
      .then(function (res) {
        if (res.data.success) {
          AdminAPI.toast('Uložené. Náhľad sa obnovuje…');
          fetchOverrides(true).then(function () { loadIframe(); });
        } else {
          AdminAPI.toast(res.data.error || 'Chyba', 'error');
        }
      }).finally(function () { btn.disabled = false; });
  });

  document.getElementById('rte-revert').addEventListener('click', function () {
    var btn = this;
    btn.disabled = true;
    AdminAPI.apiFetch('/api/admin_content.php', { method: 'POST', body: { action: 'revert_text', key: key, lang: curLang } })
      .then(function (res) {
        if (res.data.success) {
          AdminAPI.toast('Text bol vrátený na pôvodný.');
          fetchOverrides(true).then(function (ov) { overrides = ov; loadLangValue(curLang); loadIframe(); });
        } else {
          AdminAPI.toast(res.data.error || 'Chyba', 'error');
        }
      }).finally(function () { btn.disabled = false; });
  });
}

/* ---------- Image panel ---------- */
function openImagePanel(key, currentSrc) {
  panel.innerHTML =
    panelHeader(key) +
    '<img class="a-editor-img-preview" id="img-preview" src="' + AdminAPI.escapeHtml(currentSrc || '') + '" alt="" data-testid="editor-image-preview">' +
    '<input type="file" accept="image/jpeg,image/png,image/webp,image/gif" id="img-file" style="display:none;">' +
    '<div class="a-inline-actions">' +
      '<button type="button" class="a-btn a-btn-sm" id="img-change" data-testid="editor-image-change-btn"><?php echo admin_icon("upload", 14); ?> Zmeniť obrázok</button>' +
      '<button type="button" class="a-btn a-btn-secondary a-btn-sm" id="img-revert" data-testid="editor-image-revert-btn"><?php echo admin_icon("revert", 14); ?> Pôvodný obrázok</button>' +
    '</div>' +
    '<div class="a-hint">Nahrajte JPG, PNG, WEBP alebo GIF (max 5MB). Nový obrázok sa hneď zobrazí na stránke.</div>';

  var fileInput = document.getElementById('img-file');
  document.getElementById('img-change').addEventListener('click', function () { fileInput.click(); });

  fileInput.addEventListener('change', function () {
    var file = fileInput.files[0];
    if (!file) return;
    var fd = new FormData();
    fd.append('file', file);
    var btn = document.getElementById('img-change');
    btn.disabled = true;
    AdminAPI.apiFetch('/api/admin_upload.php', { method: 'POST', body: fd }).then(function (res) {
      if (!res.data.success) { AdminAPI.toast(res.data.error || 'Nahrávanie zlyhalo', 'error'); return; }
      return AdminAPI.apiFetch('/api/admin_content.php', { method: 'POST', body: { action: 'save_image', key: key, url: res.data.url } })
        .then(function (res2) {
          if (res2.data.success) {
            document.getElementById('img-preview').src = res.data.url;
            AdminAPI.toast('Obrázok bol zmenený. Náhľad sa obnovuje…');
            fetchOverrides(true).then(function () { loadIframe(); });
          } else {
            AdminAPI.toast(res2.data.error || 'Chyba', 'error');
          }
        });
    }).finally(function () { btn.disabled = false; fileInput.value = ''; });
  });

  document.getElementById('img-revert').addEventListener('click', function () {
    var btn = this;
    btn.disabled = true;
    AdminAPI.apiFetch('/api/admin_content.php', { method: 'POST', body: { action: 'revert_image', key: key } })
      .then(function (res) {
        if (res.data.success) {
          AdminAPI.toast('Obrázok bol vrátený na pôvodný.');
          fetchOverrides(true).then(function () { loadIframe(); });
        } else {
          AdminAPI.toast(res.data.error || 'Chyba', 'error');
        }
      }).finally(function () { btn.disabled = false; });
  });
}
</script>
</body>
</html>
