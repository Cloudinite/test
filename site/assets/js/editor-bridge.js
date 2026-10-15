/* ============================================================
   KMSSK.SK — editor-bridge.js
   Loaded on every public page but is a complete no-op unless the
   page is opened inside the webadmin "Editor stránok" iframe with
   ?admin_edit=1 in the URL. When active, it makes every editable
   text/image block inside <main> clickable and reports the click
   (content key + current language) to the parent admin window via
   postMessage so the right-hand edit panel can open.
   ============================================================ */
(function () {
  "use strict";

  var params = new URLSearchParams(window.location.search);
  if (params.get("admin_edit") !== "1") return;
  if (window.self === window.top) return; // only inside the editor iframe

  function markEditable() {
    var style = document.createElement("style");
    style.textContent =
      ".kmssk-editable{outline:2px dashed transparent;outline-offset:3px;cursor:pointer;transition:outline-color .15s ease,background-color .15s ease;border-radius:4px;}" +
      ".kmssk-editable:hover{outline-color:#1F5A43;background-color:rgba(31,90,67,.07);}" +
      ".kmssk-editable.kmssk-selected{outline-color:#9A5A2A;outline-style:solid;background-color:rgba(154,90,42,.08);}";
    document.head.appendChild(style);

    var nodes = document.querySelectorAll(
      "#main [data-i18n], #main [data-i18n-html], #main [data-edit-img]"
    );
    nodes.forEach(function (el) {
      el.classList.add("kmssk-editable");
      el.addEventListener(
        "click",
        function (e) {
          e.preventDefault();
          e.stopPropagation();
          document.querySelectorAll(".kmssk-selected").forEach(function (n) {
            n.classList.remove("kmssk-selected");
          });
          el.classList.add("kmssk-selected");

          var isImage = el.hasAttribute("data-edit-img");
          var key = isImage
            ? el.getAttribute("data-edit-img")
            : el.getAttribute("data-i18n-html") || el.getAttribute("data-i18n");

          window.parent.postMessage(
            {
              source: "kmssk-edit-bridge",
              type: "select",
              kind: isImage ? "image" : "text",
              key: key,
              lang: window.KMSSK_LANG || "sk",
              currentSrc: isImage ? el.getAttribute("src") : null
            },
            "*"
          );
        },
        true
      );
    });

    window.parent.postMessage({ source: "kmssk-edit-bridge", type: "ready" }, "*");
  }

  if (window.KMSSK_LANG) {
    markEditable();
  } else {
    window.addEventListener("kmssk:i18n-applied", markEditable, { once: true });
  }
})();
