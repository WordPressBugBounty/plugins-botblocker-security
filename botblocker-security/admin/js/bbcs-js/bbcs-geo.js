(function ($) {
    "use strict";

    var isProcessingGeo = false;
    var geoTable = null;
    var geoTableLoading = false;

    if (typeof window.BBCS_TabLoadingRegistry !== 'undefined') {
      window.BBCS_TabLoadingRegistry['GEO'] = function() { return geoTableLoading; };
    }

    var lastGeoUITab = '';
    var geoJustInitialized = false;

    var geoI18n = (window.botblockerGeoData && botblockerGeoData.i18n) || {};

    function geoText(key, fallback) {
        return geoI18n[key] || fallback || key;
    }

    function initializeGeoTable() {
        if (!$.fn.DataTable.isDataTable("#botblocker-geo")) {
            geoTable = $("#botblocker-geo").DataTable({
                processing: true,
                serverSide: true,
                scrollX: true,
                autoWidth: false,
                fixedHeader: true,
                responsive: true,
                colReorder: true,
                ajax: {
                    url: botblockerData.ajaxurl,
                    type: "POST",
                    data: function (d) {
                        d.action = "bbcs_get_countries_table";
                        d.nonce = botblockerData.nonce;
                    },
                    beforeSend: function() {
                        geoTableLoading = true;
                    },
                    complete: function() {
                        geoTableLoading = false;
                        setTimeout(function() {
                            if (geoTable) {
                                geoTable.columns.adjust();
                            }
                        }, 200);
                    }
                },
                columns: [
                    { data: "id", visible: false },
                    { data: "priority", width: "80px" },
                    { data: "code", width: "80px" },
                    { data: "name", width: "150px" },
                    { data: "rule", width: "80px" },
                    { data: "comment", width: "150px" },
                    {
                        data: null,
                        width: "100px",
                        render: function (data, type, row) {
                            return (
                                '<button class="btn btn-sm btn-default bbcs-actions-b delete-country" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="' + geoText('delete', 'Delete') + '" data-id="' +
                                row.id +
                                '"><i class="fa-regular fa-trash-can"></i></button> ' +
                                '<button class="btn btn-sm bbcs-actions-b ' +
                                (row.disable == 0 ? "btn-default" : "btn-warning") +
                                ' toggle-country" data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="' + geoText('toggle', 'Toggle On/Off') + '" data-id="' +
                                row.id +
                                '"><i class="fas ' +
                                (row.disable == 0 ? "fa-stop" : "fa-play") +
                                '"></i></button>'
                            );
                        },
                    },
                ],
                columnDefs: [
                    {
                        targets: "_all",
                        className: "text-wrap",
                    },
                ],
                createdRow: function (row, data) {
                    $(row).addClass(data.disable == 0 ? "bbcs-rule-row--active" : "bbcs-rule-row--disabled");
                },
                layout: (function () {
                    var isNewUI = !!document.querySelector('.bbcs-app');
                    return isNewUI ? {
                        topStart: {
                            search: {
                                text: '',
                                placeholder: geoText('search_placeholder', 'Search by code, rule, comment…')
                            }
                        },
                        topEnd: {
                            buttons: ['csv', 'excel']
                        }
                    } : {
                        topStart: {
                            buttons: [
                                "copy", "csv", "excel", "pdf", "print", "colvis",
                                {
                                    extend: "collection",
                                    text: "Length Menu",
                                    buttons: [
                                        { text: "10", action: function (e, dt) { dt.page.len(10).draw(); } },
                                        { text: "25", action: function (e, dt) { dt.page.len(25).draw(); } },
                                        { text: "50", action: function (e, dt) { dt.page.len(50).draw(); } },
                                        { text: "100", action: function (e, dt) { dt.page.len(100).draw(); } }
                                    ]
                                }
                            ]
                        }
                    };
                })(),
                drawCallback: function () {
                    var api = this.api();
                    api.columns().every(function () {
                        var column = this;
                        var header = $(column.header());
                        var body = $(column.nodes());
                        if (body.length > 0) {
                            header.css("min-width", body.first().css("width"));
                            header.css("max-width", body.first().css("width"));
                        }
                    });
                    setTimeout(function() {
                        api.columns.adjust();
                    }, 100);
                },
            });

            geoJustInitialized = true;

            $(document).on("click", "#botblocker-geo .toggle-country", function (e) {
                e.preventDefault();
                if (isProcessingGeo) return;

                var $button = $(this);
                var id = $button.data("id");

                isProcessingGeo = true;
                $button.prop("disabled", true);

                $.ajax({
                    url: botblockerData.ajaxurl,
                    type: "POST",
                    data: {
                        action: "bbcs_toggle_country",
                        id: id,
                        nonce: botblockerData.nonce,
                    },
                    success: function (response) {
                        if (response.success) {
                            var rowData = geoTable.row($button.closest("tr")).data();
                            rowData.disable = rowData.disable == 0 ? 1 : 0;
                            geoTable.row($button.closest("tr")).data(rowData).draw(false);
                            if (typeof window.bbcsRefreshTableOverview === 'function') { window.bbcsRefreshTableOverview(); }
                            window.bbcsRulesToast('success', geoText('success_toggle', 'Country rule toggled successfully.'));
                        } else {
                            window.bbcsRulesToast('error', response.data || geoText('failed_toggle', 'Failed to toggle country rule.'));
                        }
                    },
                    complete: function () {
                        isProcessingGeo = false;
                        $button.prop("disabled", false);
                    },
                });
            });

            $(document).on("click", "#botblocker-geo .delete-country", function (e) {
                e.preventDefault();
                if (isProcessingGeo) return;

                var $button = $(this);
                var id = $button.data("id");
                if (!confirm(geoText('confirm_delete', 'Are you sure you want to delete this country rule?'))) {
                    return;
                }

                isProcessingGeo = true;
                $button.prop("disabled", true);

                $.ajax({
                    url: botblockerData.ajaxurl,
                    type: "POST",
                    data: {
                        action: "bbcs_delete_country",
                        id: id,
                        nonce: botblockerData.nonce,
                    },
                    success: function (response) {
                        if (response.success) {
                            geoTable.ajax.reload();
                            if (typeof window.bbcsRefreshTableOverview === 'function') { window.bbcsRefreshTableOverview(); }
                            window.bbcsRulesToast('success', geoText('success_delete', 'Country rule deleted successfully.'));
                        } else {
                            window.bbcsRulesToast('error', response.data || geoText('failed_delete', 'Failed to delete country rule.'));
                        }
                    },
                    complete: function () {
                        isProcessingGeo = false;
                        $button.prop("disabled", false);
                    },
                });
            });

            $(document).on("click", "#bbcs_geo_add_country", function (e) {
                e.preventDefault();
                if (isProcessingGeo) return;

                var code = String($("#geoCountrySelect").val() || '').trim().toUpperCase();
                if (!code) {
                    window.bbcsRulesToast('error', geoText('please_select_country', 'Please select a country.'));
                    return;
                }

                isProcessingGeo = true;
                var $button = $(this);
                $button.prop("disabled", true);

                $.ajax({
                    url: botblockerData.ajaxurl,
                    type: "POST",
                    data: {
                        action: "bbcs_create_country",
                        code: code,
                        nonce: botblockerData.nonce,
                    },
                    success: function (response) {
                        if (response.success) {
                            geoTable.ajax.reload();
                            if (typeof window.bbcsRefreshTableOverview === 'function') { window.bbcsRefreshTableOverview(); }
                            window.bbcsRulesToast('success', geoText('success_create', 'Country added successfully.'));
                        } else {
                            window.bbcsRulesToast('error', response.data || geoText('failed_create', 'Failed to add country.'));
                        }
                    },
                    complete: function () {
                        isProcessingGeo = false;
                        $button.prop("disabled", false);
                    },
                });
            });

            $(document).on("click", "#bbcs_geo_clear_all", function (e) {
                e.preventDefault();
                if (isProcessingGeo) return;
                if (!confirm(geoText('confirm_clear', 'Are you sure you want to remove all country rules?'))) {
                    return;
                }

                isProcessingGeo = true;
                var $button = $(this);
                $button.prop("disabled", true);

                $.ajax({
                    url: botblockerData.ajaxurl,
                    type: "POST",
                    data: {
                        action: "bbcs_clear_all_countries",
                        nonce: botblockerData.nonce,
                    },
                    success: function (response) {
                        if (response.success) {
                            geoTable.ajax.reload();
                            if (typeof window.bbcsRefreshTableOverview === 'function') { window.bbcsRefreshTableOverview(); }
                            window.bbcsRulesToast('success', geoText('success_clear', 'All country rules have been cleared.'));
                        } else {
                            window.bbcsRulesToast('error', response.data || geoText('failed_clear', 'Failed to clear countries.'));
                        }
                    },
                    complete: function () {
                        isProcessingGeo = false;
                        $button.prop("disabled", false);
                    },
                });
            });
        }
        return geoTable;
    }

    $(document).ready(function () {
        $(document).on('bbcs:tab-changed', function (e, data) {
            if (data.tab === 'GEO') {
                var sameTab = (lastGeoUITab === data.tab);
                lastGeoUITab = data.tab;
                initializeGeoTable();
                if (!sameTab && !geoJustInitialized && geoTable) {
                    geoTable.ajax.reload();
                }
                geoJustInitialized = false;
            }
        });
    });
})(jQuery);
