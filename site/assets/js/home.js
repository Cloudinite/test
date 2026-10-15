/* ============================================================
   KMSSK.SK — home.js
   Homepage teasers: latest announcements + upcoming events
   ============================================================ */
(function () {
  "use strict";
  var annList = document.querySelector("[data-home-announcements]");
  var evList = document.querySelector("[data-home-events]");
  if (!annList && !evList) return;

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

  function loadAnnouncements() {
    if (!annList) return;
    fetch("/api/announcements_list.php?audience=public&limit=3", { cache: "no-store" })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        var items = (data && data.items) || [];
        if (!items.length) {
          annList.innerHTML = '<div class="card card-pad">' + t("home.announcements_empty", t("common.no_announcements", "No announcements right now.")) + "</div>";
          return;
        }
        var html = "";
        items.forEach(function (a) {
          var d = a.published_at ? new Date(a.published_at.replace(" ", "T")) : null;
          var dateStr = d ? d.toLocaleDateString(undefined, { day: "2-digit", month: "short", year: "numeric" }) : "";
          html += '<div class="card announcement-card" data-testid="home-announcement-' + a.id + '">' +
            '<div class="badge-row"><span class="badge"><span class="badge-dot"></span>' + escapeHtml(dateStr) + "</span></div>" +
            "<h3>" + escapeHtml(a.title) + "</h3>" +
            "<p>" + escapeHtml(a.body.length > 160 ? a.body.slice(0, 160) + "…" : a.body) + "</p>" +
            "</div>";
        });
        annList.innerHTML = html;
      })
      .catch(function () {
        annList.innerHTML = '<div class="card card-pad">' + t("common.error_generic", "Something went wrong.") + "</div>";
      });
  }

  function loadEvents() {
    if (!evList) return;
    fetch("/api/events_list.php?scope=upcoming", { cache: "no-store" })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        var items = ((data && data.items) || []).slice(0, 3);
        if (!items.length) {
          evList.innerHTML = '<div class="card card-pad">' + t("common.no_events", "No events scheduled right now.") + "</div>";
          return;
        }
        var html = "";
        items.forEach(function (ev) {
          var d = new Date(ev.start_dt.replace(" ", "T"));
          var day = d.getDate();
          var month = d.toLocaleDateString(undefined, { month: "short" }).toUpperCase();
          var timeStr = d.toLocaleTimeString(undefined, { hour: "2-digit", minute: "2-digit" });
          html += '<div class="card event-card" data-testid="home-event-' + ev.id + '">' +
            '<div class="event-date-pill"><span class="d">' + day + '</span><span class="m">' + month + "</span></div>" +
            '<div class="event-body">' +
            '<span class="event-status ' + ev.status + '">' + t("common.status_" + ev.status, ev.status) + "</span>" +
            "<h3>" + escapeHtml(ev.title) + "</h3>" +
            '<div class="event-meta"><span>&#128197; ' + timeStr + "</span>" +
            (ev.location ? '<span>&#128205; ' + escapeHtml(ev.location) + "</span>" : "") + "</div>" +
            "</div></div>";
        });
        evList.innerHTML = html;
      })
      .catch(function () {
        evList.innerHTML = '<div class="card card-pad">' + t("common.error_generic", "Something went wrong.") + "</div>";
      });
  }

  loadAnnouncements();
  loadEvents();
  window.addEventListener("kmssk:i18n-applied", function () { loadAnnouncements(); loadEvents(); });
})();
