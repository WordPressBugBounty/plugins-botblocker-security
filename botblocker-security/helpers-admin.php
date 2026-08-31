<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// === Shared with scheduled tasks (also loaded by the cron fallback runner) ===
require_once BOTBLOCKER_DIR . 'helpers-cron.php';

// === Admin-only data / UI ===
require_once BOTBLOCKER_DIR . 'includes/data/class-botblocker-lang-options.php';
require_once BOTBLOCKER_DIR . 'includes/data/class-botblocker-system-info-data.php';
require_once BOTBLOCKER_DIR . 'includes/data/class-botblocker-snav.php';
require_once BOTBLOCKER_DIR . 'includes/data/class-botblocker-palette.php';

// === Install / Activation ===
require_once BOTBLOCKER_DIR . 'includes/install/class-botblocker-seed-data.php';
require_once BOTBLOCKER_DIR . 'includes/install/class-botblocker-activator.php';
require_once BOTBLOCKER_DIR . 'includes/install/class-botblocker-deactivator.php';
require_once BOTBLOCKER_DIR . 'includes/utilites/class-botblocker-deactivation-feedback.php';

// === AJAX wrappers (hook registration) ===
require_once BOTBLOCKER_DIR . 'includes/ajax/inc-botblocker-ajax-wrappers.php';

// === Admin hooks ===
require_once BOTBLOCKER_DIR . 'includes/hook/class-botblocker-settings-hooks.php';
require_once BOTBLOCKER_DIR . 'includes/hook/class-botblocker-addon-hooks.php';
require_once BOTBLOCKER_DIR . 'includes/hook/class-botblocker-tools-hooks.php';
require_once BOTBLOCKER_DIR . 'includes/hook/class-botblocker-addon-settings-hooks.php';
require_once BOTBLOCKER_DIR . 'includes/hook/class-botblocker-payment-hooks.php';

// === Admin utilities / marketing ===
require_once BOTBLOCKER_DIR . 'includes/pro/class-botblocker-settings-presets.php';
require_once BOTBLOCKER_DIR . 'includes/data/class-botblocker-marketing-blocks.php';
require_once BOTBLOCKER_DIR . 'includes/mail/class-botblocker-mailer.php';
require_once BOTBLOCKER_DIR . 'includes/utilites/util-botblocker-user.php';
require_once BOTBLOCKER_DIR . 'includes/data/class-botblocker-news.php';
require_once BOTBLOCKER_DIR . 'includes/utilites/class-botblocker-support.php';
BotBlockerSupport::register();

// === Sync (admin) ===
require_once BOTBLOCKER_DIR . 'includes/utilites/class-botblocker-early-phase-dedup.php';

// === AJAX handler classes ===
require_once BOTBLOCKER_DIR . 'includes/ajax/class-botblocker-ajax-ip-rules-trait.php';
require_once BOTBLOCKER_DIR . 'includes/ajax/class-botblocker-ajax-ipv4-rules.php';
require_once BOTBLOCKER_DIR . 'includes/ajax/class-botblocker-ajax-ipv6-rules.php';
require_once BOTBLOCKER_DIR . 'includes/ajax/class-botblocker-ajax-rules.php';
require_once BOTBLOCKER_DIR . 'includes/ajax/class-botblocker-ajax-hits.php';
require_once BOTBLOCKER_DIR . 'includes/ajax/class-botblocker-ajax-audit.php';
require_once BOTBLOCKER_DIR . 'includes/ajax/class-botblocker-ajax-paths.php';
require_once BOTBLOCKER_DIR . 'includes/ajax/class-botblocker-ajax-proxy.php';
require_once BOTBLOCKER_DIR . 'includes/ajax/class-botblocker-ajax-asn.php';
require_once BOTBLOCKER_DIR . 'includes/ajax/class-botblocker-ajax-white-bots.php';
require_once BOTBLOCKER_DIR . 'includes/ajax/class-botblocker-ajax-llm.php';
require_once BOTBLOCKER_DIR . 'includes/ajax/class-botblocker-ajax-addons.php';
require_once BOTBLOCKER_DIR . 'includes/ajax/class-botblocker-ajax-maintenance.php';
require_once BOTBLOCKER_DIR . 'includes/ajax/class-botblocker-ajax-backup.php';
require_once BOTBLOCKER_DIR . 'includes/ajax/class-botblocker-ajax-cache.php';
require_once BOTBLOCKER_DIR . 'includes/ajax/class-botblocker-ajax-debug.php';
require_once BOTBLOCKER_DIR . 'includes/ajax/class-botblocker-ajax-geo.php';
require_once BOTBLOCKER_DIR . 'includes/ajax/class-botblocker-ajax-email.php';
require_once BOTBLOCKER_DIR . 'includes/ajax/class-botblocker-ajax-early-phase.php';
require_once BOTBLOCKER_DIR . 'includes/ajax/class-botblocker-ajax-secret-links.php';
require_once BOTBLOCKER_DIR . 'includes/ajax/class-botblocker-ajax-profile.php';
require_once BOTBLOCKER_DIR . 'includes/ajax/class-botblocker-ajax-rugov.php';
require_once BOTBLOCKER_DIR . 'includes/ajax/class-botblocker-ajax-rules-stats.php';
require_once BOTBLOCKER_DIR . 'includes/ajax/class-botblocker-ajax-tls-fingerprints.php';
