<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// === Infrastructure ===
require_once BOTBLOCKER_DIR . 'includes/install/class-botblocker-install.php';
require_once BOTBLOCKER_DIR . 'includes/install/class-botblocker-install-ip.php';
require_once BOTBLOCKER_DIR . 'includes/install/class-botblocker-migration.php';
require_once BOTBLOCKER_DIR . 'includes/utilites/util-botblocker-data-file.php';
require_once BOTBLOCKER_DIR . 'includes/utilites/util-botblocker-tampering.php';
require_once BOTBLOCKER_DIR . 'includes/hook/class-botblocker-login-brutforce.php';
require_once BOTBLOCKER_DIR . 'includes/hook/botblocker-hook-login-brutforce.php';
require_once BOTBLOCKER_DIR . 'includes/inc-botblocker-shortcode.php';
require_once BOTBLOCKER_DIR . 'includes/pro/botblocker-cloud-bb.php';
require_once BOTBLOCKER_DIR . 'includes/class-botblocker-check.php';

// === Verification / Connectors ===
require_once BOTBLOCKER_DIR . 'includes/inc-botblocker-verify-endpoint.php';
require_once BOTBLOCKER_DIR . 'includes/inc-botblocker-connectors.php';

// === Main plugin bootstrap ===
require_once BOTBLOCKER_DIR . 'includes/class-cyber-secure-botblocker.php';

// === Core utilities ===
require_once BOTBLOCKER_DIR . 'includes/cache/class-bbcs-object-cache-storage.php';
require_once BOTBLOCKER_DIR . 'includes/rules/class-botblocker-rkn.php';
require_once BOTBLOCKER_DIR . 'includes/utilites/util-botblocker-request.php';
require_once BOTBLOCKER_DIR . 'includes/rules/class-botblocker-rugov.php';
require_once BOTBLOCKER_DIR . 'includes/data/class-botblocker-stats.php';
require_once BOTBLOCKER_DIR . 'includes/utilites/util-botblocker-wp.php';
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
