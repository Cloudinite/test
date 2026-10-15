/* ============================================================
   KMSSK.SK — popup.js
   Homepage announcement modal.
   - Fetches /api/announcements_popup.php
   - Shows only if there's a NEW published popup-flagged announcement
     (tracked via localStorage popup_version)
   - Has its OWN independent mini language toggle (chrome labels only)
     completely separate from the site-wide language switcher.
   - Because the modal scrim sits above the floating language button
     (z-index), the global language cannot be changed while the modal
     is open — so the background only ever reflects the globally
     selected language before the modal opens / after it closes.
   ============================================================ */
(function () {
  "use strict";

  if (!document.body.hasAttribute("data-page-popup")) return;
  // Suppress the popup entirely when this page is loaded inside the
  // webadmin Page Editor (?admin_edit=1) - it would otherwise sit on top
  // of the page and block clicks on editable hero/section elements.
  if (new URLSearchParams(window.location.search).get("admin_edit") === "1") return;

  var SEEN_KEY = "kmssk_popup_seen_version";
  var scrim = document.querySelector("[data-popup-scrim]");
  var box = document.querySelector("[data-popup-box]");
  var titleEl = document.querySelector("[data-popup-title]");
  var bodyEl = document.querySelector("[data-popup-body]");
  var dateEl = document.querySelector("[data-popup-date]");
  var badgeEl = document.querySelector("[data-popup-badge]");
  var closeBtns = document.querySelectorAll("[data-popup-close]");
  var miniLangBtns = document.querySelectorAll("[data-popup-lang]");

  if (!scrim || !box) return;

  var miniDictCache = {};
  var currentMiniLang = "sk";

  function loadMiniDict(lang) {
    if (miniDictCache[lang]) return Promise.resolve(miniDictCache[lang]);
    return fetch("/i18n/" + lang + ".json", { cache: "no-store" })
      .then(function (r) { return r.json(); })
      .then(function (d) { miniDictCache[lang] = d; return d; });
  }

  function applyMiniLang(lang) {
    currentMiniLang = lang;
    loadMiniDict(lang).then(function (dict) {
      if (badgeEl) badgeEl.textContent = dict.popup.badge;
      closeBtns.forEach(function (b) {
        if (b.closest("[data-popup-box]")) b.setAttribute("aria-label", dict.popup.close_label);
      });
      if (bodyEl) {
        if (lang === "ar") bodyEl.setAttribute("dir", "rtl");
        else bodyEl.removeAttribute("dir");
      }
      if (titleEl) {
        if (lang === "ar") titleEl.setAttribute("dir", "rtl");
        else titleEl.removeAttribute("dir");
      }
      miniLangBtns.forEach(function (btn) {
        btn.setAttribute("aria-pressed", btn.getAttribute("data-popup-lang") === lang ? "true" : "false");
      });
    });
  }

  miniLangBtns.forEach(function (btn) {
    btn.addEventListener("click", function () {
      applyMiniLang(btn.getAttribute("data-popup-lang"));
    });
  });

  function openModal() {
    // Close the mobile drawer first if it happens to be open, so its
    // scrim (z-index 90) never gets stuck underneath the popup scrim
    // (z-index 120) and blocking the drawer's own close button.
    var drawer = document.querySelector("[data-drawer]");
    var drawerScrim = document.querySelector("[data-drawer-scrim]");
    if (drawer && drawer.getAttribute("data-open") === "true") {
      drawer.setAttribute("data-open", "false");
      if (drawerScrim) drawerScrim.setAttribute("data-open", "false");
    }
    scrim.setAttribute("data-open", "true");
    document.body.classList.add("drawer-locked");
    box.focus();
  }
  function closeModal() {
    scrim.setAttribute("data-open", "false");
    document.body.classList.remove("drawer-locked");
  }
  closeBtns.forEach(function (b) { b.addEventListener("click", closeModal); });
  scrim.addEventListener("click", function (e) { if (e.target === scrim) closeModal(); });
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape" && scrim.getAttribute("data-open") === "true") closeModal();
  });

  function getSeenVersion() {
    try { return localStorage.getItem(SEEN_KEY); } catch (e) { return null; }
  }
  function setSeenVersion(v) {
    try { localStorage.setItem(SEEN_KEY, v); } catch (e) { /* ignore */ }
  }

  fetch("/api/announcements_popup.php", { cache: "no-store" })
    .then(function (r) { return r.json(); })
    .then(function (data) {
      if (!data || !data.success || !data.announcement) return;
      var ann = data.announcement;
      var seen = getSeenVersion();
      if (seen === ann.popup_version) return; // already shown

      if (titleEl) titleEl.textContent = ann.title;
      if (bodyEl) bodyEl.textContent = ann.body;
      if (dateEl && ann.published_at) {
        var d = new Date(ann.published_at.replace(" ", "T"));
        if (!isNaN(d.getTime())) {
          dateEl.textContent = d.toLocaleDateString(undefined, { day: "2-digit", month: "long", year: "numeric" });
        }
      }

      var initialLang = (window.KMSSK_LANG) || "sk";
      applyMiniLang(initialLang);

      setTimeout(function () {
        openModal();
        setSeenVersion(ann.popup_version);
      }, 260);
    })
    .catch(function (err) { console.warn("Popup fetch failed:", err); });
})();
