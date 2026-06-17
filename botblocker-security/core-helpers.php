<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// === Infrastructure ===
require_once BOTBLOCKER_DIR . 'includes/install/class-botblocker-install.php';
require_once BOTBLOCKER_DIR . 'includes/install/class-botblocker-install-ip.php';
require_once BOTBLOCKER_DIR . 'includes/install/class-botblocker-migration.php';
require_once BOTBLOCKER_DIR . 'includes/install/class-botblocker-seed-data.php';
require_once BOTBLOCKER_DIR . 'includes/utilites/util-botblocker-data-file.php';
require_once BOTBLOCKER_DIR . 'includes/utilites/util-botblocker-tampering.php';
require_once BOTBLOCKER_DIR . 'includes/ajax/inc-botblocker-ajax-wrappers.php';
require_once BOTBLOCKER_DIR . 'includes/hook/class-botblocker-cloud-api-hooks.php';
require_once BOTBLOCKER_DIR . 'includes/hook/class-botblocker-settings-hooks.php';
require_once BOTBLOCKER_DIR . 'includes/hook/class-botblocker-addon-hooks.php';
require_once BOTBLOCKER_DIR . 'includes/hook/class-botblocker-payment-hooks.php';
require_once BOTBLOCKER_DIR . 'includes/hook/class-botblocker-login-brutforce.php';
require_once BOTBLOCKER_DIR . 'includes/hook/botblocker-hook-login-brutforce.php';
require_once BOTBLOCKER_DIR . 'includes/inc-botblocker-shortcode.php';
require_once BOTBLOCKER_DIR . 'includes/pro/botblocker-cloud-bb.php';
require_once BOTBLOCKER_DIR . 'includes/pro/botblocker-presets.php';
require_once BOTBLOCKER_DIR . 'includes/class-botblocker-check.php';

// === Activation ===
require_once BOTBLOCKER_DIR . 'includes/install/class-botblocker-activator.php';
require_once BOTBLOCKER_DIR . 'includes/install/class-botblocker-deactivator.php';

// === Endpoints / Marketing / Connectors ===
require_once BOTBLOCKER_DIR . 'includes/inc-botblocker-verify-endpoint.php';
require_once BOTBLOCKER_DIR . 'includes/data/botblocker-marketing-blocks.php';
require_once BOTBLOCKER_DIR . 'includes/inc-botblocker-connectors.php';

// === Main plugin bootstrap ===
require_once BOTBLOCKER_DIR . 'includes/class-cyber-secure-botblocker.php';

// === Utility files ===
require_once BOTBLOCKER_DIR . 'includes/cache/class-bbcs-object-cache-storage.php';
require_once BOTBLOCKER_DIR . 'includes/mail/class-botblocker-mailer.php';
require_once BOTBLOCKER_DIR . 'includes/rules/class-botblocker-rkn.php';
require_once BOTBLOCKER_DIR . 'includes/utilites/util-botblocker-user.php';
require_once BOTBLOCKER_DIR . 'includes/utilites/util-botblocker-request.php';
require_once BOTBLOCKER_DIR . 'includes/rules/class-botblocker-rugov.php';
require_once BOTBLOCKER_DIR . 'includes/data/class-botblocker-summary.php';
require_once BOTBLOCKER_DIR . 'includes/data/class-botblocker-stats.php';
require_once BOTBLOCKER_DIR . 'includes/utilites/util-botblocker-wp.php';
require_once BOTBLOCKER_DIR . 'includes/utilites/util-botblocker-support.php';

// === OOP Classes ===
require_once BOTBLOCKER_DIR . 'includes/data/class-botblocker-payment-data.php';
require_once BOTBLOCKER_DIR . 'includes/ip/class-botblocker-ip.php';
require_once BOTBLOCKER_DIR . 'includes/pro/class-botblocker-pro.php';
require_once BOTBLOCKER_DIR . 'includes/database/class-botblocker-asn-db.php';
require_once BOTBLOCKER_DIR . 'includes/cron/class-botblocker-cron.php';
require_once BOTBLOCKER_DIR . 'includes/cache/class-botblocker-cache.php';
require_once BOTBLOCKER_DIR . 'includes/class-botblocker-addons.php';
require_once BOTBLOCKER_DIR . 'includes/database/class-botblocker-counters.php';
require_once BOTBLOCKER_DIR . 'includes/database/class-botblocker-db.php';
require_once BOTBLOCKER_DIR . 'includes/database/class-botblocker-store.php';
require_once BOTBLOCKER_DIR . 'includes/cache/class-botblocker-file-renderer.php';
require_once BOTBLOCKER_DIR . 'includes/cache/class-botblocker-llm-sync.php';

// === AJAX Classes ===
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
