(function ($) {
  "use strict";

  var auditTable = null;
  var auditInitialized = false;
  var auditLoading = false;

  // One icon per event category, so a new event key inherits its category icon
  // instead of needing its own mapping.
  var CATEGORY_ICONS = {
    auth: "fa-solid fa-right-to-bracket",
    user: "fa-solid fa-user",
    content: "fa-solid fa-file-lines",
    media: "fa-solid fa-image",
    comment: "fa-solid fa-comment",
    taxonomy: "fa-solid fa-tags",
    plugin: "fa-solid fa-plug",
    theme: "fa-solid fa-palette",
    core: "fa-solid fa-wordpress",
    settings: "fa-solid fa-gear",
    botblocker: "fa-solid fa-shield-halved"
  };
  var DEFAULT_ICON = "fa-solid fa-circle-info";

  function esc(value) {
    return $("<div>").text(value === null || value === undefined ? "" : String(value)).html();
  }

  function iconFor(category) {
    return CATEGORY_ICONS[category] || DEFAULT_ICON;
  }

  // Mirrors the server thresholds: 500+ critical, 300+ medium, else info.
  function severityPill(row) {
    var cls = "bbcs-pill--green";
    if (row.severity >= 500) {
      cls = "bbcs-pill--red";
    } else if (row.severity >= 300) {
      cls = "bbcs-pill--amber";
    }
    return '<span class="bbcs-pill bbcs-pill--dot ' + cls + '">' + esc(row.severity_label) + "</span>";
  }

  function renderEventCell(row) {
    return (
      '<div class="bbcs-audit-event">' +
      '<i class="' + iconFor(row.category) + ' bbcs-audit-event-icon" aria-hidden="true"></i>' +
      '<div class="bbcs-audit-event-text">' +
      '<span class="bbcs-audit-event-msg">' + esc(row.message) + "</span>" +
      '<span class="bbcs-audit-event-key">' + esc(row.event_key) + "</span>" +
      "</div></div>"
    );
  }

  function renderActorCell(row) {
    var actor = row.actor || "—";
    var out = '<div class="bbcs-audit-event-text"><span>' + esc(actor) + "</span>";
    if (row.role) {
      out += '<span class="bbcs-audit-event-key">' + esc(row.role) + "</span>";
    }
    return out + "</div>";
  }

  function detailRow(label, value, mono) {
    if (value === null || value === undefined || value === "") {
      return "";
    }
    return (
      "<dt>" + esc(label) + "</dt>" +
      '<dd' + (mono ? ' class="bbcs-audit-mono"' : "") + ">" + esc(value) + "</dd>"
    );
  }

  function openDetailModal(row) {
    var l10n = window.bbcsAuditL10n || {};
    var overlay = document.createElement("div");
    overlay.className = "bbcs-modal-overlay";

    var modal = document.createElement("div");
    modal.className = "bbcs-modal bbcs-modal--wide";

    var body =
      '<dl class="bbcs-audit-details">' +
      detailRow(l10n.time || "Time", row.time) +
      detailRow(l10n.event || "Event", row.event_key, true) +
      detailRow(l10n.message || "Message", row.message) +
      detailRow(l10n.severity || "Severity", row.severity_label) +
      detailRow(l10n.actor || "Actor", row.actor) +
      detailRow(l10n.role || "Role", row.role) +
      detailRow(l10n.objectType || "Object type", row.object_type) +
      detailRow(l10n.objectId || "Object ID", row.object_id) +
      detailRow(l10n.ip || "IP", row.ip, true) +
      detailRow(l10n.context || "Context", row.context) +
      detailRow(l10n.path || "Path", row.path, true) +
      detailRow(l10n.userAgent || "User agent", row.user_agent, true) +
      "</dl>";

    if (row.data) {
      body +=
        '<h4 class="bbcs-audit-data-title">' + esc(l10n.data || "Data") + "</h4>" +
        '<pre class="bbcs-audit-data">' + esc(row.data) + "</pre>";
    }

    modal.innerHTML =
      '<div class="bbcs-modal-header">' +
      '<h3 class="bbcs-modal-title">' +
      '<i class="' + iconFor(row.category) + '" aria-hidden="true"></i> ' +
      esc(row.message) +
      "</h3>" +
      '<button type="button" class="bbcs-modal-close" aria-label="' + esc(l10n.close || "Close") + '">&times;</button>' +
      "</div>" +
      '<div class="bbcs-modal-body">' + body + "</div>" +
      '<div class="bbcs-modal-footer">' +
      '<button type="button" class="bbcs-btn bbcs-btn--sec bbcs-audit-close">' + esc(l10n.close || "Close") + "</button>" +
      "</div>";

    overlay.appendChild(modal);
    document.body.appendChild(overlay);

    function cleanup() {
      if (overlay.parentNode) {
        overlay.parentNode.removeChild(overlay);
      }
      document.removeEventListener("keydown", onKey);
    }

    function onKey(e) {
      if (e.key === "Escape") {
        cleanup();
      }
    }

    overlay.addEventListener("click", function (e) {
      if (e.target === overlay) {
        cleanup();
      }
    });
    $(modal).on("click", ".bbcs-modal-close, .bbcs-audit-close", cleanup);
    document.addEventListener("keydown", onKey);

    var closeBtn = modal.querySelector(".bbcs-modal-close");
    if (closeBtn) {
      closeBtn.focus();
    }
  }

  function getFilters() {
    return {
      category: $("#bbcs-audit-category").val() || "",
      severity: $("#bbcs-audit-severity").val() || "",
      context: $("#bbcs-audit-context").val() || "",
    };
  }

  function initAuditTable() {
    if (auditInitialized || !$("#botblocker-audit-log").length) {
      return;
    }
    auditInitialized = true;

    auditTable = $("#botblocker-audit-log").DataTable({
      processing: true,
      // Server-side: an audit log has to be searchable in full. Client-side paging
      // would only ever see the first batch, so "no results" would be a lie, and the
      // CSV export (which always filters server-side) would disagree with the table.
      serverSide: true,
      autoWidth: false,
      ajax: {
        url: botblockerData.ajaxurl,
        type: "POST",
        data: function (d) {
          d.action = "bbcs_get_audit_log";
          d.nonce = botblockerData.nonce;
          var filters = getFilters();
          d.category = filters.category;
          d.severity = filters.severity;
          d.context = filters.context;
          auditLoading = true;
        },
        complete: function () {
          auditLoading = false;
        },
      },
      columns: [
        { data: "time", className: "bbcs-audit-col-time" },
        {
          data: "severity",
          className: "bbcs-audit-col-sev",
          render: function (severity, type, row) {
            // Sort and filter on the raw number, show the pill.
            return type === "display" ? severityPill(row) : severity;
          }
        },
        {
          data: "message",
          render: function (message, type, row) {
            return type === "display" ? renderEventCell(row) : message + " " + row.event_key;
          }
        },
        {
          data: "actor",
          className: "bbcs-audit-col-actor",
          render: function (actor, type, row) {
            return type === "display" ? renderActorCell(row) : actor + " " + row.role;
          }
        },
        { data: "ip", className: "bbcs-audit-mono bbcs-audit-col-ip", render: function (v, type) { return type === "display" ? esc(v) : v; } },
        {
          data: null,
          orderable: false,
          searchable: false,
          className: "bbcs-audit-col-actions",
          render: function () {
            var label = (window.bbcsAuditL10n && window.bbcsAuditL10n.details) || "Details";
            return (
              '<button type="button" class="bbcs-btn bbcs-btn--sec bbcs-audit-details-btn">' +
              '<i class="fa-solid fa-circle-info" aria-hidden="true"></i> ' + esc(label) +
              "</button>"
            );
          }
        }
      ],
      order: [[0, "desc"]],
      searchDelay: 400,
      lengthChange: false,
      pageLength: 25,
      layout: {
        topStart: {
          search: {
            text: "",
            placeholder: (window.bbcsAuditL10n && window.bbcsAuditL10n.searchPlaceholder) || "Search audit log…"
          }
        },
        topEnd: [
          // Hand the markup-defined filter bar to DataTables so it shares the
          // search row instead of sitting in a stray strip above the table.
          function () {
            return document.getElementById("bbcs-audit-filters");
          },
          // Hidden: the page-head Copy and Excel buttons trigger these, the same way
          // the other report tabs do it in bbcs-hits.js.
          {
            buttons: [
              { extend: "copy", className: "d-none" },
              { extend: "excel", className: "d-none" }
            ]
          }
        ]
      }
    });

    $("#botblocker-audit-log").on("click", ".bbcs-audit-details-btn", function () {
      var row = auditTable.row($(this).closest("tr")).data();
      if (row) {
        openDetailModal(row);
      }
    });

    $("#bbcs-audit-category, #bbcs-audit-severity, #bbcs-audit-context").on("change", function () {
      if (auditTable) {
        // Reset to page one: the current offset is meaningless under a new filter.
        auditTable.ajax.reload(null, true);
      }
    });

  }

  // Called by the shared page-head CSV button in bbcs-hits.js.
  function submitExport(format) {
    // Post into a hidden iframe, not target="_blank": the response is an attachment,
    // so a real tab would pop open, download, and leave a blank tab behind.
    var frameName = "bbcs-audit-export-" + Date.now();
    var frame = $("<iframe>", { name: frameName, "aria-hidden": "true" }).css("display", "none").appendTo("body");
    var form = $("<form>", { method: "POST", action: botblockerData.ajaxurl, target: frameName });
    var filters = getFilters();
    form.append($("<input>", { type: "hidden", name: "action", value: "bbcs_export_audit_log" }));
    form.append($("<input>", { type: "hidden", name: "nonce", value: botblockerData.nonce }));
    form.append($("<input>", { type: "hidden", name: "format", value: format }));
    form.append($("<input>", { type: "hidden", name: "search", value: auditTable ? auditTable.search() : "" }));
    form.append($("<input>", { type: "hidden", name: "category", value: filters.category }));
    form.append($("<input>", { type: "hidden", name: "severity", value: filters.severity }));
    form.append($("<input>", { type: "hidden", name: "context", value: filters.context }));
    $("body").append(form);
    form.trigger("submit");
    form.remove();
    // Keep the iframe alive long enough for the browser to take the download.
    setTimeout(function () { frame.remove(); }, 60000);
  }

  window.bbcsAuditExportCsv = function () { submitExport("csv"); };

  $(document).on("bbcs:tab-changed", function (_e, data) {
    if (data && data.tab === "Audit Log") {
      initAuditTable();
      if (auditTable) {
        auditTable.columns.adjust();
      }
    }
  });

  // Same race as the page-head buttons: on reload the tab may already be active before
  // this file binds, so the event never arrives and the table would stay empty.
  $(function () {
    if ($(".bbcs-tab.is-active").first().data("tab") === "Audit Log") {
      initAuditTable();
    }
  });

  if (typeof window.BBCS_TabLoadingRegistry !== "undefined") {
    window.BBCS_TabLoadingRegistry["Audit Log"] = function () {
      return auditLoading;
    };
  }
})(jQuery);
