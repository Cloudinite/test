/* ============================================================
   KMSSK.SK webadmin — shared JS helpers
   ============================================================ */
window.AdminAPI = (function () {
  "use strict";

  function apiFetch(url, opts) {
    opts = opts || {};
    opts.credentials = "same-origin";
    opts.headers = opts.headers || {};
    if (window.CSRF_TOKEN) opts.headers["X-Csrf-Token"] = window.CSRF_TOKEN;
    if (opts.body && typeof opts.body !== "string" && !(opts.body instanceof FormData)) {
      opts.headers["Content-Type"] = "application/json";
      opts.body = JSON.stringify(opts.body);
    }
    if (opts.body && typeof opts.body === "object" && !(opts.body instanceof FormData)) {
      opts.headers["Content-Type"] = "application/json";
    }
    return fetch(url, opts).then(function (r) {
      return r.json().then(function (data) { return { ok: r.ok, status: r.status, data: data }; });
    });
  }

  function toast(msg, type) {
    var el = document.querySelector(".a-toast");
    if (!el) {
      el = document.createElement("div");
      el.className = "a-toast";
      document.body.appendChild(el);
    }
    el.textContent = msg;
    el.className = "a-toast show" + (type === "error" ? " error" : "");
    setTimeout(function () { el.classList.remove("show"); }, 3200);
  }

  function escapeHtml(s) {
    var div = document.createElement("div");
    div.textContent = s == null ? "" : String(s);
    return div.innerHTML;
  }

  function fmtDate(s) {
    if (!s) return "—";
    var d = new Date(String(s).replace(" ", "T"));
    if (isNaN(d.getTime())) return s;
    return d.toLocaleDateString("sk-SK", { day: "2-digit", month: "2-digit", year: "numeric" }) +
      " " + d.toLocaleTimeString("sk-SK", { hour: "2-digit", minute: "2-digit" });
  }

  // Mobile sidebar toggle
  document.addEventListener("DOMContentLoaded", function () {
    var burger = document.querySelector("[data-admin-burger]");
    var sidebar = document.querySelector(".a-sidebar");
    if (burger && sidebar) {
      burger.addEventListener("click", function () { sidebar.classList.toggle("open"); });
    }
    var logoutBtn = document.querySelector("[data-admin-logout]");
    if (logoutBtn) {
      logoutBtn.addEventListener("click", function () {
        apiFetch("/api/admin_logout.php", { method: "POST" }).then(function () {
          window.location.href = "index.php";
        });
      });
    }
  });

  return { apiFetch: apiFetch, toast: toast, escapeHtml: escapeHtml, fmtDate: fmtDate };
})();
