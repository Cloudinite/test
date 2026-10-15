/* ============================================================
   KMSSK.SK — announcements.js (oznamy.html archive page)
   ============================================================ */
(function () {
  "use strict";
  var list = document.querySelector("[data-announcements-list]");
  if (!list) return;

  function t(key, fallback) {
    var dict = window.KMSSK_DICT;
    if (!dict || !window.KMSSK_I18N) return fallback;
    var v = window.KMSSK_I18N.getByPath(dict, key);
    return v != null ? v : fallback;
  }
  function escapeHtml(s) {
    var div = document.createElement("div");
    div.textContent = s || "";
    return div.innerHTML;
  }

  function load() {
    list.innerHTML = '<div class="skeleton skeleton-line" style="width:70%"></div><div class="skeleton skeleton-line" style="width:90%"></div><div class="skeleton skeleton-line" style="width:60%"></div>';
    fetch("/api/announcements_list.php?audience=public&limit=50", { cache: "no-store" })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        var items = (data && data.items) || [];
        if (!items.length) {
          list.innerHTML = '<div class="card card-pad">' + t("announcements.empty", "There are currently no announcements.") + "</div>";
          return;
        }
        var html = "";
        items.forEach(function (a) {
          var d = a.published_at ? new Date(a.published_at.replace(" ", "T")) : null;
          var dateStr = d ? d.toLocaleDateString(undefined, { day: "2-digit", month: "long", year: "numeric" }) : "";
          html += '<div class="card announcement-card" data-reveal data-testid="announcement-' + a.id + '">' +
            '<div class="badge-row"><span class="badge"><span class="badge-dot"></span>' + escapeHtml(dateStr) + "</span></div>" +
            "<h3>" + escapeHtml(a.title) + "</h3>" +
            "<p>" + escapeHtml(a.body) + "</p>" +
            "</div>";
        });
        list.innerHTML = html;
        document.querySelectorAll("[data-reveal]").forEach(function (el) { el.classList.add("is-visible"); });
      })
      .catch(function () {
        list.innerHTML = '<div class="card card-pad">' + t("common.error_generic", "Something went wrong.") + "</div>";
      });
  }

  load();
  window.addEventListener("kmssk:i18n-applied", load);
})();
