<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// === Admin-only data / UI ===
require_once BOTBLOCKER_DIR . 'includes/data/botblocker-data-lang-options.php';
require_once BOTBLOCKER_DIR . 'includes/data/botblocker-data-system-info.php';
require_once BOTBLOCKER_DIR . 'includes/data/botblocker-data-snav.php';
require_once BOTBLOCKER_DIR . 'includes/data/botblocker-data-palette.php';

// === Install / Activation ===
require_once BOTBLOCKER_DIR . 'includes/install/class-botblocker-seed-data.php';
require_once BOTBLOCKER_DIR . 'includes/install/class-botblocker-activator.php';
require_once BOTBLOCKER_DIR . 'includes/install/class-botblocker-deactivator.php';

// === AJAX wrappers (hook registration) ===
require_once BOTBLOCKER_DIR . 'includes/ajax/inc-botblocker-ajax-wrappers.php';

// === Admin hooks ===
require_once BOTBLOCKER_DIR . 'includes/hook/class-botblocker-cloud-api-hooks.php';
require_once BOTBLOCKER_DIR . 'includes/hook/class-botblocker-settings-hooks.php';
require_once BOTBLOCKER_DIR . 'includes/hook/class-botblocker-addon-hooks.php';
require_once BOTBLOCKER_DIR . 'includes/hook/class-botblocker-tools-hooks.php';
require_once BOTBLOCKER_DIR . 'includes/hook/class-botblocker-addon-settings-hooks.php';
require_once BOTBLOCKER_DIR . 'includes/hook/class-botblocker-payment-hooks.php';

// === Admin utilities / marketing ===
require_once BOTBLOCKER_DIR . 'includes/pro/botblocker-presets.php';
require_once BOTBLOCKER_DIR . 'includes/data/botblocker-marketing-blocks.php';
require_once BOTBLOCKER_DIR . 'includes/mail/class-botblocker-mailer.php';
require_once BOTBLOCKER_DIR . 'includes/utilites/util-botblocker-user.php';
require_once BOTBLOCKER_DIR . 'includes/data/botblocker-data-news.php';
require_once BOTBLOCKER_DIR . 'includes/data/class-botblocker-summary.php';
require_once BOTBLOCKER_DIR . 'includes/utilites/util-botblocker-support.php';
require_once BOTBLOCKER_DIR . 'includes/class-botblocker-telegram.php';

// === Sync (admin/cron) ===
require_once BOTBLOCKER_DIR . 'includes/cache/class-botblocker-llm-sync.php';
require_once BOTBLOCKER_DIR . 'includes/cache/class-botblocker-tls-fingerprints-sync.php';
require_once BOTBLOCKER_DIR . 'includes/utilites/util-botblocker-early-phase-dedup.php';

// === AJAX handler classes ===
require_once BOTBLOCKER_DIR . 'includes/ajax/class-botblocker-ajax-ip-rules-trait.php';
require_once BOTBLOCKER_DIR . 'includes/ajax/class-botblocker-ajax-ipv4-rules.php';
require_once BOTBLOCKER_DIR . 'includes/ajax/class-botblocker-ajax-ipv6-rules.php';
require_once BOTBLOCKER_DIR . 'includes/ajax/class-botblocker-ajax-rules.php';
require_once BOTBLOCKER_DIR . 'includes/ajax/class-botblocker-ajax-hits.php';
require_once BOTBLOCKER_DIR . 'includes/ajax/class-botblocker-ajax-paths.php';
require_once BOTBLOCKER_DIR . 'includes/ajax/class-botblocker-ajax-proxy.php';
require_once BOTBLOCKER_DIR . 'includes/ajax/class-botblocker-ajax-asn.php';
require_once BOTBLOCKER_DIR . 'includes/ajax/class-botblocker-ajax-white-bots.php';
require_once BOTBLOCKER_DIR . 'includes/ajax/class-botblocker-ajax-llm.php';
require_once BOTBLOCKER_DIR . 'includes/ajax/class-botblocker-ajax-maintenance.php';
require_once BOTBLOCKER_DIR . 'includes/ajax/class-botblocker-ajax-backup.php';
require_once BOTBLOCKER_DIR . 'includes/ajax/class-botblocker-ajax-cache.php';
require_once BOTBLOCKER_DIR . 'includes/ajax/class-botblocker-ajax-debug.php';
require_once BOTBLOCKER_DIR . 'includes/ajax/class-botblocker-ajax-geo.php';
require_once BOTBLOCKER_DIR . 'includes/ajax/class-botblocker-ajax-email.php';
require_once BOTBLOCKER_DIR . 'includes/ajax/class-botblocker-ajax-early-phase.php';
require_once BOTBLOCKER_DIR . 'includes/ajax/class-botblocker-ajax-profile.php';
require_once BOTBLOCKER_DIR . 'includes/ajax/class-botblocker-ajax-rugov.php';
require_once BOTBLOCKER_DIR . 'includes/ajax/class-botblocker-ajax-rules-stats.php';
require_once BOTBLOCKER_DIR . 'includes/ajax/class-botblocker-ajax-tls-fingerprints.php';
