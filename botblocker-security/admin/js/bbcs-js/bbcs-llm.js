(function ($) {
    "use strict";

    var llmTableLoading = false;

    // Register loading state for new UI tab switching guard.
    if (typeof window.BBCS_TabLoadingRegistry !== 'undefined') {
      window.BBCS_TabLoadingRegistry['LLM'] = function() { return llmTableLoading; };
    }

    var lastLlmUITab = '';
    var llmJustInitialized = false;

    var switchDebounceMs = 200;
    var _lastSwitchTs = 0;

    $(document).on('show.bs.tab', 'a[data-bs-toggle="tab"]', function (e) {
        var now = Date.now();
        if (now - _lastSwitchTs < switchDebounceMs) {
            e.preventDefault();
            return;
        }

        var loading = false;
        if (typeof tables !== 'undefined') {
            loading = Object.keys(tables).some(function (k) { return !!tables[k].isLoading; });
        }
        loading = loading || llmTableLoading;
        if (loading) {
            e.preventDefault();
            var activeTab = $('a[data-bs-toggle="tab"].active');
            activeTab && activeTab.addClass('bbcs-tab-wait');
            setTimeout(function () { activeTab && activeTab.removeClass('bbcs-tab-wait'); }, 400);
            return;
        }
        _lastSwitchTs = now;
    });

    function syncFromCloud($btn) {
        if (!$btn.data('bbcs-original-html')) {
            $btn.data('bbcs-original-html', $btn.html());
        }
        $btn.prop('disabled', true).text('\u23F3');
        $.ajax({
            url:  botblockerData.ajaxurl,
            type: 'POST',
            data: { action: 'bbcs_sync_llm_cloud', nonce: botblockerData.nonce },
            success: function (response) {
                $btn.prop('disabled', false).html($btn.data('bbcs-original-html'));
                if (response.success) {
                    alert(response.data.message || bbcsLLML10n.sync_scheduled);
                    if ($.fn.DataTable.isDataTable('#botblocker-llm')) {
                        $('#botblocker-llm').DataTable().ajax.reload(null, false);
                    if (typeof window.bbcsRefreshTableOverview === 'function') { window.bbcsRefreshTableOverview(); }
                    }
                } else {
                    alert(bbcsLLML10n.sync_failed + (response.data || ''));
                }
            },
            error: function () {
                $btn.prop('disabled', false).html($btn.data('bbcs-original-html'));
                alert(bbcsLLML10n.sync_failed);
            }
        });
    }

    function downloadJson($btn) {
        if (!$btn.data('bbcs-original-html')) {
            $btn.data('bbcs-original-html', $btn.html());
        }
        $btn.prop('disabled', true).text('\u23F3');
        $.ajax({
            url:  botblockerData.ajaxurl,
            type: 'POST',
            data: { action: 'bbcs_export_llm_json', nonce: botblockerData.nonce },
            success: function (response) {
                $btn.prop('disabled', false).html($btn.data('bbcs-original-html'));
                if (response.success && response.data) {
                    var json = JSON.stringify(response.data, null, 2);
                    var blob = new Blob([json], { type: 'application/json' });
                    var url  = URL.createObjectURL(blob);
                    var a    = document.createElement('a');
                    a.href     = url;
                    a.download = 'llm-providers.json';
                    document.body.appendChild(a);
                    a.click();
                    document.body.removeChild(a);
                    URL.revokeObjectURL(url);
                } else {
                    alert(bbcsLLML10n.failed_update);
                }
            },
            error: function () {
                $btn.prop('disabled', false).html($btn.data('bbcs-original-html'));
                alert(bbcsLLML10n.failed_update);
            }
        });
    }

    function saveToPhp() {
        $.ajax({
            url:  botblockerData.ajaxurl,
            type: 'POST',
            data: { action: 'bbcs_llm_to_php', nonce: botblockerData.nonce },
            success: function (response) {
                alert(response.data);
            }
        });
    }

    function initializeLLMTable() {
        if ($.fn.DataTable.isDataTable("#botblocker-llm")) {
            return;
        }

        $("#botblocker-llm").DataTable({
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
                    d.action = "bbcs_get_botblocker_llm";
                    d.nonce  = botblockerData.nonce;
                },
                beforeSend: function () { llmTableLoading = true; },
                complete:   function () { llmTableLoading = false; }
            },
            columns: [
                {
                    data: "provider_label",
                    width: "150px",
                    render: function (data, type, row) {
                        if (type !== "display") return data;
                        return '<strong>' + $('<span>').text(data || row.provider).html() + '</strong>';
                    }
                },
                { data: "range_count", width: "80px" },
                {
                    data: "ua_tokens",
                    width: "200px",
                    render: function (data) {
                        return '<span style="font-size:10px; word-break:break-all;">' + $('<span>').text(data || '').html() + '</span>';
                    }
                },
                {
                    data: null,
                    width: "120px",
                    orderable: false,
                    render: function (data, type, row) {
                        var icon  = row.disabled == 1 ? 'fa-play' : 'fa-stop';
                        var cls   = row.disabled == 1 ? 'btn-warning' : 'btn-default';
                        var title = row.disabled == 1 ? bbcsLLML10n.enable : bbcsLLML10n.disable;
                        return '<button class="btn btn-sm bbcs-actions-b ' + cls + ' toggle-llm-provider" '
                            + 'data-bs-toggle="tooltip" data-bs-placement="top" data-bs-original-title="' + title + '" '
                            + 'data-provider="' + $('<span>').text(row.provider).html() + '" '
                            + 'data-disabled="' + row.disabled + '">'
                            + '<i class="fas ' + icon + '"></i></button>';
                    }
                }
            ],
            order: [[0, "asc"]],
            createdRow: function (row, data) {
                $(row).addClass(data.disabled == 1 ? "bbcs-rule-row--disabled" : "bbcs-rule-row--active");
            },
            layout: (function () {
                var isNewUI = !!document.querySelector('.bbcs-app');
                return isNewUI ? {
                    topStart: {
                        search: {
                            text: '',
                            placeholder: bbcsLLML10n.search_placeholder
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
            drawCallback: function (settings) {
                var api = this.api();
                api.columns().every(function () {
                    var column = this;
                    var header = $(column.header());
                    var body   = $(column.nodes());
                    if (body.length > 0) {
                        header.css("min-width", body.first().css("width"));
                        header.css("max-width", body.first().css("width"));
                    }
                });
                api.columns.adjust();
            }
        });

        llmJustInitialized = true;
    }

    $(document).ready(function () {

        $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
            var target = $(e.target).attr('href');
            if (target === '#bbcs_llm_list') {
                initializeLLMTable();
            }
        });

        $(document).on('bbcs:tab-changed', function (e, data) {
            if (data.tab === 'LLM') {
                var sameTab = (lastLlmUITab === data.tab);
                lastLlmUITab = data.tab;
                initializeLLMTable();
                if ($.fn.DataTable.isDataTable('#botblocker-llm')) {
                    var dt = $('#botblocker-llm').DataTable();
                    dt.columns.adjust();
                    if (!sameTab && !llmJustInitialized) {
                        dt.draw(false);
                    }
                    llmJustInitialized = false;
                }
            }
        });

        if ($('#bbcs_llm_list').hasClass('active')) {
            initializeLLMTable();
        }

        $('#bbcs_llm_sync_cloud').on('click', function (e) {
            e.preventDefault();
            syncFromCloud($(this));
        });

        $('#bbcs_llm_download_json').on('click', function (e) {
            e.preventDefault();
            downloadJson($(this));
        });

        $('#bbcs_pagehead_llm_sync').on('click', function (e) {
            e.preventDefault();
            syncFromCloud($(this));
        });

        $('#bbcs_pagehead_llm_download').on('click', function (e) {
            e.preventDefault();
            downloadJson($(this));
        });

        $('#bbcs_llm_to_php').on('click', function (e) {
            e.preventDefault();
            saveToPhp();
        });

        $(document).on('click', '.toggle-llm-provider', function () {
            var $btn     = $(this);
            var provider = $btn.data('provider');
            $btn.prop('disabled', true);

            $.ajax({
                url:  botblockerData.ajaxurl,
                type: 'POST',
                data: {
                    action:   'bbcs_toggle_llm_provider',
                    provider: provider,
                    nonce:    botblockerData.nonce,
                },
                success: function (response) {
                    $btn.prop('disabled', false);
                    if (response.success) {
                        $('#botblocker-llm').DataTable().ajax.reload(null, false);
                    if (typeof window.bbcsRefreshTableOverview === 'function') { window.bbcsRefreshTableOverview(); }
                    } else {
                        alert(response.data || bbcsLLML10n.failed_update);
                    }
                },
                error: function () {
                    $btn.prop('disabled', false);
                    alert(bbcsLLML10n.failed_update);
                }
            });
        });

    });

})(jQuery);
