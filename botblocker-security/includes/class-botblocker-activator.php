<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

class Botblocker_Activator {
	/**
	 * Activation hook placeholder.
	 *
	 * All per-site activation logic is handled directly in
	 * bbcs_activate_botblocker() / bbcs_activate_network_wide() via
	 * bbcs_check_install(), bbcs_register_cron_tasks(), etc.
	 * This class is retained for structural compatibility only.
	 */
	public static function activate() {
		// Intentionally empty - activation logic handled by bbcs_check_install().
	}
}
