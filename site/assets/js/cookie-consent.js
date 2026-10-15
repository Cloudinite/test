/* ============================================================
   KMSSK.SK — cookie-consent.js
   Simple GDPR-style cookie/privacy notice. Shown once per visitor
   (persisted in localStorage) with a link to the Privacy Policy
   page. A complete no-op inside the webadmin Page Editor iframe.
   ============================================================ */
(function () {
  "use strict";

  if (new URLSearchParams(window.location.search).get("admin_edit") === "1") return;

  var KEY = "kmssk_cookie_consent";
  var banner = document.querySelector("[data-cookie-banner]");
  if (!banner) return;

  function getConsent() {
    try { return localStorage.getItem(KEY); } catch (e) { return null; }
  }
  function setConsent(value) {
    try { localStorage.setItem(KEY, value); } catch (e) { /* ignore */ }
  }

  function show() {
    banner.hidden = false;
    document.body.classList.add("has-cookie-banner");
    requestAnimationFrame(function () {
      banner.classList.add("is-visible");
    });
  }

  function dismiss(value) {
    setConsent(value);
    banner.classList.remove("is-visible");
    document.body.classList.remove("has-cookie-banner");
    window.setTimeout(function () { banner.hidden = true; }, 250);
  }

  if (!getConsent()) {
    window.setTimeout(show, 400);
  }

  var acceptBtn = banner.querySelector("[data-cookie-accept]");
  var declineBtn = banner.querySelector("[data-cookie-decline]");
  if (acceptBtn) acceptBtn.addEventListener("click", function () { dismiss("accepted"); });
  if (declineBtn) declineBtn.addEventListener("click", function () { dismiss("declined"); });
})();
