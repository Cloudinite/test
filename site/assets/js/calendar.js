/* ============================================================
   KMSSK.SK — calendar.js
   Month grid + upcoming/ongoing list, fed by /api/events_list.php
   ============================================================ */
(function () {
  "use strict";

  var root = document.querySelector("[data-calendar-root]");
  if (!root) return;

  var monthGrid = document.querySelector("[data-calendar-month]");
  var monthLabel = document.querySelector("[data-calendar-month-label]");
  var prevBtn = document.querySelector("[data-calendar-prev]");
  var nextBtn = document.querySelector("[data-calendar-next]");
  var listEl = document.querySelector("[data-calendar-list]");
  var filterChips = document.querySelectorAll("[data-calendar-filter]");
  var dowNames = { sk: ["Po","Ut","St","Št","Pi","So","Ne"], en: ["Mo","Tu","We","Th","Fr","Sa","Su"], ru: ["Пн","Вт","Ср","Чт","Пт","Сб","Нд"], de: ["Mo","Di","Mi","Do","Fr","Sa","So"], ar: ["ن","ث","ر","خ","ج","س","ح"] };
  var monthNames = {
    sk: ["Január","Február","Marec","Apríl","Máj","Jún","Júl","August","September","Október","November","December"],
    en: ["January","February","March","April","May","June","July","August","September","October","November","December"],
    ru: ["Январь","Февраль","Март","Апрель","Май","Июнь","Июль","Август","Сентябрь","Октябрь","Ноябрь","Декабрь"],
    de: ["Januar","Februar","März","April","Mai","Juni","Juli","August","September","Oktober","November","Dezember"],
    ar: ["يناير","فبراير","مارس","أبريل","مايو","يونيو","يوليو","أغسطس","سبتمبر","أكتوبر","نوفمبر","ديسمبر"]
  };

  var state = {
    events: [],
    viewDate: new Date(),
    selectedDay: null,
    filter: "all"
  };

  function curLang() { return (window.KMSSK_LANG) || "sk"; }
  function t(key, fallback) {
    var dict = window.KMSSK_DICT;
    if (!dict) return fallback;
    var val = window.KMSSK_I18N ? window.KMSSK_I18N.getByPath(dict, key) : null;
    return val != null ? val : fallback;
  }

  function fetchEvents() {
    return fetch("/api/events_list.php?scope=all", { cache: "no-store" })
      .then(function (r) { return r.json(); })
      .then(function (data) {
        state.events = (data && data.items) ? data.items : [];
        render();
      })
      .catch(function () {
        if (listEl) listEl.innerHTML = '<div class="calendar-empty">' + t("common.error_generic", "Something went wrong.") + "</div>";
      });
  }

  function dateKey(dt) {
    var d = new Date(dt.replace(" ", "T"));
    return d.getFullYear() + "-" + (d.getMonth() + 1) + "-" + d.getDate();
  }

  function fmtDay(dt) {
    var d = new Date(dt.replace(" ", "T"));
    return { d: d.getDate(), m: monthNames[curLang()][d.getMonth()].slice(0, 3).toUpperCase() };
  }

  function renderMonthGrid() {
    if (!monthGrid) return;
    var lang = curLang();
    var y = state.viewDate.getFullYear();
    var m = state.viewDate.getMonth();
    monthLabel.textContent = monthNames[lang][m] + " " + y;

    var firstDow = new Date(y, m, 1).getDay(); // 0=Sun
    firstDow = (firstDow + 6) % 7; // convert to Mon=0
    var daysInMonth = new Date(y, m + 1, 0).getDate();
    var today = new Date();

    var eventsByDay = {};
    state.events.forEach(function (ev) {
      var k = dateKey(ev.start_dt);
      if (!eventsByDay[k]) eventsByDay[k] = [];
      eventsByDay[k].push(ev);
    });

    var html = "";
    dowNames[lang].forEach(function (dn) { html += '<div class="dow">' + dn + "</div>"; });

    for (var i = 0; i < firstDow; i++) html += '<div class="calendar-day is-outside"></div>';

    for (var day = 1; day <= daysInMonth; day++) {
      var key = y + "-" + (m + 1) + "-" + day;
      var evs = eventsByDay[key] || [];
      var isToday = today.getFullYear() === y && today.getMonth() === m && today.getDate() === day;
      var isSelected = state.selectedDay === key;
      var cls = "calendar-day" + (isToday ? " is-today" : "") + (isSelected ? " is-selected" : "");
      var dots = "";
      for (var dd = 0; dd < Math.min(evs.length, 3); dd++) dots += "<span></span>";
      html += '<button type="button" class="' + cls + '" data-day-key="' + key + '" aria-label="' + day + " " + monthNames[lang][m] + (evs.length ? ", " + evs.length + " " : "") + '">' +
        "<span>" + day + "</span>" + (dots ? '<span class="dots">' + dots + "</span>" : "") + "</button>";
    }

    monthGrid.innerHTML = html;

    monthGrid.querySelectorAll("[data-day-key]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        var key = btn.getAttribute("data-day-key");
        state.selectedDay = state.selectedDay === key ? null : key;
        renderMonthGrid();
        renderList();
      });
    });
  }

  function statusLabel(status) {
    return t("common.status_" + status, status);
  }

  function renderList() {
    if (!listEl) return;
    var lang = curLang();
    var filtered = state.events.filter(function (ev) {
      if (state.selectedDay) {
        return dateKey(ev.start_dt).replace(/^(\d+)-(\d+)-(\d+)$/, "$1-$2-$3") === state.selectedDay;
      }
      if (state.filter === "all") return ev.status !== "cancelled" || true;
      if (state.filter === "upcoming") return ev.status === "upcoming" || ev.status === "ongoing";
      return ev.status === state.filter;
    });

    filtered.sort(function (a, b) { return new Date(a.start_dt) - new Date(b.start_dt); });

    if (!filtered.length) {
      listEl.innerHTML = '<div class="card calendar-empty">' + t("common.no_events", "No events scheduled right now.") + "</div>";
      return;
    }

    var html = "";
    filtered.forEach(function (ev) {
      var dp = fmtDay(ev.start_dt);
      var d = new Date(ev.start_dt.replace(" ", "T"));
      var timeStr = d.toLocaleTimeString(undefined, { hour: "2-digit", minute: "2-digit" });
      html += '<div class="card event-card" data-testid="event-card-' + ev.id + '">' +
        '<div class="event-date-pill"><span class="d">' + dp.d + '</span><span class="m">' + dp.m + "</span></div>" +
        '<div class="event-body">' +
        '<span class="event-status ' + ev.status + '">' + statusLabel(ev.status) + "</span>" +
        "<h3>" + escapeHtml(ev.title) + "</h3>" +
        '<div class="event-meta">' +
        '<span>&#128197; ' + timeStr + "</span>" +
        (ev.location ? '<span>&#128205; ' + escapeHtml(ev.location) + "</span>" : "") +
        "</div>" +
        (ev.description ? "<p>" + escapeHtml(ev.description) + "</p>" : "") +
        "</div></div>";
    });
    listEl.innerHTML = html;
  }

  function escapeHtml(s) {
    var div = document.createElement("div");
    div.textContent = s || "";
    return div.innerHTML;
  }

  function render() {
    renderMonthGrid();
    renderList();
  }

  if (prevBtn) prevBtn.addEventListener("click", function () {
    state.viewDate.setMonth(state.viewDate.getMonth() - 1);
    renderMonthGrid();
  });
  if (nextBtn) nextBtn.addEventListener("click", function () {
    state.viewDate.setMonth(state.viewDate.getMonth() + 1);
    renderMonthGrid();
  });

  filterChips.forEach(function (chip) {
    chip.addEventListener("click", function () {
      filterChips.forEach(function (c) { c.setAttribute("aria-pressed", "false"); });
      chip.setAttribute("aria-pressed", "true");
      state.filter = chip.getAttribute("data-calendar-filter");
      state.selectedDay = null;
      renderMonthGrid();
      renderList();
    });
  });

  window.addEventListener("kmssk:i18n-applied", function () { render(); });

  fetchEvents();
})();
