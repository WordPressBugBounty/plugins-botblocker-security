(function ($) {
    "use strict";

    var LEVELS = ["ok", "warn", "critical"];
    var pending = null;

    function applyLevel(level) {
        var textClass = "";
        if (level === "critical") {
            textClass = "bbcs-tx-red";
        } else if (level === "warn") {
            textClass = "bbcs-tx-amber";
        }
        $(".bbcs-stat[data-bbcs-remaining]").removeClass("bbcs-tx-red bbcs-tx-amber").addClass(textClass);

        var $cta = $(".bbcs-pro-cta--active");
        $.each(LEVELS, function (i, name) {
            $cta.removeClass("bbcs-pro-cta--" + name);
        });
        $cta.addClass("bbcs-pro-cta--" + level);
    }

    function setLoading(on) {
        var $nums = $("[data-bbcs-remaining]");
        if (on) {
            $nums.addClass("is-loading").attr("aria-busy", "true");
        } else {
            $("[data-bbcs-remaining].is-empty").text("–");
            $nums.removeClass("is-loading is-empty").removeAttr("aria-busy");
        }
    }

    // An empty word means the license expired: hide it so "Expired" stands alone.
    function setDaysWord(selector, text) {
        var fieldMissing = typeof text === "undefined" || text === null;
        if (fieldMissing) {
            return;
        }
        $(selector).text(text).prop("hidden", text === "");
    }

    function fill(data) {
        $("[data-bbcs-remaining]").each(function () {
            var kind = $(this).data("bbcs-remaining");
            var val = data["remaining_" + kind];
            if (typeof val === "undefined" || val === null) {
                return;
            }
            var text = String(val);
            if (kind === "hits" && data.remaining_hits_text) {
                text = data.remaining_hits_text;
            }
            if (kind === "days" && data.remaining_days_text) {
                text = data.remaining_days_text;
            }
            $(this).text(text).removeClass("is-empty");
        });
        setDaysWord("[data-bbcs-remaining-unit='days']", data.remaining_days_unit);
        setDaysWord("[data-bbcs-remaining-label='days']", data.remaining_days_label);
        if ($.inArray(data.alert_level, LEVELS) !== -1) {
            applyLevel(data.alert_level);
        }
    }

    function refresh() {
        if (pending) {
            return pending;
        }
        setLoading(true);
        pending = $.ajax({
            url: botblockerData.ajaxurl,
            type: "POST",
            data: {
                action: "bbcs_refresh_cloud_api",
                nonce: botblockerData.nonce,
            },
        })
            .done(function (response) {
                if (response && response.data) {
                    fill(response.data);
                }
            })
            .always(function () {
                setLoading(false);
                pending = null;
            });
        return pending;
    }

    window.bbcsProRemaining = { refresh: refresh };

    $(function () {
        if ($('[data-bbcs-pro-fetch="true"]').length) {
            refresh();
        }
    });
})(jQuery);
