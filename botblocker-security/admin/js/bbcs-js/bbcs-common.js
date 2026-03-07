(function ($) {
    "use strict";
    function bbcsBlinkTab(el, times = 3, color = 'cornflowerblue', intervalMs = 200) {
        if (!el) return;
        const originalInline = el.style.backgroundColor;
        let phaseOn = false;
        let count = 0;
        const timer = setInterval(() => {
            el.style.backgroundColor = phaseOn ? (originalInline || '') : color;
            phaseOn = !phaseOn;
            if (phaseOn) {
                count++;
                if (count >= times) {
                    clearInterval(timer);
                    el.style.backgroundColor = originalInline;
                }
            }
        }, intervalMs);
    }

    document.addEventListener("DOMContentLoaded", function () {
        const hash = window.location.hash;
        if (hash) {
            const tabLink = document.querySelector(`a.nav-link[href="${hash}"]`);
            if (tabLink) {
                const tab = new bootstrap.Tab(tabLink);
                tab.show();
                bbcsBlinkTab(tabLink);
            }
        }
        const tabLinks = document.querySelectorAll('.nav-link[data-bs-toggle="tab"]');
        tabLinks.forEach(link => {
            link.addEventListener('shown.bs.tab', function (e) {
                history.pushState(null, '', e.target.getAttribute('href'));
            });
        });

        window.addEventListener('hashchange', function () {
            const newHash = window.location.hash;
            if (!newHash) return;
            const targetLink = document.querySelector(`a.nav-link[href="${newHash}"]`);
            if (targetLink) {
                const tab = new bootstrap.Tab(targetLink);
                tab.show();
                bbcsBlinkTab(targetLink);
            }
        });
    });

    function setLanguage(lang) {
        document.cookie = "bbcs_preferred_language=" + lang + "; path=/";
        location.reload();
    }

    function getLanguageFromCookie() {
        const value = `; ${document.cookie}`;
        const parts = value.split(`; bbcs_preferred_language=`);
        if (parts.length === 2) return parts.pop().split(";").shift();
    }

    function loadTranslation(lang) {
        // console.log("Loading translations for language:", lang);
        // Add code here to load translations
    }

    function initializeLanguageOptions() {
        const languageOptions = document.querySelectorAll(".language-option");
        const currentLang = getLanguageFromCookie() || botblockerCurrentLocale.locale || 'en_US';

        languageOptions.forEach((option) => {

            const optionLang = option.getAttribute("data-lang");

            if (optionLang === currentLang) {
                option.classList.add('active');
            }

            option.addEventListener("click", function (event) {
                event.preventDefault();
                const lang = this.getAttribute("data-lang");
                if (lang !== currentLang) {

                    languageOptions.forEach(opt => opt.classList.remove('active'));

                    this.classList.add('active');

                    setLanguage(lang);
                }
            });
        });

        if (currentLang) {
            loadTranslation(currentLang);
        }
    }

    function initializeTooltips() {
        var tooltipTriggerList = [].slice.call(
            document.querySelectorAll('[data-bs-toggle="tooltip"]')
        );
        tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    }

    $(document).ready(function () {
        initializeLanguageOptions();
        initializeTooltips();
    });

    jQuery(document).ready(function ($) {

        function setRedisState(redisState) {
            $("#bbcs_switch_redis, #bbcs_integrations_switch_redis").prop("checked", redisState);
        }

        function setMemcachedState(memcachedState) {
            $("#bbcs_switch_memcached, #bbcs_integrations_switch_memcached").prop(
                "checked",
                memcachedState
            );
        }

        function updateCacheSettings(redisEnabled, memcachedEnabled) {
            $.ajax({
                url: botblockerData.ajaxurl,
                type: "POST",
                data: {
                    action: "bbcs_toggle_redis_and_memcached",
                    nonce: botblockerData.nonce,
                    redis_enable: redisEnabled ? 1 : 0,
                    memcached_enable: memcachedEnabled ? 1 : 0,
                },
                success: function (response) {
                    if (response.success) {
                      //  console.log("Cache settings updated successfully.");
                    } else {
                        console.error("Failed to update cache settings: " + response.data);
                    }
                },
                error: function (xhr, status, error) {
                    alert("AJAX Error: " + error);
                },
            });
        }

        function onCacheCheckboxChanged() {
            const isRedisCheckbox = $(this).is(
                "#bbcs_switch_redis, #bbcs_integrations_switch_redis"
            );
            const isChecked = $(this).is(":checked");

            if (isRedisCheckbox) {
                setRedisState(isChecked);
                setMemcachedState(false);
                updateCacheSettings(isChecked, false);
            } else {
                setRedisState(false);
                setMemcachedState(isChecked);
                updateCacheSettings(false, isChecked);
            }
        }

        function onApcuChanged() {
            const apcuEnabled = $(this).is(":checked");
            $.ajax({
                url: botblockerData.ajaxurl,
                type: "POST",
                data: {
                    action: "bbcs_switch_ptr_cache_in_db",
                    nonce: botblockerData.nonce,
                    ptr_cache_in_db: apcuEnabled ? 1 : 0,
                },
                success: function (response) {
                    if (response.success) {
                     //   console.log("APCu setting updated.");
                    } else {
                        console.error("APCu update failed: " + response.data);
                    }
                },
                error: function (xhr, status, error) {
                    alert("AJAX Error: " + error);
                },
            });
        }

        function onMUPluginChanged() {
            const MUEnabled = $(this).is(":checked");
            $.ajax({
                url: botblockerData.ajaxurl,
                type: "POST",
                data: {
                    action: "bbcs_toggle_early_phase_in_db",
                    nonce: botblockerData.nonce,
                    setting: 'mu_enable',
                    mu_enable: MUEnabled ? 1 : 0,
                },
                success: function (response) {
                    if (response.success) {
                     //   console.log("MU setting updated.");
                        if (MUEnabled) {
                            $('#bbcs_switch_early_init').prop('checked', false);
                        }
                        if (window.location.search.indexOf('page=bbcs_setup_guide') !== -1) {
                            setTimeout(function () { window.location.reload(); }, 300);
                        }
                    } else {
                        console.error("MU update failed: " + response.data);
                    }
                },
                error: function (xhr, status, error) {
                    alert("AJAX Error: " + error);
                },
            });
        }

        function onEarlyInitPluginChanged() {
            const EarlyInitEnabled = $(this).is(":checked");
            var $el = $(this);
            var earlyAvailable = $el.data('early-available');
            var addonsUrl = $el.data('addons-url');
            var proUrl = $el.data('pro-url');
            if (!earlyAvailable && EarlyInitEnabled) {

                $el.prop('checked', false);
                var go = confirm('Early Init requires active Cloud API Connection and the Early Init addon. Open Addons page?');
                if (go && addonsUrl) { window.location.href = addonsUrl; }
                return;
            }
            $.ajax({
                url: botblockerData.ajaxurl,
                type: "POST",
                data: {
                    action: "bbcs_toggle_early_phase_in_db",
                    nonce: botblockerData.nonce,
                    setting: 'early_init_enable',
                    early_init_enable: EarlyInitEnabled ? 1 : 0,
                },
                success: function (response) {
                    if (response.success) {
                     //   console.log("EarlyInit setting updated.");
                        if (EarlyInitEnabled) {
                            $('#bbcs_switch_mu_plugin').prop('checked', false);
                        }
                        if (window.location.search.indexOf('page=bbcs_setup_guide') !== -1) {
                            setTimeout(function () { window.location.reload(); }, 300);
                        }
                    } else {

                        $el.prop('checked', false);
                        var msg = response.data || 'Early Init cannot be enabled. Please ensure Cloud API and addon are active.';
                        alert(msg);
                    }
                },
                error: function (xhr, status, error) {
                    alert("AJAX Error: " + error);
                },
            });
        }

        $("#bbcs_switch_redis, #bbcs_integrations_switch_redis, #bbcs_switch_memcached, #bbcs_integrations_switch_memcached").on(
            "change",
            onCacheCheckboxChanged
        );

        $("#bbcs_switch_apcu").on("change", onApcuChanged);
        $("#bbcs_switch_mu_plugin").on("change", onMUPluginChanged);
        $("#bbcs_switch_early_init").on("change", onEarlyInitPluginChanged);
    });

    document.addEventListener("DOMContentLoaded", function () {
        if (
            typeof initializeServiceAvailability === "function" &&
            typeof window.botblockerData !== "undefined"
        ) {
            initializeServiceAvailability();
        }
    });
    window.initializeServiceAvailability = function () {
        if (
            typeof window.botblockerRedisMemcachedAvailability === "undefined"
        ) {
            return;
        }

        const redisAvailable =
            window.botblockerRedisMemcachedAvailability.redisAvailable;
        const memcachedAvailable =
            window.botblockerRedisMemcachedAvailability.memcachedAvailable;

        const redisCheckboxes = $(
            "#bbcs_switch_redis, #bbcs_integrations_switch_redis"
        );
        const memcachedCheckboxes = $(
            "#bbcs_switch_memcached, #bbcs_integrations_switch_memcached"
        );

        if (!redisAvailable) {
            redisCheckboxes.prop("disabled", true);
        }

        if (!memcachedAvailable) {
            memcachedCheckboxes.prop("disabled", true);
        }
    };

    window.copyToClipboard = function (button) {
        const input = button.previousElementSibling;
        input.select();
        input.setSelectionRange(0, 99999);
        navigator.clipboard.writeText(input.value).then(() => {
            button.querySelector('i').setAttribute('title', 'Copied!');
            const tooltip = bootstrap.Tooltip.getInstance(button.querySelector('i'));
            tooltip.show();
            setTimeout(() => {
                tooltip.hide();
                button.querySelector('i').setAttribute('title', 'Copy to clipboard');
            }, 2000);
        }).catch(err => {
            alert('Failed to copy: ' + err);
        });
    };




    // Cron tasks
    $(document).ready(function () {
        let tasks = [];
        let lastUpdateTime = Date.now();

        function fetchCronTasks() {
            $.ajax({
                url: botblockerData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'bbcs_get_cron_tasks',
                    nonce: botblockerData.nonce,
                },
                success: function (response) {
                    if (response.success) {
                        tasks = response.data;
                        lastUpdateTime = Date.now();
                        renderTasks();
                    } else {
                        console.error('Failed to fetch cron tasks:', response.data);
                    }
                },
                error: function () {
                    console.error('Failed to fetch cron tasks.');
                }
            });
        }

        function isObject(value) {
            return value && typeof value === 'object' && !Array.isArray(value);
        }

        function renderTasks() {
            const $taskList = $('.bbcs-task-list');
            const $taskCount = $('.task-count');
            $taskList.empty();
            $taskCount.text(tasks.length);

            tasks.forEach(task => {
                //  const timeInSeconds = parseTimeRemaining(task.time_remaining);
                if (isObject(task)) {
                    const taskHtml = `
                        <li>
                            <p class="clearfix mb-1">
                                <span class="message float-start">${task.description}</span>
                                <span class="message float-end text-dark time-remaining" 
                                      data-seconds="${task.time_remaining}">${formatTimeDiff(task.time_remaining)}</span>
                            </p>
                            <div class="progress progress-xs light">
                                <div class="progress-bar" role="progressbar"
                                     aria-valuenow="${task.progress}"
                                     aria-valuemin="0" aria-valuemax="100"
                                     style="width: ${task.progress}%;"></div>
                            </div>
                        </li>
                    `;
                    $taskList.append(taskHtml);
                } else {
                    console.warn(`Task "${task.description}" has invalid time_remaining: ${task.time_remaining}`);
                }
            });
        }

        function updateTimes() {
            $('.time-remaining').each(function () {
                const $this = $(this);
                let remainingSeconds = parseInt($this.data('seconds'), 10);

                if (remainingSeconds > 0) {
                    remainingSeconds -= 1;
                    $this.data('seconds', remainingSeconds);
                    $this.text(formatTimeDiff(remainingSeconds));
                } else {
                    $this.text('Overdue');
                }
            });
        }


        function formatTimeDiff(seconds) {
            if (seconds < 60) return `${seconds}s`;
            if (seconds < 3600) return `${Math.floor(seconds / 60)}m ${seconds % 60}s`;
            return `${Math.floor(seconds / 3600)}h ${Math.floor((seconds % 3600) / 60)}m`;
        }

        fetchCronTasks();
        setInterval(fetchCronTasks, 60000);
        setInterval(updateTimes, 1000);
    });


    $(document).ready(function () {
        setTimeout(function () {
            var $n = $('#setting-error-botblocker_message');
            if ($n.length) {
                $n.fadeOut(300, function () { $(this).remove(); });
            }
        }, 5000);
    });

    $('.bbcs-btn-blink').on('click', function (e) {
        e.preventDefault();
        const addon_btn = $('.bbcs-link-blink');
        if (addon_btn.length > 0) {
            addon_btn.each(function (index, el) {
                bbcsBlinkTab(el, 5, 'cornflowerblue', 150);
            });
        }
    });

    $(document).ready(function () {
        const $activationBtn = $('#bbcs_send_activation_btn');
        const $emailInput = $('#bbcs_contact_email');

        if (!$activationBtn.length || !$emailInput.length) {
            return;
        }

        $activationBtn.on('click', function (e) {
            e.preventDefault();
            
            const $btn = $(this);
            const data = ($emailInput.val() || '').trim();
            const initialText = $btn.html();
            
            $btn.prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin me-2"></i>');

            $.ajax({
                url: botblockerData.ajaxurl,
                type: 'POST',
                data: {
                    action: 'bbcs_contact_email',
                    nonce: botblockerData.nonce,
                    data: data
                },
                success: function (response) {
                    $btn.html('<i class="fa-solid fa-check me-2"></i>');
                    $btn.prop('disabled', true);
                    setTimeout(function () {
                        $btn.closest('.card').fadeOut();
                    }, 1000);
                },
                error: function (xhr, status, error) {
                    $btn.prop('disabled', false).html(initialText);
                    const errorMsg = (xhr.responseJSON && xhr.responseJSON.data) ? xhr.responseJSON.data : error;
                    alert('Error: ' + errorMsg);
                }
            });
        });
    });

})(jQuery);