/* ============================================================
   KMSSK.SK — main.js
   Header/nav, mobile drawer, floating action rail (scroll-to-top +
   language menu), page transitions, reveal-on-scroll, FAQ accordion,
   embed-mode detection.
   ============================================================ */
(function () {
  "use strict";

  /* ---------- Embed mode ---------- */
  var params = new URLSearchParams(window.location.search);
  if (params.get("embed") === "1") {
    document.body.setAttribute("data-embed", "true");
  }

  /* ---------- Mobile Drawer ---------- */
  var hamburger = document.querySelector("[data-drawer-open]");
  var drawer = document.querySelector("[data-drawer]");
  var scrim = document.querySelector("[data-drawer-scrim]");
  var drawerCloseBtns = document.querySelectorAll("[data-drawer-close]");

  function openDrawer() {
    if (!drawer) return;
    // Don't open the mobile drawer while the announcement popup is showing.
    var popupScrim = document.querySelector("[data-popup-scrim]");
    if (popupScrim && popupScrim.getAttribute("data-open") === "true") return;
    drawer.setAttribute("data-open", "true");
    if (scrim) scrim.setAttribute("data-open", "true");
    document.body.classList.add("drawer-locked");
    if (hamburger) hamburger.setAttribute("aria-expanded", "true");
    var firstLink = drawer.querySelector("a");
    if (firstLink) firstLink.focus();
  }
  function closeDrawer() {
    if (!drawer) return;
    drawer.setAttribute("data-open", "false");
    if (scrim) scrim.setAttribute("data-open", "false");
    document.body.classList.remove("drawer-locked");
    if (hamburger) { hamburger.setAttribute("aria-expanded", "false"); hamburger.focus(); }
  }
  if (hamburger) hamburger.addEventListener("click", openDrawer);
  drawerCloseBtns.forEach(function (b) { b.addEventListener("click", closeDrawer); });
  if (scrim) scrim.addEventListener("click", closeDrawer);
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape" && drawer && drawer.getAttribute("data-open") === "true") closeDrawer();
  });

  /* ---------- Floating Action Rail ---------- */
  var scrollBtn = document.querySelector("[data-scroll-top]");
  var langBtn = document.querySelector("[data-lang-toggle]");
  var langMenu = document.querySelector("[data-lang-menu]");

  function onScroll() {
    if (!scrollBtn) return;
    if (window.scrollY > 320) scrollBtn.classList.add("is-visible");
    else scrollBtn.classList.remove("is-visible");
  }
  window.addEventListener("scroll", onScroll, { passive: true });
  onScroll();

  if (scrollBtn) {
    scrollBtn.addEventListener("click", function () {
      window.scrollTo({ top: 0, behavior: "smooth" });
    });
  }

  function closeLangMenu() {
    if (langMenu) langMenu.setAttribute("data-open", "false");
    if (langBtn) langBtn.setAttribute("aria-expanded", "false");
  }
  if (langBtn && langMenu) {
    langBtn.addEventListener("click", function (e) {
      e.stopPropagation();
      var open = langMenu.getAttribute("data-open") === "true";
      langMenu.setAttribute("data-open", open ? "false" : "true");
      langBtn.setAttribute("aria-expanded", open ? "false" : "true");
    });
    document.addEventListener("click", function (e) {
      if (!langMenu.contains(e.target) && e.target !== langBtn) closeLangMenu();
    });
    document.addEventListener("keydown", function (e) {
      if (e.key === "Escape") closeLangMenu();
    });
    langMenu.querySelectorAll("[data-lang-option]").forEach(function (btn) {
      btn.addEventListener("click", function () {
        var lang = btn.getAttribute("data-lang-option");
        if (window.KMSSK_I18N) window.KMSSK_I18N.setLanguage(lang);
        closeLangMenu();
      });
    });
  }

  /* ---------- Active nav link ---------- */
  var path = window.location.pathname.split("/").pop() || "index.html";
  document.querySelectorAll(".site-nav a, .drawer-nav a").forEach(function (a) {
    var href = a.getAttribute("href");
    if (href === path || (path === "" && href === "index.html")) {
      a.setAttribute("aria-current", "page");
    }
  });

  /* ---------- Page transition on internal navigation ---------- */
  document.querySelectorAll('a[href$=".html"]').forEach(function (a) {
    if (a.hostname !== window.location.hostname) return;
    if (a.target === "_blank") return;
    a.addEventListener("click", function (e) {
      var href = a.getAttribute("href");
      if (!href || href.indexOf("#") === 0) return;
      e.preventDefault();
      document.body.setAttribute("data-page-state", "out");
      setTimeout(function () { window.location.href = href; }, 220);
    });
  });
  window.addEventListener("pageshow", function () {
    document.body.removeAttribute("data-page-state");
  });

  /* ---------- Reveal on scroll ---------- */
  var revealEls = document.querySelectorAll("[data-reveal]");
  if ("IntersectionObserver" in window && revealEls.length) {
    var io = new IntersectionObserver(function (entries) {
      entries.forEach(function (entry) {
        if (entry.isIntersecting) {
          entry.target.classList.add("is-visible");
          io.unobserve(entry.target);
        }
      });
    }, { threshold: 0.12, rootMargin: "0px 0px -40px 0px" });
    revealEls.forEach(function (el) { io.observe(el); });
  } else {
    revealEls.forEach(function (el) { el.classList.add("is-visible"); });
  }

  /* ---------- FAQ accordion ---------- */
  document.querySelectorAll("[data-faq-trigger]").forEach(function (trigger) {
    trigger.addEventListener("click", function () {
      var expanded = trigger.getAttribute("aria-expanded") === "true";
      var contentId = trigger.getAttribute("aria-controls");
      var content = document.getElementById(contentId);
      trigger.setAttribute("aria-expanded", expanded ? "false" : "true");
      if (content) {
        if (expanded) {
          content.style.maxHeight = "0px";
        } else {
          content.style.maxHeight = content.scrollHeight + "px";
        }
      }
    });
  });

  /* ---------- Copy-to-clipboard (embed code) ---------- */
  document.querySelectorAll("[data-copy-target]").forEach(function (btn) {
    btn.addEventListener("click", function () {
      var targetSel = btn.getAttribute("data-copy-target");
      var target = document.querySelector(targetSel);
      if (!target) return;
      var text = target.textContent;
      var done = function () {
        var original = btn.getAttribute("data-copy-label") || btn.textContent;
        var copiedLabel = btn.getAttribute("data-copied-label") || "Copied!";
        btn.textContent = copiedLabel;
        setTimeout(function () { btn.textContent = original; }, 1800);
      };
      if (navigator.clipboard && navigator.clipboard.writeText) {
        navigator.clipboard.writeText(text).then(done).catch(function () {
          fallbackCopy(text); done();
        });
      } else {
        fallbackCopy(text); done();
      }
    });
  });
  function fallbackCopy(text) {
    var ta = document.createElement("textarea");
    ta.value = text; ta.style.position = "fixed"; ta.style.left = "-9999px";
    document.body.appendChild(ta); ta.select();
    try { document.execCommand("copy"); } catch (e) { /* ignore */ }
    document.body.removeChild(ta);
  }
})();
