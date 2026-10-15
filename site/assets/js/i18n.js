/* ============================================================
   KMSSK.SK — i18n loader
   - Fetches /i18n/{lang}.json and applies to [data-i18n] nodes
   - Cross-fades #app-content on language change
   - Arabic: RTL applied ONLY to [data-i18n-dir] text nodes, never html/body
   ============================================================ */
(function () {
  "use strict";

  var SUPPORTED = ["sk", "en", "ru", "de", "ar"];
  var DEFAULT_LANG = "sk";
  var STORAGE_KEY = "kmssk_lang";
  var cache = {};
  var overridesCache = null;

  function getSavedLang() {
    try {
      var v = localStorage.getItem(STORAGE_KEY);
      if (v && SUPPORTED.indexOf(v) !== -1) return v;
    } catch (e) { /* localStorage unavailable, use default */ }
    return DEFAULT_LANG;
  }

  function saveLang(lang) {
    try { localStorage.setItem(STORAGE_KEY, lang); } catch (e) { /* ignore */ }
  }

  function getByPath(obj, path) {
    var parts = path.split(".");
    var cur = obj;
    for (var i = 0; i < parts.length; i++) {
      if (cur == null) return null;
      cur = cur[parts[i]];
    }
    return cur;
  }

  function loadLocale(lang) {
    if (cache[lang]) return Promise.resolve(cache[lang]);
    return fetch("/i18n/" + lang + ".json", { cache: "no-store" })
      .then(function (res) {
        if (!res.ok) throw new Error("Failed to load locale " + lang);
        return res.json();
      })
      .then(function (data) {
        cache[lang] = data;
        return data;
      });
  }

  // Page-Editor overrides (admin-customized text/images), merged on top of
  // the shipped /i18n/{lang}.json defaults. Loaded once per page view.
  function loadOverrides() {
    if (overridesCache) return Promise.resolve(overridesCache);
    return fetch("/api/content_overrides.php", { cache: "no-store" })
      .then(function (res) { return res.json(); })
      .then(function (data) {
        overridesCache = (data && data.success) ? { text: data.text || {}, images: data.images || {} } : { text: {}, images: {} };
        return overridesCache;
      })
      .catch(function () {
        overridesCache = { text: {}, images: {} };
        return overridesCache;
      });
  }

  function applyTranslations(dict, lang, overrides) {
    var isArabic = lang === "ar";
    overrides = overrides || { text: {}, images: {} };
    var langOverrides = overrides.text[lang] || {};

    document.querySelectorAll("[data-i18n]").forEach(function (el) {
      var key = el.getAttribute("data-i18n");
      var override = langOverrides[key];
      var val = override != null ? override : getByPath(dict, key);
      if (val == null) return;
      // innerHTML (not textContent): admin Page-Editor overrides may contain
      // basic rich-text markup (bold/italic/lists). Values always originate
      // from our own JSON files or admin-authored DB content, never raw
      // third-party input, so this is safe.
      el.innerHTML = val;
      if (el.hasAttribute("data-i18n-dir")) {
        if (isArabic) { el.setAttribute("dir", "rtl"); }
        else { el.removeAttribute("dir"); }
      }
    });

    document.querySelectorAll("[data-i18n-html]").forEach(function (el) {
      var key = el.getAttribute("data-i18n-html");
      var override = langOverrides[key];
      var val = override != null ? override : getByPath(dict, key);
      if (val == null) return;
      el.innerHTML = val;
      if (el.hasAttribute("data-i18n-dir") && isArabic) el.setAttribute("dir", "rtl");
    });

    // Editable images (Page Editor): language-independent overrides.
    document.querySelectorAll("[data-edit-img]").forEach(function (el) {
      var key = el.getAttribute("data-edit-img");
      var url = overrides.images[key];
      if (url) el.setAttribute("src", url);
    });

    document.querySelectorAll("[data-i18n-placeholder]").forEach(function (el) {
      var key = el.getAttribute("data-i18n-placeholder");
      var val = getByPath(dict, key);
      if (val != null) el.setAttribute("placeholder", val);
    });

    document.querySelectorAll("[data-i18n-aria-label]").forEach(function (el) {
      var key = el.getAttribute("data-i18n-aria-label");
      var val = getByPath(dict, key);
      if (val != null) el.setAttribute("aria-label", val);
    });

    document.querySelectorAll("[data-i18n-title]").forEach(function (el) {
      var key = el.getAttribute("data-i18n-title");
      var val = getByPath(dict, key);
      if (val != null) document.title = val;
    });

    document.querySelectorAll("[data-i18n-content]").forEach(function (el) {
      var key = el.getAttribute("data-i18n-content");
      var val = getByPath(dict, key);
      if (val != null) el.setAttribute("content", val);
    });

    document.body.classList.toggle("lang-ar", isArabic);
    document.querySelectorAll(".fab-lang-code").forEach(function (el) {
      el.textContent = lang.toUpperCase();
    });
    document.querySelectorAll("[data-lang-option]").forEach(function (el) {
      var isCurrent = el.getAttribute("data-lang-option") === lang;
      el.setAttribute("aria-checked", isCurrent ? "true" : "false");
    });

    window.dispatchEvent(new CustomEvent("kmssk:i18n-applied", { detail: { lang: lang, dict: dict } }));
  }

  function setLanguage(lang, opts) {
    opts = opts || {};
    if (SUPPORTED.indexOf(lang) === -1) lang = DEFAULT_LANG;

    var content = document.getElementById("app-content");
    var doFade = !opts.skipFade && content;

    function apply() {
      Promise.all([loadLocale(lang), loadOverrides()]).then(function (results) {
        var dict = results[0];
        var overrides = results[1];
        applyTranslations(dict, lang, overrides);
        window.KMSSK_LANG = lang;
        window.KMSSK_DICT = dict;
        if (doFade) {
          requestAnimationFrame(function () {
            content.removeAttribute("data-i18n-state");
          });
        }
      }).catch(function (err) {
        console.error(err);
        if (doFade) content.removeAttribute("data-i18n-state");
      });
    }

    if (!opts.silent) saveLang(lang);

    if (doFade) {
      content.setAttribute("data-i18n-state", "out");
      setTimeout(apply, 220);
    } else {
      apply();
    }
  }

  window.KMSSK_I18N = {
    setLanguage: setLanguage,
    getSavedLang: getSavedLang,
    loadLocale: loadLocale,
    getByPath: getByPath,
    SUPPORTED: SUPPORTED
  };

  document.addEventListener("DOMContentLoaded", function () {
    var lang = getSavedLang();
    setLanguage(lang, { skipFade: true, silent: true });
  });
})();
