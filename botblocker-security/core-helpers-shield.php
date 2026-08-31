<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once BOTBLOCKER_DIR . 'includes/class-botblocker-asn-value.php';
require_once BOTBLOCKER_DIR . 'includes/install/class-botblocker-install.php';
require_once BOTBLOCKER_DIR . 'includes/install/class-botblocker-install-ip.php';
require_once BOTBLOCKER_DIR . 'includes/install/class-botblocker-migration.php';
require_once BOTBLOCKER_DIR . 'includes/data/class-botblocker-data-file.php';
require_once BOTBLOCKER_DIR . 'includes/cache/class-botblocker-compiled-file.php';
require_once BOTBLOCKER_DIR . 'includes/class-botblocker-check.php';

require_once BOTBLOCKER_DIR . 'includes/class-botblocker-verify-endpoint.php';
BotBlockerVerifyEndpoint::register();
require_once BOTBLOCKER_DIR . 'includes/class-botblocker-connectors.php';
BotBlockerConnectors::register();

require_once BOTBLOCKER_DIR . 'includes/hook/class-botblocker-login-brutforce.php';
require_once BOTBLOCKER_DIR . 'includes/hook/botblocker-hook-login-brutforce.php';

require_once BOTBLOCKER_DIR . 'includes/class-botblocker-security.php';

require_once BOTBLOCKER_DIR . 'includes/cache/class-bbcs-object-cache-storage.php';
require_once BOTBLOCKER_DIR . 'includes/rules/class-botblocker-rkn.php';
require_once BOTBLOCKER_DIR . 'includes/rules/class-botblocker-rugov.php';
require_once BOTBLOCKER_DIR . 'includes/utilites/util-botblocker-request.php';
require_once BOTBLOCKER_DIR . 'includes/utilites/util-botblocker-wp.php';
require_once BOTBLOCKER_DIR . 'includes/ip/class-botblocker-ip.php';
require_once BOTBLOCKER_DIR . 'includes/botblocker/class-botblocker-ban-target-resolver.php';
require_once BOTBLOCKER_DIR . 'includes/pro/class-botblocker-pro.php';
require_once BOTBLOCKER_DIR . 'includes/pro/class-botblocker-cloud-bb.php';
require_once BOTBLOCKER_DIR . 'includes/database/class-botblocker-asn-db.php';
require_once BOTBLOCKER_DIR . 'includes/cache/class-botblocker-cache.php';
require_once BOTBLOCKER_DIR . 'includes/class-botblocker-addons.php';
require_once BOTBLOCKER_DIR . 'includes/database/class-botblocker-counters.php';
require_once BOTBLOCKER_DIR . 'includes/database/class-botblocker-db.php';
require_once BOTBLOCKER_DIR . 'includes/database/class-botblocker-store.php';
require_once BOTBLOCKER_DIR . 'includes/cache/class-botblocker-file-renderer.php';
require_once BOTBLOCKER_DIR . 'includes/data/class-botblocker-data-time.php';
