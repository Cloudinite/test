/* ============================================================
   KMSSK.SK — contact.js — contact form submit handler
   ============================================================ */
(function () {
  "use strict";
  var form = document.querySelector("[data-contact-form]");
  if (!form) return;

  var msgEl = document.querySelector("[data-contact-msg]");
  var submitBtn = form.querySelector('button[type="submit"]');

  function t(key, fallback) {
    var dict = window.KMSSK_DICT;
    if (!dict || !window.KMSSK_I18N) return fallback;
    var v = window.KMSSK_I18N.getByPath(dict, key);
    return v != null ? v : fallback;
  }

  function showMsg(type, text) {
    if (!msgEl) return;
    msgEl.textContent = text;
    msgEl.className = "form-msg show " + type;
  }

  form.addEventListener("submit", function (e) {
    e.preventDefault();
    var name = form.querySelector('[name="name"]').value.trim();
    var email = form.querySelector('[name="email"]').value.trim();
    var phone = form.querySelector('[name="phone"]').value.trim();
    var message = form.querySelector('[name="message"]').value.trim();
    var website = form.querySelector('[name="website"]');

    if (!name || !email || !message) {
      showMsg("error", t("contact.form_error_required", "Please fill in your name, email and message."));
      return;
    }
    var emailRe = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
    if (!emailRe.test(email)) {
      showMsg("error", t("contact.form_error_email", "Please enter a valid email address."));
      return;
    }

    submitBtn.setAttribute("aria-disabled", "true");
    submitBtn.disabled = true;
    var originalLabel = submitBtn.textContent;
    submitBtn.textContent = t("contact.form_sending", "Sending…");

    fetch("/api/contact_submit.php", {
      method: "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({
        name: name, email: email, phone: phone, message: message,
        lang: window.KMSSK_LANG || "sk",
        website: website ? website.value : ""
      })
    })
      .then(function (r) { return r.json().then(function (d) { return { ok: r.ok, data: d }; }); })
      .then(function (res) {
        if (res.ok && res.data.success) {
          showMsg("success", t("contact.form_success", "Thank you! Your message has been sent."));
          form.reset();
        } else {
          showMsg("error", (res.data && res.data.error) || t("common.error_generic", "Something went wrong."));
        }
      })
      .catch(function () {
        showMsg("error", t("common.error_generic", "Something went wrong."));
      })
      .finally(function () {
        submitBtn.removeAttribute("aria-disabled");
        submitBtn.disabled = false;
        submitBtn.textContent = originalLabel;
      });
  });
})();
