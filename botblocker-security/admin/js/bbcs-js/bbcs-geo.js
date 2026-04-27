(function ($) {
    "use strict";

    var selectedCountries = [];
    var geoTabInitialized = false;

    function normalizeCountryCode(code) {
        return String(code || '').trim().toUpperCase();
    }

    var geoI18n = (window.botblockerGeoData && botblockerGeoData.i18n) || {};

    function geoText(key, fallback) {
        return geoI18n[key] || fallback || key;
    }

    function updateGeoTextarea() {
        $("#geoCountryCodes").val(selectedCountries.join(','));
        updateGeoCount();
    }

    function updateGeoCount() {
        $("#bbcs_geo_count").text(selectedCountries.length);
    }

    function renderGeoTags() {
        var container = $("#geoTags");
        container.empty();

        if (selectedCountries.length === 0) {
            container.append('<span class="text-muted">' + geoText('no_countries_selected', 'No countries selected') + '</span>');
            return;
        }

        selectedCountries.forEach(function (code) {
            var label = $("#geoCountrySelect option[value='" + code + "']").text() || code;
            var tag = $('<span></span>', {
                class: 'bbcs-geo-tag badge rounded-pill d-inline-flex align-items-center',
                'data-code': code,
                text: label + ' (' + code + ')',
                style: 'background: none; color: #000; border: 1px solid #000; padding: 0.35rem 0.75rem;'
            });
            var remove = $('<button type="button" class="bbcs-geo-tag-remove ms-2" aria-label="' + geoText('remove_country', 'Remove country') + '">\u00d7</button>');
            remove.css({
                background: 'none',
                border: 'none',
                color: '#000',
                padding: '0',
                marginLeft: '7px',
                fontSize: '0.9rem',
                lineHeight: '1',
                cursor: 'pointer'
            });
            remove.on('click', function () {
                removeCountry(code);
            });
            tag.append(remove);
            container.append(tag);
        });
    }

    function showGeoStatus(message, isError) {
        var container = $("#bbcs_geo_alert");
        if (!container.length) {
            container = $("<div id=\"bbcs_geo_alert\" class=\"alert mt-2\" role=\"alert\"></div>");
            $("#bbcs_geo_list .bbcs_control_panel").after(container);
        }
        container.removeClass('alert-success alert-danger').addClass(isError ? 'alert-danger' : 'alert-success').text(message).show();
        setTimeout(function () {
            container.fadeOut(300);
        }, 4000);
    }

    function addCountry(code) {
        code = normalizeCountryCode(code);
        if (!code) {
            showGeoStatus(geoText('please_select_country', 'Please select a country.'), true);
            return;
        }
        if (selectedCountries.includes(code)) {
            showGeoStatus(geoText('country_already_added', 'Country already added.'), true);
            return;
        }
        if (!$("#geoCountrySelect option[value='" + code + "']").length) {
            showGeoStatus(geoText('invalid_country', 'Invalid country.'), true);
            return;
        }
        selectedCountries.push(code);
        selectedCountries = Array.from(new Set(selectedCountries));
        renderGeoTags();
        updateGeoTextarea();
    }

    function removeCountry(code) {
        code = normalizeCountryCode(code);
        selectedCountries = selectedCountries.filter(function (item) {
            return item !== code;
        });
        renderGeoTags();
        updateGeoTextarea();
    }

    function clearGeoCountries() {
        selectedCountries = [];
        renderGeoTags();
        updateGeoTextarea();
    }

    function loadGeoCountriesFromTextarea() {
        var raw = $("#geoCountryCodes").val() || '';
        selectedCountries = raw.split(',').map(normalizeCountryCode).filter(function (code) {
            return code.length === 2;
        });
        selectedCountries = Array.from(new Set(selectedCountries));
        renderGeoTags();
        updateGeoTextarea();
    }

    function setButtonLoading($btn, label) {
        if (!$btn.length) {
            return;
        }
        if (!$btn.data('bbcs-original-html')) {
            $btn.data('bbcs-original-html', $btn.html());
        }
        var text = label || $.trim($btn.text()) || $btn.data('bbcs-original-html');
        $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-2"></i>' + text);
    }

    function resetButton($btn) {
        if (!$btn.length) {
            return;
        }
        var originalHtml = $btn.data('bbcs-original-html');
        if (originalHtml) {
            $btn.html(originalHtml);
        }
        $btn.prop('disabled', false);
    }

    function showGeoSectionOverlay() {
        var $section = $('#bbcs_geo_list');
        if (!$section.length || $section.find('.bbcs-loading-overlay--geo').length) {
            return;
        }
        if ($section.css('position') === 'static') {
            $section.css('position', 'relative');
        }
        $section.append(
            '<div class="bbcs-loading-overlay bbcs-loading-overlay--geo" style="position:absolute;top:0;left:0;right:0;bottom:0;background:rgba(255,255,255,0.85);display:flex;align-items:center;justify-content:center;z-index:9999;pointer-events:auto;">' +
            '<i class="fa-solid fa-spinner fa-spin fa-2x" style="color:#0d6efd;"></i>' +
            '</div>'
        );
    }

    function hideGeoSectionOverlay() {
        var $overlay = $('#bbcs_geo_list').find('.bbcs-loading-overlay--geo');
        if (!$overlay.length) {
            return;
        }
        $overlay.fadeOut(200, function () {
            $(this).remove();
        });
    }

    function loadGeoCountriesFromServer() {
        var $refreshBtn = $('#bbcs_geo_refresh');
        showGeoSectionOverlay();
        setButtonLoading($refreshBtn, geoText('reloading', 'Reloading...'));

        $.ajax({
            url: botblockerData.ajaxurl,
            type: 'POST',
            data: {
                action: 'bbcs_get_geo_countries',
                nonce: botblockerData.nonce
            },
            success: function (response) {
                if (response.success && Array.isArray(response.data)) {
                    selectedCountries = response.data.map(normalizeCountryCode).filter(function (code) {
                        return code.length === 2;
                    });
                    selectedCountries = Array.from(new Set(selectedCountries));
                    renderGeoTags();
                    updateGeoTextarea();
                    showGeoStatus(geoText('country_list_loaded', 'Country list loaded.'), false);
                } else {
                    showGeoStatus(geoText('failed_to_load_saved_countries', 'Failed to load saved countries.'), true);
                }
            },
            error: function () {
                showGeoStatus(geoText('server_error_loading_saved_countries', 'Server error while loading saved countries.'), true);
            },
            complete: function () {
                resetButton($refreshBtn);
                hideGeoSectionOverlay();
            }
        });
    }

    function saveGeoCountries() {
        var $saveBtn = $('#bbcs_geo_save');
        showGeoSectionOverlay();
        setButtonLoading($saveBtn, geoText('saving', 'Saving...'));

        $.ajax({
            url: botblockerData.ajaxurl,
            type: 'POST',
            data: {
                action: 'bbcs_save_geo_countries',
                countries: JSON.stringify(selectedCountries),
                nonce: botblockerData.nonce
            },
            success: function (response) {
                if (response.success && Array.isArray(response.data)) {
                    selectedCountries = response.data.map(normalizeCountryCode).filter(function (code) {
                        return code.length === 2;
                    });
                    selectedCountries = Array.from(new Set(selectedCountries));
                    renderGeoTags();
                    updateGeoTextarea();
                    showGeoStatus(geoText('country_list_saved', 'Country list saved.'), false);
                } else {
                    showGeoStatus(response.data || geoText('failed_to_save_country_list', 'Failed to save country list.'), true);
                }
            },
            error: function () {
                showGeoStatus(geoText('server_error_saving_country_list', 'Server error while saving country list.'), true);
            },
            complete: function () {
                resetButton($saveBtn);
                hideGeoSectionOverlay();
            }
        });
    }

    function initializeGeoTab() {
        if (geoTabInitialized) {
            return;
        }
        geoTabInitialized = true;
        loadGeoCountriesFromTextarea();
    }

    $(document).ready(function () {
        $('a[data-bs-toggle="tab"]').on('shown.bs.tab', function (e) {
            var target = $(e.target).attr('href');
            if (target === '#bbcs_geo_list') {
                initializeGeoTab();
                loadGeoCountriesFromServer();
            }
        });

        if ($('#bbcs_geo_list').hasClass('active')) {
            initializeGeoTab();
            loadGeoCountriesFromServer();
        }

        $('#bbcs_geo_list').on('click', '#bbcs_geo_add_country', function () {
            addCountry($('#geoCountrySelect').val());
        });

        $('#bbcs_geo_list').on('click', '#bbcs_geo_save', function () {
            saveGeoCountries();
        });

        $('#bbcs_geo_list').on('click', '#bbcs_geo_refresh', function () {
            loadGeoCountriesFromServer();
        });

        $('#bbcs_geo_list').on('click', '#bbcs_geo_clear_all', function () {
            clearGeoCountries();
        });

        $('#bbcs_geo_list').on('click', '.bbcs-geo-tag .bbcs-geo-tag-remove', function () {
            removeCountry($(this).closest('.bbcs-geo-tag').data('code'));
        });
    });
})(jQuery);
