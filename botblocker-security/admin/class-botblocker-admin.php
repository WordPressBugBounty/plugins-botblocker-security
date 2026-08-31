<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( defined( 'BOTBLOCKER_WIDGETS' ) && BOTBLOCKER_WIDGETS ) {
	require_once BOTBLOCKER_DIR . 'admin/partials/class-botblocker-dashboard-widgets.php';
	BotBlockerDashboardWidgets::register();
}

require_once BOTBLOCKER_DIR . 'admin/class-botblocker-admin-settings.php';

class Botblocker_Admin {

	use BotBlockerAdminSettingsTrait;

	private static ?self $instance = null;

	public static function getInstance(): self {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		$this->register_admin_bar();
	}

	private function get_botblocker_screen_ids(): array {
		$base_ids = array(
			'toplevel_page_bbcs_dashboard',
			'botblocker_page_bbcs_settings',
			'botblocker_page_bbcs_integrations',
			'botblocker_page_bbcs_rules',
			'botblocker_page_bbcs_tools',
			'botblocker_page_bbcs_reports',
			'botblocker_page_bbcs_cloud_api',
			'botblocker_page_bbcs_setup_guide',
			'botblocker_page_bbcs_about',
			'botblocker_page_bbcs_addons',
		);

		$screen_ids = $base_ids;
		foreach ( $base_ids as $id ) {
			$screen_ids[] = $id . '-network';
		}

		return $screen_ids;
	}

	private function is_screen( string $screen_id, string $base_id ): bool {
		return $screen_id === $base_id || $screen_id === $base_id . '-network';
	}

	private function render_page( string $page, ...$args ): void {
		$template = BOTBLOCKER_DIR . 'admin/templates/' . $page . '.php';

		if ( file_exists( $template ) ) {
			$renderer = require $template;
			$renderer( ...$args );
		}
	}

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles(): void {
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->id, $this->get_botblocker_screen_ids(), true ) ) {
			return;
		}

		$this->enqueue_new_ui_styles( $screen );
	}

	/**
	 * New unified admin UI stylesheet bundle.
	 *
	 * Loaded on all BotBlocker admin screens.
	 * No Bootstrap - all layout uses bbcs-* classes from the design system.
	 */
	private function enqueue_new_ui_styles( ?WP_Screen $screen = null ): void {
		if ( ! $screen ) {
			$screen = get_current_screen();
		}
		$deps = array( 'common' );
		wp_enqueue_style( 'bbcs-tokens', plugin_dir_url( __FILE__ ) . 'css/bbcs-tokens.css', $deps, BOTBLOCKER_VERSION, 'all' );
		wp_enqueue_style( 'bbcs-main', plugin_dir_url( __FILE__ ) . 'css/bbcs.css', array( 'bbcs-tokens' ), BOTBLOCKER_VERSION, 'all' );
		wp_enqueue_style( 'bbcs-fa', plugin_dir_url( __FILE__ ) . 'css/all.min.css', $deps, BOTBLOCKER_VERSION, 'all' );
		wp_enqueue_style( 'bbcs-support', plugin_dir_url( __FILE__ ) . 'css/botblocker-support-component.css', array( 'bbcs-main' ), BOTBLOCKER_VERSION, 'all' );
		wp_enqueue_style( 'bbcs-flags', plugin_dir_url( __FILE__ ) . 'css/flags/flags.css', $deps, BOTBLOCKER_VERSION, 'all' );
		// DataTables CSS - only on screens that render data tables (rules, reports).
		if ( $this->is_screen( $screen->id, 'botblocker_page_bbcs_rules' ) || $this->is_screen( $screen->id, 'botblocker_page_bbcs_reports' ) ) {
			wp_enqueue_style( BOTBLOCKER_SHORT_NAME . '-datatables', plugin_dir_url( __FILE__ ) . 'css/datatables/datatables.min.css', $deps, BOTBLOCKER_VERSION, 'all' );
			wp_enqueue_style( 'bbcs-bridge', plugin_dir_url( __FILE__ ) . 'css/bbcs/bbcs-bridge.css', array( 'bbcs-tokens' ), BOTBLOCKER_VERSION, 'all' );
		}

		// jsvectormap CSS - dashboard world map and reports geo chart.
		if ( $this->is_screen( $screen->id, 'toplevel_page_bbcs_dashboard' ) || $this->is_screen( $screen->id, 'botblocker_page_bbcs_reports' ) ) {
			wp_enqueue_style( BOTBLOCKER_SHORT_NAME . '-jsvectormap', plugin_dir_url( __FILE__ ) . 'css/jsvectormap/jsvectormap.min.css', $deps, BOTBLOCKER_VERSION, 'all' );
		}

		// Setup wizard CSS - setup guide and settings pages (one-click modal).
		if ( $this->is_screen( $screen->id, 'botblocker_page_bbcs_setup_guide' ) || $this->is_screen( $screen->id, 'botblocker_page_bbcs_settings' ) ) {
			wp_enqueue_style( 'bbcs-setup-wizard-new', plugin_dir_url( __FILE__ ) . 'css/bbcs-setup-wizard-new.css', array( 'bbcs-main' ), BOTBLOCKER_VERSION, 'all' );
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts(): void {
		$screen = get_current_screen();
		if ( ! $screen || ! in_array( $screen->id, $this->get_botblocker_screen_ids(), true ) ) {
			return;
		}

		$this->enqueue_new_ui_scripts();
	}

	/**
	 * New unified admin UI script bundle.
	 *
	 * Loaded on all BotBlocker admin screens.
	 */
	private function enqueue_new_ui_scripts(): void {
		$screen = get_current_screen();
		if ( ! $screen ) {
			return;
		}

		// Chart.js - screens with chart components.
		$chart_screens = array( 'toplevel_page_bbcs_dashboard', 'botblocker_page_bbcs_setup_guide', 'botblocker_page_bbcs_rules', 'botblocker_page_bbcs_reports' );
		foreach ( $chart_screens as $chart_screen ) {
			if ( $this->is_screen( $screen->id, $chart_screen ) ) {
				wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-chartjs', plugin_dir_url( __FILE__ ) . 'js/chartjs/chart.umd.js', array(), BOTBLOCKER_VERSION, false );
				wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-charts-js', plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-charts.js', array( 'jquery', BOTBLOCKER_SHORT_NAME . '-chartjs' ), BOTBLOCKER_VERSION, true );
				wp_localize_script( BOTBLOCKER_SHORT_NAME . '-charts-js', 'bbcsChartsL10n', array(
					'no_data'      => __( 'No data yet', 'botblocker-security' ),
					'no_data_hint' => __( 'Shows up after the first hits', 'botblocker-security' ),
				) );
				break;
			}
		}

		// Dashboard page - additional scripts (world map + dashboard logic).
		if ( $this->is_screen( $screen->id, 'toplevel_page_bbcs_dashboard' ) ) {
			wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-jsvectormap', plugin_dir_url( __FILE__ ) . 'js/jsvectormap/jsvectormap.js', array(), BOTBLOCKER_VERSION, false );
			wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-jsvectormap-world', plugin_dir_url( __FILE__ ) . 'js/jsvectormap/maps/world.js', array( BOTBLOCKER_SHORT_NAME . '-jsvectormap' ), BOTBLOCKER_VERSION, false );
			wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-dash-js', plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-dashboard.js', array( 'jquery', BOTBLOCKER_SHORT_NAME . '-multipage-js', BOTBLOCKER_SHORT_NAME . '-toast-js' ), BOTBLOCKER_VERSION, true );
			wp_localize_script( BOTBLOCKER_SHORT_NAME . '-dash-js', 'bbcsDashL10n', array(
				'error_prefix'        => __( 'Error: ', 'botblocker-security' ),
				'ajax_error'          => __( 'AJAX Error: ', 'botblocker-security' ),
				'regenerate_confirm'  => __( 'Regenerate security action links? Old links will stop working immediately.', 'botblocker-security' ),
			) );
		}

		// Reports page - world map (geo chart on dashboard tab).
		if ( $this->is_screen( $screen->id, 'botblocker_page_bbcs_reports' ) ) {
			wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-jsvectormap', plugin_dir_url( __FILE__ ) . 'js/jsvectormap/jsvectormap.js', array(), BOTBLOCKER_VERSION, false );
			wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-jsvectormap-world', plugin_dir_url( __FILE__ ) . 'js/jsvectormap/maps/world.js', array( BOTBLOCKER_SHORT_NAME . '-jsvectormap' ), BOTBLOCKER_VERSION, false );
		}

		// DataTables - screens with data tables (rules, reports).
		$datatable_screens = array( 'botblocker_page_bbcs_rules', 'botblocker_page_bbcs_reports' );
		foreach ( $datatable_screens as $dt_screen ) {
			if ( $this->is_screen( $screen->id, $dt_screen ) ) {
				wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-datatables-js', plugin_dir_url( __FILE__ ) . 'js/datatables/datatables.min.js', array( 'jquery' ), BOTBLOCKER_VERSION, false );
				break;
			}
		}

		if ( $this->is_screen( $screen->id, 'botblocker_page_bbcs_setup_guide' ) || $this->is_screen( $screen->id, 'botblocker_page_bbcs_settings' ) ) {
			wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-setup-js', plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-setup.js', array( 'jquery', BOTBLOCKER_SHORT_NAME . '-toast-js' ), BOTBLOCKER_VERSION, true );
			wp_localize_script( BOTBLOCKER_SHORT_NAME . '-setup-js', 'bbcsSetupL10n', array(
				'pro_required'   => __( 'Full Protection requires PRO license. Please upgrade to PRO first.', 'botblocker-security' ),
				'error_apply'    => __( 'Error applying profile', 'botblocker-security' ),
				'request_failed' => __( 'Request failed. Please try again.', 'botblocker-security' ),
				'please_wait'    => __( 'Please wait...', 'botblocker-security' ),
				'apply_now'      => __( 'Apply Now', 'botblocker-security' ),
			) );
		}

		// Shared: lightweight helpers for scroll-to-setting + blink highlight (used by snav and multipage).
		wp_enqueue_script(
			BOTBLOCKER_SHORT_NAME . '-shared-helpers-js',
			plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-shared-helpers.js',
			array( 'jquery', BOTBLOCKER_SHORT_NAME . '-toast-js' ),
			BOTBLOCKER_VERSION,
			true
		);

		// Shared: vertical nav sidebar (snav) - used by Settings, Integrations, Tools, and Addons pages.
		if ( $this->is_screen( $screen->id, 'botblocker_page_bbcs_settings' ) || $this->is_screen( $screen->id, 'botblocker_page_bbcs_integrations' ) || $this->is_screen( $screen->id, 'botblocker_page_bbcs_tools' ) || $this->is_screen( $screen->id, 'botblocker_page_bbcs_addons' ) ) {
			wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-help-tips-js', plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-help-tips.js', array(), BOTBLOCKER_VERSION, true );
			wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-snav-js', plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-snav.js', array( 'jquery', BOTBLOCKER_SHORT_NAME . '-shared-helpers-js' ), BOTBLOCKER_VERSION, true );

			if ( class_exists( 'BotBlockerSnav' ) ) {
				wp_add_inline_script(
					BOTBLOCKER_SHORT_NAME . '-snav-js',
					'var BOTBLOCKER_GLOBAL_SEARCH_INDEX = ' . wp_json_encode( BotBlockerSnav::getGlobalSearchIndex() ) . ';',
					'before'
				);
			}
		}

		// Shared: support button on all admin pages.
		wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-support-js', plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-support.js', array( 'jquery' ), BOTBLOCKER_VERSION, true );
		wp_localize_script(
			BOTBLOCKER_SHORT_NAME . '-support-js',
			'botblockerSupportData',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'botblocker_support_nonce' ),
				'i18n'    => array(
					'sending' => __( 'Sending...', 'botblocker-security' ),
					'send'    => __( 'Send', 'botblocker-security' ),
					'error'   => __( 'An error occurred. Please try again later.', 'botblocker-security' ),
				),
			)
		);

		// Shared: multipage.js (replaces Bootstrap + bbcs-common.js).
		wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-multipage-js', plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-multipage.js', array( 'jquery', BOTBLOCKER_SHORT_NAME . '-shared-helpers-js' ), BOTBLOCKER_VERSION, true );
		wp_localize_script(
			BOTBLOCKER_SHORT_NAME . '-multipage-js',
			'botblockerData',
			array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'adminUrl' => is_network_admin() ? network_admin_url( '' ) : admin_url( '' ),
				'nonce'   => wp_create_nonce( 'botblocker_nonce' ),
				'proActive' => ( class_exists( 'BotBlockerPro' ) && BotBlockerPro::isActive() ),
			)
		);
		wp_add_inline_script(
			BOTBLOCKER_SHORT_NAME . '-multipage-js',
			'var bbcsEarlyInitConfirm = ' . wp_json_encode( __( 'Disabling Early Init mode may reduce protection against advanced bots. Are you sure you want to proceed?', 'botblocker-security' ) ) . ';',
			'before'
		);
		wp_localize_script( BOTBLOCKER_SHORT_NAME . '-multipage-js', 'bbcsMultipageL10n', array(
			'protection_paused' => __( 'Protection paused', 'botblocker-security' ),
			'site_is_protected' => __( 'Site is protected', 'botblocker-security' ),
			'protection_active' => __( 'Protection active', 'botblocker-security' ),
			'enabled'           => __( 'Enabled', 'botblocker-security' ),
			'disabled'          => __( 'Disabled', 'botblocker-security' ),
			'pro_badge'         => 'PRO',
			'addon_badge'       => __( 'addon', 'botblocker-security' ),
			'actions'           => __( 'Actions', 'botblocker-security' ),
			'settings'          => __( 'Settings', 'botblocker-security' ),
			'sections'          => __( 'Sections', 'botblocker-security' ),
			'nothing_found'     => __( 'Nothing found for', 'botblocker-security' ),
			'add_rule'          => __( 'Add Rule', 'botblocker-security' ),
			'add_path'          => __( 'Add Path', 'botblocker-security' ),
			'add_bot'           => __( 'Add Bot', 'botblocker-security' ),
			'add_ipv4'          => __( 'Add IPv4', 'botblocker-security' ),
			'add_ipv6'          => __( 'Add IPv6', 'botblocker-security' ),
			'add_proxy'         => __( 'Add Proxy', 'botblocker-security' ),
			'add_asn'           => __( 'Add ASN', 'botblocker-security' ),
			'stat_active'       => __( 'Active', 'botblocker-security' ),
			'stat_disabled'     => __( 'Disabled', 'botblocker-security' ),
			'stat_attention'    => __( 'Attention', 'botblocker-security' ),
			'mu_loader_present' => __( 'present', 'botblocker-security' ),
			'mu_loader_missing' => __( 'missing', 'botblocker-security' ),
		) );

		// Inject translatable command palette and rules tab labels from PHP.
		// GROUPS are now auto-derived from BotBlockerSnav::getGlobalSearchIndex() inside BotBlockerPalette::getPaletteData().
		if ( class_exists( 'BotBlockerPalette' ) ) {
			$palette = BotBlockerPalette::getPaletteData();
			wp_add_inline_script(
				BOTBLOCKER_SHORT_NAME . '-multipage-js',
				'var BOTBLOCKER_PALETTE_ACTIONS = ' . wp_json_encode( $palette['actions'] ) . ';' .
				'var BOTBLOCKER_PALETTE_GROUPS = ' . wp_json_encode( $palette['groups'] ) . ';' .
				'var BOTBLOCKER_PALETTE_SECTIONS = ' . wp_json_encode( $palette['sections'] ) . ';' .
				'var BOTBLOCKER_ADD_LABELS = ' . wp_json_encode( $palette['addLabels'] ) . ';',
				'before'
			);
		}

		// Rules page - 9 JS modules + per-module l10n.
		if ( $this->is_screen( $screen->id, 'botblocker_page_bbcs_rules' ) ) {
			$rules_deps = array( 'jquery', BOTBLOCKER_SHORT_NAME . '-shared-helpers-js' );
			wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-rules-js', plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-rules.js', $rules_deps, BOTBLOCKER_VERSION, true );

			// Module scripts depend on rules-js (shared table helpers). Toast lives on global bbcsToast.
			$module_deps = array_merge( $rules_deps, array( BOTBLOCKER_SHORT_NAME . '-rules-js' ) );
			wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-rules-ipv4-js', plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-rules-ipv4.js', $module_deps, BOTBLOCKER_VERSION, true );
			wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-rules-ipv6-js', plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-rules-ipv6.js', $module_deps, BOTBLOCKER_VERSION, true );
			wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-rules-white-js', plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-white.js', $module_deps, BOTBLOCKER_VERSION, true );
			wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-rules-path-js', plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-path.js', $module_deps, BOTBLOCKER_VERSION, true );
			wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-proxy-js', plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-proxy.js', $module_deps, BOTBLOCKER_VERSION, true );
			wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-asn-js', plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-asn.js', $module_deps, BOTBLOCKER_VERSION, true );
			wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-geo-js', plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-geo.js', $module_deps, BOTBLOCKER_VERSION, true );
			wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-llm-js', plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-llm.js', $module_deps, BOTBLOCKER_VERSION, true );

			// Rules per-module l10n.
			$rules_l10n = array(
				'invalid_json'      => __( 'Invalid JSON file: ', 'botblocker-security' ),
				'failed_update'     => __( 'Failed to update rule: ', 'botblocker-security' ),
				'failed_load'       => __( 'Failed to load rule details: ', 'botblocker-security' ),
				'confirm_delete'    => __( 'Are you sure you want to delete this rule?', 'botblocker-security' ),
				'failed_create'     => __( 'Failed to create rule: ', 'botblocker-security' ),
				'failed_export'     => __( 'Failed to export rules: ', 'botblocker-security' ),
				'failed_import'     => __( 'Failed to import rules: ', 'botblocker-security' ),
				'failed_clear'      => __( 'Failed to clear rules: ', 'botblocker-security' ),
				'search_placeholder' => __( 'Search by data, comment…', 'botblocker-security' ),
				'import_result'     => __( 'Import Result', 'botblocker-security' ),
				'imported'          => __( 'Imported', 'botblocker-security' ),
				'skipped'           => __( 'Skipped', 'botblocker-security' ),
				'close'             => __( 'Close', 'botblocker-security' ),
				'clear_all_rules'   => __( 'Clear All Rules', 'botblocker-security' ),
				'confirm_clear'     => __( 'Are you sure you want to remove all rules?', 'botblocker-security' ),
				'yes'               => __( 'Yes', 'botblocker-security' ),
				'no'                => __( 'No', 'botblocker-security' ),
				'edit'              => __( 'Edit', 'botblocker-security' ),
				'delete'            => __( 'Delete', 'botblocker-security' ),
				'toggle'            => __( 'Toggle On/Off', 'botblocker-security' ),
				'success_toggle'    => __( 'Rule toggled successfully.', 'botblocker-security' ),
				'success_update'    => __( 'Rule updated successfully.', 'botblocker-security' ),
				'success_create'    => __( 'Rule created successfully.', 'botblocker-security' ),
				'success_delete'    => __( 'Rule deleted successfully.', 'botblocker-security' ),
				'success_export'    => __( 'Rules exported successfully.', 'botblocker-security' ),
				'success_import'    => __( 'Rules imported successfully.', 'botblocker-security' ),
				'success_clear'     => __( 'All rules have been cleared.', 'botblocker-security' ),
			);
			wp_localize_script( BOTBLOCKER_SHORT_NAME . '-rules-js', 'bbcsRulesL10n', $rules_l10n );

			$ipv4_l10n = $rules_l10n;
			$ipv4_l10n['confirm_delete'] = __( 'Are you sure you want to delete this IPv4 rule?', 'botblocker-security' );
			$ipv4_l10n['failed_create']  = __( 'Failed to create IPv4 rule: ', 'botblocker-security' );
			$ipv4_l10n['failed_update']  = __( 'Failed to update IPv4 rule: ', 'botblocker-security' );
			$ipv4_l10n['failed_load']    = __( 'Failed to load IPv4 rule details: ', 'botblocker-security' );
			$ipv4_l10n['failed_export']  = __( 'Failed to export IPv4 rules: ', 'botblocker-security' );
			$ipv4_l10n['failed_import']  = __( 'Failed to import IPv4 rules: ', 'botblocker-security' );
			$ipv4_l10n['failed_clear']   = __( 'Failed to clear IPv4 rules: ', 'botblocker-security' );
			$ipv4_l10n['failed_import_whitelist'] = __( 'Failed to import IPv4 whitelist: ', 'botblocker-security' );
			$ipv4_l10n['failed_import_blacklist'] = __( 'Failed to import IPv4 blacklist: ', 'botblocker-security' );
			$ipv4_l10n['success_import_whitelist'] = __( 'IPv4 whitelist imported successfully.', 'botblocker-security' );
			$ipv4_l10n['success_import_blacklist'] = __( 'IPv4 blacklist imported successfully.', 'botblocker-security' );
			$ipv4_l10n['clear_all_rules'] = __( 'Clear All IPv4 Rules', 'botblocker-security' );
			$ipv4_l10n['confirm_clear']  = __( 'Are you sure you want to remove all IPv4 rules?', 'botblocker-security' );
			wp_localize_script( BOTBLOCKER_SHORT_NAME . '-rules-ipv4-js', 'bbcsIpv4L10n', $ipv4_l10n );

			$ipv6_l10n = $rules_l10n;
			$ipv6_l10n['confirm_delete'] = __( 'Are you sure you want to delete this IPv6 rule?', 'botblocker-security' );
			$ipv6_l10n['failed_create']  = __( 'Failed to create IPv6 rule: ', 'botblocker-security' );
			$ipv6_l10n['failed_update']  = __( 'Failed to update IPv6 rule: ', 'botblocker-security' );
			$ipv6_l10n['failed_load']    = __( 'Failed to load IPv6 rule details: ', 'botblocker-security' );
			$ipv6_l10n['failed_export']  = __( 'Failed to export IPv6 rules: ', 'botblocker-security' );
			$ipv6_l10n['failed_import']  = __( 'Failed to import IPv6 rules: ', 'botblocker-security' );
			$ipv6_l10n['failed_clear']   = __( 'Failed to clear IPv6 rules: ', 'botblocker-security' );
			$ipv6_l10n['failed_import_whitelist'] = __( 'Failed to import IPv6 whitelist: ', 'botblocker-security' );
			$ipv6_l10n['failed_import_blacklist'] = __( 'Failed to import IPv6 blacklist: ', 'botblocker-security' );
			$ipv6_l10n['success_import_whitelist'] = __( 'IPv6 whitelist imported successfully.', 'botblocker-security' );
			$ipv6_l10n['success_import_blacklist'] = __( 'IPv6 blacklist imported successfully.', 'botblocker-security' );
			$ipv6_l10n['clear_all_rules'] = __( 'Clear All IPv6 Rules', 'botblocker-security' );
			$ipv6_l10n['confirm_clear']  = __( 'Are you sure you want to remove all IPv6 rules?', 'botblocker-security' );
			wp_localize_script( BOTBLOCKER_SHORT_NAME . '-rules-ipv6-js', 'bbcsIpv6L10n', $ipv6_l10n );

			$white_l10n = $rules_l10n;
			$white_l10n['confirm_delete'] = __( 'Are you sure you want to delete this white bot?', 'botblocker-security' );
			$white_l10n['failed_create']  = __( 'Failed to create white bot: ', 'botblocker-security' );
			$white_l10n['failed_update']  = __( 'Failed to update white bot: ', 'botblocker-security' );
			$white_l10n['failed_load']    = __( 'Failed to load white bot details: ', 'botblocker-security' );
			$white_l10n['failed_export']  = __( 'Failed to export white bots: ', 'botblocker-security' );
			$white_l10n['failed_import']  = __( 'Failed to import white bots: ', 'botblocker-security' );
			$white_l10n['failed_clear']   = __( 'Failed to clear white bots: ', 'botblocker-security' );
			$white_l10n['clear_all_rules'] = __( 'Clear All White Bots', 'botblocker-security' );
			$white_l10n['confirm_clear']  = __( 'Are you sure you want to remove all white bots?', 'botblocker-security' );
			wp_localize_script( BOTBLOCKER_SHORT_NAME . '-rules-white-js', 'bbcsWhiteL10n', $white_l10n );

			$path_l10n = $rules_l10n;
			$path_l10n['confirm_delete'] = __( 'Are you sure you want to delete this path?', 'botblocker-security' );
			$path_l10n['failed_create']  = __( 'Failed to create path: ', 'botblocker-security' );
			$path_l10n['failed_update']  = __( 'Failed to update path: ', 'botblocker-security' );
			$path_l10n['failed_load']    = __( 'Failed to load path details: ', 'botblocker-security' );
			$path_l10n['failed_export']  = __( 'Failed to export paths: ', 'botblocker-security' );
			$path_l10n['failed_import']  = __( 'Failed to import paths: ', 'botblocker-security' );
			$path_l10n['failed_clear']   = __( 'Failed to clear paths: ', 'botblocker-security' );
			$path_l10n['clear_all_rules'] = __( 'Clear All Paths', 'botblocker-security' );
			$path_l10n['confirm_clear']  = __( 'Are you sure you want to remove all paths?', 'botblocker-security' );
			wp_localize_script( BOTBLOCKER_SHORT_NAME . '-rules-path-js', 'bbcsPathL10n', $path_l10n );

			wp_localize_script( BOTBLOCKER_SHORT_NAME . '-proxy-js', 'bbcsProxyL10n', array(
				'invalid_json'          => __( 'Invalid JSON file: ', 'botblocker-security' ),
				'failed_update'         => __( 'Failed to update proxy: ', 'botblocker-security' ),
				'failed_load'           => __( 'Failed to load proxy details: ', 'botblocker-security' ),
				'confirm_delete'        => __( 'Are you sure you want to delete this proxy?', 'botblocker-security' ),
				'failed_create'         => __( 'Failed to create proxy: ', 'botblocker-security' ),
				'failed_export'         => __( 'Failed to export proxies: ', 'botblocker-security' ),
				'failed_import'         => __( 'Failed to import proxies: ', 'botblocker-security' ),
				'failed_clear'          => __( 'Failed to clear proxies: ', 'botblocker-security' ),
				'proxies_updated'       => __( 'Proxies successfully updated for Early BotBlocker Mode', 'botblocker-security' ),
				'failed_update_proxies' => __( 'Failed to update proxies: ', 'botblocker-security' ),
				'clear_all_rules'       => __( 'Clear All Proxies', 'botblocker-security' ),
				'confirm_clear'         => __( 'Are you sure you want to remove all proxies?', 'botblocker-security' ),
				'import_result'         => __( 'Import Result', 'botblocker-security' ),
				'imported'              => __( 'Imported', 'botblocker-security' ),
				'skipped'               => __( 'Skipped', 'botblocker-security' ),
				'close'                 => __( 'Close', 'botblocker-security' ),
				'yes'                   => __( 'Yes', 'botblocker-security' ),
				'no'                    => __( 'No', 'botblocker-security' ),
				'edit'                  => __( 'Edit', 'botblocker-security' ),
				'delete'                => __( 'Delete', 'botblocker-security' ),
				'search_placeholder'    => __( 'Search by data, comment…', 'botblocker-security' ),
				'success_update'        => __( 'Proxy updated successfully.', 'botblocker-security' ),
				'success_create'        => __( 'Proxy created successfully.', 'botblocker-security' ),
				'success_delete'        => __( 'Proxy deleted successfully.', 'botblocker-security' ),
				'success_export'        => __( 'Proxies exported successfully.', 'botblocker-security' ),
				'success_import'        => __( 'Proxies imported successfully.', 'botblocker-security' ),
				'success_clear'         => __( 'All proxies have been cleared.', 'botblocker-security' ),
			) );

			$asn_l10n = $rules_l10n;
			$asn_l10n['confirm_delete'] = __( 'Are you sure you want to delete this ASN rule?', 'botblocker-security' );
			$asn_l10n['failed_create']  = __( 'Failed to create ASN rule: ', 'botblocker-security' );
			$asn_l10n['failed_update']  = __( 'Failed to update ASN rule: ', 'botblocker-security' );
			$asn_l10n['failed_load']    = __( 'Failed to load ASN details: ', 'botblocker-security' );
			$asn_l10n['failed_export']  = __( 'Failed to export ASN rules: ', 'botblocker-security' );
			$asn_l10n['failed_import']  = __( 'Failed to import ASN rules: ', 'botblocker-security' );
			$asn_l10n['failed_clear']   = __( 'Failed to clear ASN rules: ', 'botblocker-security' );
			$asn_l10n['clear_all_rules'] = __( 'Clear All ASN Rules', 'botblocker-security' );
			$asn_l10n['confirm_clear']  = __( 'Are you sure you want to remove all ASN rules?', 'botblocker-security' );
			wp_localize_script( BOTBLOCKER_SHORT_NAME . '-asn-js', 'bbcsAsnL10n', $asn_l10n );

			wp_localize_script( BOTBLOCKER_SHORT_NAME . '-geo-js', 'botblockerGeoData', array(
				'ajaxurl' => admin_url( 'admin-ajax.php' ),
				'nonce'   => wp_create_nonce( 'botblocker_nonce' ),
				'i18n'    => array(
					'please_select_country'              => __( 'Please select a country.', 'botblocker-security' ),
					'delete'                             => __( 'Delete', 'botblocker-security' ),
					'toggle'                             => __( 'Toggle On/Off', 'botblocker-security' ),
					'confirm_delete'                     => __( 'Are you sure you want to delete this country rule?', 'botblocker-security' ),
					'confirm_clear'                      => __( 'Are you sure you want to remove all country rules?', 'botblocker-security' ),
					'failed_toggle'                      => __( 'Failed to toggle country rule.', 'botblocker-security' ),
					'failed_delete'                      => __( 'Failed to delete country rule.', 'botblocker-security' ),
					'failed_create'                      => __( 'Failed to add country.', 'botblocker-security' ),
					'failed_clear'                       => __( 'Failed to clear countries.', 'botblocker-security' ),
					'search_placeholder'                 => __( 'Search by code, rule, comment…', 'botblocker-security' ),
					'success_toggle'                     => __( 'Country rule toggled successfully.', 'botblocker-security' ),
					'success_delete'                     => __( 'Country rule deleted successfully.', 'botblocker-security' ),
					'success_create'                     => __( 'Country added successfully.', 'botblocker-security' ),
					'success_clear'                      => __( 'All country rules have been cleared.', 'botblocker-security' ),
				),
			) );

			wp_localize_script( BOTBLOCKER_SHORT_NAME . '-llm-js', 'bbcsLLML10n', array(
				'failed_update'      => __( 'Failed to update: ', 'botblocker-security' ),
				'failed_load'        => __( 'Failed to load: ', 'botblocker-security' ),
				'edit'               => __( 'Edit', 'botblocker-security' ),
				'enable'             => __( 'Enable', 'botblocker-security' ),
				'disable'            => __( 'Disable', 'botblocker-security' ),
				'enabled'            => __( 'Enabled', 'botblocker-security' ),
				'disabled'           => __( 'Disabled', 'botblocker-security' ),
				'no_providers'       => __( 'No LLM providers found', 'botblocker-security' ),
				'sync_cloud'         => __( 'Sync from Cloud', 'botblocker-security' ),
				'syncing'            => __( 'Syncing...', 'botblocker-security' ),
				'sync_scheduled'     => __( 'Sync scheduled.', 'botblocker-security' ),
				'sync_failed'        => __( 'Sync failed: ', 'botblocker-security' ),
				'download_json'      => __( 'Download as JSON', 'botblocker-security' ),
				'to_php'             => __( 'Save LLM rules to PHP file', 'botblocker-security' ),
				'search_placeholder' => __( 'Search by data, comment…', 'botblocker-security' ),
				'length_menu'        => __( 'Length Menu', 'botblocker-security' ),
				'success_toggle'     => __( 'Provider toggled successfully.', 'botblocker-security' ),
				'success_download'   => __( 'Rules downloaded successfully.', 'botblocker-security' ),
			) );
		}

		// Reports page.
		if ( $this->is_screen( $screen->id, 'botblocker_page_bbcs_reports' ) ) {
			wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-hits-js', plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-hits.js', array( 'jquery', BOTBLOCKER_SHORT_NAME . '-toast-js' ), BOTBLOCKER_VERSION, true );
			wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-audit-js', plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-audit.js', array( 'jquery' ), BOTBLOCKER_VERSION, true );
			wp_localize_script( BOTBLOCKER_SHORT_NAME . '-audit-js', 'bbcsAuditL10n', array(
				'details'           => __( 'Details', 'botblocker-security' ),
				'searchPlaceholder' => __( 'Search audit log…', 'botblocker-security' ),
				'close'             => __( 'Close', 'botblocker-security' ),
				'time'              => __( 'Time', 'botblocker-security' ),
				'event'             => __( 'Event', 'botblocker-security' ),
				'message'           => __( 'Message', 'botblocker-security' ),
				'severity'          => __( 'Severity', 'botblocker-security' ),
				'actor'             => __( 'Actor', 'botblocker-security' ),
				'role'              => __( 'Role', 'botblocker-security' ),
				'objectType'        => __( 'Object type', 'botblocker-security' ),
				'objectId'          => __( 'Object ID', 'botblocker-security' ),
				'ip'                => __( 'IP', 'botblocker-security' ),
				'context'           => __( 'Context', 'botblocker-security' ),
				'path'              => __( 'Path', 'botblocker-security' ),
				'userAgent'         => __( 'User agent', 'botblocker-security' ),
				'data'              => __( 'Data', 'botblocker-security' ),
			) );
			wp_localize_script( BOTBLOCKER_SHORT_NAME . '-hits-js', 'bbcsHitsL10n', array(
				'failed_create_rule' => __( 'Failed to create rule: ', 'botblocker-security' ),
				'search_placeholder' => __( 'Search by data, comment…', 'botblocker-security' ),
				'add_rule'           => __( 'Add Rule', 'botblocker-security' ),
				'page_label'         => __( 'Page:', 'botblocker-security' ),
				'referer_label'      => __( 'Referer:', 'botblocker-security' ),
				'display'            => __( 'Display', 'botblocker-security' ),
				'web'                => __( 'Web', 'botblocker-security' ),
				'adblocker'          => __( 'Adblocker:', 'botblocker-security' ),
				'cid'                => __( 'CID:', 'botblocker-security' ),
				'length_menu'        => __( 'Length Menu', 'botblocker-security' ),
				'import_result'      => __( 'Import Result', 'botblocker-security' ),
				'imported'           => __( 'Imported', 'botblocker-security' ),
				'skipped'            => __( 'Skipped', 'botblocker-security' ),
				'close'              => __( 'Close', 'botblocker-security' ),
				'yes'                => __( 'Yes', 'botblocker-security' ),
				'no'                 => __( 'No', 'botblocker-security' ),
			) );
		}

		// Integrations page.
		if ( $this->is_screen( $screen->id, 'botblocker_page_bbcs_integrations' ) ) {
			wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-integrations-js', plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-integrations.js', array( 'jquery' ), BOTBLOCKER_VERSION, true );
			wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-2fa-js', plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-2fa.js', array( 'jquery', BOTBLOCKER_SHORT_NAME . '-shared-helpers-js' ), BOTBLOCKER_VERSION, true );
			wp_localize_script( BOTBLOCKER_SHORT_NAME . '-2fa-js', 'bbcs2faL10n', array(
				'reset_failed' => __( 'Reset failed', 'botblocker-security' ),
				'invalid_code' => __( 'Invalid verification code.', 'botblocker-security' ),
				'verify_failed' => __( 'Verification failed.', 'botblocker-security' ),
				'enabled' => __( 'Two-Factor Authentication enabled.', 'botblocker-security' ),
				'reset_success' => __( 'Two-Factor Authentication has been reset.', 'botblocker-security' ),
				'invalid_format' => __( 'Please enter a valid 6-digit code.', 'botblocker-security' ),
				'connection_error' => __( 'Connection error. Please try again.', 'botblocker-security' ),
			) );
			// Cloud API (BotBlocker API tab) controls live on this page too.
			wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-cloud-api-js', plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-cloud-api.js', array( 'jquery', BOTBLOCKER_SHORT_NAME . '-toast-js' ), BOTBLOCKER_VERSION, true );
			wp_localize_script( BOTBLOCKER_SHORT_NAME . '-cloud-api-js', 'botblockerData', array(
				'adminUrl' => is_network_admin() ? network_admin_url( '' ) : admin_url( '' ),
				'ajaxurl'  => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'botblocker_nonce' ),
			) );
			wp_localize_script( BOTBLOCKER_SHORT_NAME . '-cloud-api-js', 'bbcsCloudApiL10n', array(
				'refreshed'      => __( 'BotBlocker PRO information refreshed successfully!', 'botblocker-security' ),
				'failed_refresh' => __( 'Failed to refresh BotBlocker PRO information.', 'botblocker-security' ),
				'ajax_error'     => __( 'AJAX Error: ', 'botblocker-security' ),
			) );
		}

		// Settings page.
		if ( $this->is_screen( $screen->id, 'botblocker_page_bbcs_settings' ) ) {
			wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-settings-js', plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-settings.js', array( 'jquery', BOTBLOCKER_SHORT_NAME . '-toast-js' ), BOTBLOCKER_VERSION, true );
			wp_add_inline_script( BOTBLOCKER_SHORT_NAME . '-settings-js', 'var bbcsUnsavedLabel = ' . wp_json_encode( __( 'Not saved!', 'botblocker-security' ) ) . ';', 'before' );
			$bbcs_tls_sync_saved = ( BotBlocker::getInstance()->settings->tls_fingerprint_check == 1 );
			wp_localize_script( BOTBLOCKER_SHORT_NAME . '-settings-js', 'bbcsTlsL10n', array(
				'invalid_json'   => __( 'Invalid JSON file: ', 'botblocker-security' ),
				'failed_import'  => __( 'Failed to import TLS fingerprints: ', 'botblocker-security' ),
				'confirm_ask'    => __( 'Are you sure you want to clear all TLS fingerprints?', 'botblocker-security' ),
				'failed_clear'   => __( 'Failed to clear TLS fingerprints: ', 'botblocker-security' ),
				'import_success' => __( 'Successfully imported', 'botblocker-security' ),
				'imported'       => __( 'imported', 'botblocker-security' ),
				'skipped'        => __( 'skipped', 'botblocker-security' ),
				'cleared'        => __( 'All TLS fingerprints cleared.', 'botblocker-security' ),
				'sync_now'       => __( 'Sync Now', 'botblocker-security' ),
				'sync_success'   => __( 'TLS fingerprints synced successfully.', 'botblocker-security' ),
				'syncing_process' => __( 'Syncing...', 'botblocker-security' ),
				'failed_sync'    => __( 'Failed to sync TLS fingerprints: ', 'botblocker-security' ),
				'sync_requires_save' => __( 'Enable TLS Fingerprint Check and save settings before syncing.', 'botblocker-security' ),
				'tls_sync_saved' => $bbcs_tls_sync_saved ? '1' : '0',
			) );
		}

		// Tools page.
		if ( $this->is_screen( $screen->id, 'botblocker_page_bbcs_tools' ) ) {
			wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-tools-js', plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-tools.js', array( 'jquery' ), BOTBLOCKER_VERSION, true );
			wp_add_inline_script( BOTBLOCKER_SHORT_NAME . '-tools-js', 'var bbcsUnsavedLabel = ' . wp_json_encode( __( 'Not saved!', 'botblocker-security' ) ) . ';', 'before' );
			wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-maintenance-js', plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-maintenance.js', array( 'jquery', BOTBLOCKER_SHORT_NAME . '-toast-js' ), BOTBLOCKER_VERSION, true );
			wp_localize_script( BOTBLOCKER_SHORT_NAME . '-maintenance-js', 'botblockerData', array(
				'adminUrl' => is_network_admin() ? network_admin_url( '' ) : admin_url( '' ),
				'ajaxurl'  => admin_url( 'admin-ajax.php' ),
				'nonce'    => wp_create_nonce( 'botblocker_nonce' ),
			) );
			wp_localize_script( BOTBLOCKER_SHORT_NAME . '-maintenance-js', 'bbcsMaintenanceL10n', array(
				'db_reinstalled'          => __( 'Database reinstalled successfully!', 'botblocker-security' ),
				'failed_reinstall'        => __( 'Failed to reinstall database: ', 'botblocker-security' ),
				'ajax_error'              => __( 'AJAX Error: ', 'botblocker-security' ),
				'failed_backup'           => __( 'Failed backup: ', 'botblocker-security' ),
				'import_success'          => __( 'Import data and settings was successful!', 'botblocker-security' ),
				'failed_import'           => __( 'Failed import: ', 'botblocker-security' ),
				'salt_created'            => __( 'Salt successfully created!', 'botblocker-security' ),
				'failed_salt'             => __( 'Failed to create salt file.', 'botblocker-security' ),
				'operation_error'         => __( 'An error occurred while performing the operation.', 'botblocker-security' ),
				'log_cleared'             => __( 'Log file successfully cleared!', 'botblocker-security' ),
				'failed_clear_log'        => __( 'Failed to clear log file.', 'botblocker-security' ),
				'failed_get_log'          => __( 'Failed to get log file.', 'botblocker-security' ),
				'transients_cleared'      => __( 'Transients successfully cleared!', 'botblocker-security' ),
				'failed_clear_transients' => __( 'Failed to clear transients.', 'botblocker-security' ),
				'visitors_cleared'        => __( 'Visitors data successfully cleared!', 'botblocker-security' ),
				'failed_clear_visitors'   => __( 'Failed to clear visitors data.', 'botblocker-security' ),
				'rewrite_flushed'         => __( 'Rewrite rules successfully flushed!', 'botblocker-security' ),
				'failed_flush_rewrite'    => __( 'Failed to flush rewrite rules.', 'botblocker-security' ),
				'cache_cleared'           => __( 'Object cache successfully cleared!', 'botblocker-security' ),
				'failed_clear_cache'      => __( 'Failed to clear object cache.', 'botblocker-security' ),
				'asn_scheduled'           => __( 'ASN database update scheduled.', 'botblocker-security' ),
				'failed_schedule_asn'     => __( 'Failed to schedule ASN database update.', 'botblocker-security' ),
				'asn_error'               => __( 'An error occurred while scheduling the ASN database update.', 'botblocker-security' ),
			) );
		}

		// Cloud API page.
		if ( $this->is_screen( $screen->id, 'botblocker_page_bbcs_cloud_api' ) ) {
			wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-cloud-api-js', plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-cloud-api.js', array( 'jquery', BOTBLOCKER_SHORT_NAME . '-toast-js' ), BOTBLOCKER_VERSION, true );
			wp_localize_script( BOTBLOCKER_SHORT_NAME . '-cloud-api-js', 'bbcsCloudApiL10n', array(
				'refreshed'      => __( 'BotBlocker PRO information refreshed successfully!', 'botblocker-security' ),
				'failed_refresh' => __( 'Failed to refresh BotBlocker PRO information.', 'botblocker-security' ),
				'ajax_error'     => __( 'AJAX Error: ', 'botblocker-security' ),
			) );
		}

		// Add-ons page.
		if ( $this->is_screen( $screen->id, 'botblocker_page_bbcs_addons' ) ) {
			wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-addons-js', plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-addons.js', array( 'jquery' ), BOTBLOCKER_VERSION, true );
			wp_localize_script( BOTBLOCKER_SHORT_NAME . '-addons-js', 'bbcsAddonsL10n', array(
				'installed'          => __( 'Add-on installed successfully.', 'botblocker-security' ),
				'uploaded'           => __( 'Add-on package uploaded. Find it below, then activate it when ready.', 'botblocker-security' ),
				'updated_all'        => __( 'All add-ons have been updated.', 'botblocker-security' ),
				'updated'            => __( 'Add-on updated successfully.', 'botblocker-security' ),
				/* translators: %s: required BotBlocker version */
				'requires_core_msg'  => __( 'Add-on was updated but not reactivated - it requires BotBlocker version %s or higher. Please update the plugin.', 'botblocker-security' ),
				'deleted'            => __( 'Add-on deleted.', 'botblocker-security' ),
				'operation_failed'   => __( 'Operation failed.', 'botblocker-security' ),
				'invalid'            => __( 'The add-on is invalid or broken.', 'botblocker-security' ),
				'install_args'       => __( 'Installation arguments are missing.', 'botblocker-security' ),
				'download'           => __( 'Failed to download the add-on package.', 'botblocker-security' ),
				'unzip'              => __( 'Failed to unpack the add-on package.', 'botblocker-security' ),
				'fs_unavailable'     => __( 'Filesystem API is not available.', 'botblocker-security' ),
				'url_not_allowed'    => __( 'The add-on download URL is not allowed.', 'botblocker-security' ),
				'upload_missing'     => __( 'Choose an add-on ZIP package first.', 'botblocker-security' ),
				'upload_failed'      => __( 'The add-on upload failed.', 'botblocker-security' ),
				'upload_untrusted'   => __( 'The uploaded file was not accepted by WordPress.', 'botblocker-security' ),
				'zip_missing'        => __( 'Add-on package is missing or unreadable.', 'botblocker-security' ),
				'zip_extension'      => __( 'The add-on package must be a ZIP file.', 'botblocker-security' ),
				'zip_too_large'      => __( 'The add-on package is too large.', 'botblocker-security' ),
				'zip_open'           => __( 'The add-on package cannot be opened.', 'botblocker-security' ),
				'zip_file_count'     => __( 'The add-on package has an invalid file count.', 'botblocker-security' ),
				'zip_unsafe_path'    => __( 'The package contains an unsafe file path.', 'botblocker-security' ),
				'zip_entry_too_large' => __( 'The package contains an oversized file.', 'botblocker-security' ),
				'extract_missing'    => __( 'The temporary extraction folder is missing.', 'botblocker-security' ),
				'package_root'       => __( 'The package must contain exactly one root folder.', 'botblocker-security' ),
				'package_slug'       => __( 'The package root folder must be a valid slug.', 'botblocker-security' ),
				'package_invalid'    => __( 'The package does not match the BotBlocker add-on contract.', 'botblocker-security' ),
				'pro_required'       => __( 'Official BotBlocker add-ons require BotBlocker PRO. Custom ZIP add-ons can still be uploaded.', 'botblocker-security' ),
				'requires_core_missing' => __( 'The package must declare Requires-Core.', 'botblocker-security' ),
				'slug_mismatch'      => __( 'The package slug does not match the requested add-on.', 'botblocker-security' ),
				'requires_php'       => __( 'This add-on requires a newer PHP version.', 'botblocker-security' ),
				'file_mods_disabled' => __( 'File modifications are disabled for this WordPress installation.', 'botblocker-security' ),
				'tmp_failed'         => __( 'Failed to create a temporary add-on folder.', 'botblocker-security' ),
				'move_source'        => __( 'The validated add-on source is missing.', 'botblocker-security' ),
				'backup_failed'      => __( 'Failed to backup the existing add-on.', 'botblocker-security' ),
				'move_failed'        => __( 'Failed to install the add-on package.', 'botblocker-security' ),
				'requires_core_newer' => __( 'This add-on requires a newer version of BotBlocker.', 'botblocker-security' ),
				'required_version'   => __( 'Required:', 'botblocker-security' ),
				'update_plugin'      => __( 'Please update the plugin first.', 'botblocker-security' ),
				'no_file_selected'   => __( 'No file selected', 'botblocker-security' ),
				'marketplace'        => __( 'Marketplace', 'botblocker-security' ),
				/* translators: %d: number of available addon updates */
				'update_all'         => __( 'Update All (%d)', 'botblocker-security' ),
				'update_tag'         => __( 'Update', 'botblocker-security' ),
				'catalog_error'      => __( 'Failed to load add-on catalog.', 'botblocker-security' ),
				'catalog_unavailable' => __( 'Add-on catalog is currently unavailable.', 'botblocker-security' ),
			) );
			wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-addons-settings-js', plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-addons-settings.js', array(), BOTBLOCKER_VERSION, true );
			wp_add_inline_script( BOTBLOCKER_SHORT_NAME . '-addons-settings-js', 'var bbcsUnsavedLabel = ' . wp_json_encode( __( 'Not saved!', 'botblocker-security' ) ) . ';', 'before' );

		}

		// Setup Guide (Health Status) page.
		if ( $this->is_screen( $screen->id, 'botblocker_page_bbcs_setup_guide' ) ) {
			wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-health-gauge-js', plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-health-gauge.js', array( 'jquery', BOTBLOCKER_SHORT_NAME . '-chartjs' ), BOTBLOCKER_VERSION, true );
			wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-setup-js', plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-setup.js', array( 'jquery', BOTBLOCKER_SHORT_NAME . '-toast-js' ), BOTBLOCKER_VERSION, true );
			wp_localize_script( BOTBLOCKER_SHORT_NAME . '-setup-js', 'bbcsSetupL10n', array(
				'pro_required'   => __( 'Full Protection requires PRO license. Please upgrade to PRO first.', 'botblocker-security' ),
				'error_apply'    => __( 'Error applying profile', 'botblocker-security' ),
				'request_failed' => __( 'Request failed. Please try again.', 'botblocker-security' ),
				'please_wait'    => __( 'Please wait...', 'botblocker-security' ),
				'apply_now'      => __( 'Apply Now', 'botblocker-security' ),
			) );
		}

	}

	public function add_admin_menu(): void {
		$cap = BotBlockerMultisite::canManage();

		$menu_title = 'BotBlocker';
		if ( class_exists( 'BotBlockerAddonsMarket' ) ) {
			$menu_title .= BotBlockerAddonsMarket::menuBubbleHtml();
		}

		add_menu_page(
			'BotBlocker',
			$menu_title,
			$cap,
			'bbcs_dashboard',
			array( $this, 'dashboard_page' ),
			'dashicons-shield-alt',
			6
		);

		// 1) Dashboard
		add_submenu_page(
			'bbcs_dashboard',
			__( 'Dashboard', 'botblocker-security' ),
			__( 'Dashboard', 'botblocker-security' ),
			$cap,
			'bbcs_dashboard',
			array( $this, 'dashboard_page' )
		);

		// 2) Health Status
		add_submenu_page(
			'bbcs_dashboard',
			__( 'Health Status', 'botblocker-security' ),
			__( 'Health Status', 'botblocker-security' ),
			$cap,
			'bbcs_setup_guide',
			array( $this, 'setup_guide_page' )
		);

		// 3) Settings
		add_submenu_page(
			'bbcs_dashboard',
			__( 'Settings', 'botblocker-security' ),
			__( 'Settings', 'botblocker-security' ),
			$cap,
			'bbcs_settings',
			array( $this, 'settings_page' )
		);

		// 4) Rules
		add_submenu_page(
			'bbcs_dashboard',
			__( 'Rules', 'botblocker-security' ),
			__( 'Rules', 'botblocker-security' ),
			$cap,
			'bbcs_rules',
			array( $this, 'rules_page' )
		);

		// 5) Integrations
		add_submenu_page(
			'bbcs_dashboard',
			__( 'Integrations', 'botblocker-security' ),
			__( 'Integrations', 'botblocker-security' ),
			$cap,
			'bbcs_integrations',
			array( $this, 'integrations_page' )
		);

		// 6) Tools
		add_submenu_page(
			'bbcs_dashboard',
			__( 'Tools', 'botblocker-security' ),
			__( 'Tools', 'botblocker-security' ),
			$cap,
			'bbcs_tools',
			array( $this, 'tools_page' )
		);

		// 7) Reports
		add_submenu_page(
			'bbcs_dashboard',
			__( 'Reports', 'botblocker-security' ),
			__( 'Reports', 'botblocker-security' ),
			$cap,
			'bbcs_reports',
			array( $this, 'reports_page' )
		);

		// 8) Add-ons
		add_submenu_page(
			'bbcs_dashboard',
			__( 'Add-ons', 'botblocker-security' ),
			__( 'Add-ons', 'botblocker-security' ),
			$cap,
			'bbcs_addons',
			array( $this, 'addons_page' )
		);

		// 9) PRO
		add_submenu_page(
			'bbcs_dashboard',
			__( 'PRO', 'botblocker-security' ),
			__( 'PRO', 'botblocker-security' ),
			$cap,
			'bbcs_cloud_api',
			array( $this, 'cloud_api_page' )
		);

		// 10) About
		add_submenu_page(
			'bbcs_dashboard',
			__( 'About', 'botblocker-security' ),
			__( 'About', 'botblocker-security' ),
			$cap,
			'bbcs_about',
			array( $this, 'about_page' )
		);

		add_submenu_page(
			null,
			__( 'Setup Wizard', 'botblocker-security' ),
			__( 'Setup Wizard', 'botblocker-security' ),
			$cap,
			'bbcs_setup_wizard',
			'__return_false'
		);

		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
	}

	public function dashboard_page(): void {
		global $BBCS;
		$BBCS = BotBlocker::getInstance();
		require_once BOTBLOCKER_DIR . 'includes/viewmodels/class-dashboard-viewmodel.php';
		require_once BOTBLOCKER_DIR . 'includes/components/component-loader.php';
		require_once BOTBLOCKER_DIR . 'admin/views/class-dashboard-view.php';
		require_once BOTBLOCKER_DIR . 'admin/views/class-layout-view.php';
		BotBlockerHealthShortcodes::collectStatisticData();
		if ( isset( $BBCS->settings->cache_ui_data ) && $BBCS->settings->cache_ui_data == false ) {
			BotBlockerCache::clearTransients();
		}
		$vm     = new Botblocker_DashboardViewModel();
		$view   = new Botblocker_Dashboard_View( $vm );
		$layout = new Botblocker_Layout_View( $vm );
		$this->render_page( 'dashboard', $view, $layout );
		do_action( 'bbcs_show_support_button' );
	}

	public function settings_page(): void {
		require_once BOTBLOCKER_DIR . 'includes/viewmodels/class-settings-viewmodel.php';
		require_once BOTBLOCKER_DIR . 'includes/components/component-loader.php';
		require_once BOTBLOCKER_DIR . 'admin/views/class-settings-view.php';
		require_once BOTBLOCKER_DIR . 'admin/views/class-layout-view.php';

		$vm     = new Botblocker_SettingsViewModel();
		$view   = new Botblocker_Settings_View( $vm );
		$layout = new Botblocker_Layout_View( $vm );

		$this->render_page( 'settings', $view, $layout );
	}

	public function reports_page(): void {
		require_once BOTBLOCKER_DIR . 'includes/viewmodels/class-reports-viewmodel.php';
		require_once BOTBLOCKER_DIR . 'includes/components/component-loader.php';
		require_once BOTBLOCKER_DIR . 'admin/views/class-reports-view.php';
		require_once BOTBLOCKER_DIR . 'admin/views/class-layout-view.php';
		BotBlockerHealthShortcodes::collectStatisticData();

		$vm     = new Botblocker_ReportsViewModel();
		$view   = new Botblocker_Reports_View( $vm );
		$layout = new Botblocker_Layout_View( $vm );

		$this->render_page( 'reports', $view, $layout );
		do_action( 'bbcs_show_support_button' );
	}

	public function rules_page(): void {
		require_once BOTBLOCKER_DIR . 'includes/viewmodels/class-rules-viewmodel.php';
		require_once BOTBLOCKER_DIR . 'includes/components/component-loader.php';
		require_once BOTBLOCKER_DIR . 'admin/views/class-rules-view.php';
		require_once BOTBLOCKER_DIR . 'admin/views/class-layout-view.php';

		$vm     = new Botblocker_RulesViewModel();
		$view   = new Botblocker_Rules_View( $vm );
		$layout = new Botblocker_Layout_View( $vm );

		$this->render_page( 'rules', $view, $layout );
		do_action( 'bbcs_show_support_button' );
	}

	public function tools_page(): void {
		require_once BOTBLOCKER_DIR . 'includes/viewmodels/class-tools-viewmodel.php';
		require_once BOTBLOCKER_DIR . 'includes/components/component-loader.php';
		require_once BOTBLOCKER_DIR . 'admin/views/class-tools-view.php';
		require_once BOTBLOCKER_DIR . 'admin/views/class-layout-view.php';

		$vm     = new Botblocker_ToolsViewModel();
		$view   = new Botblocker_Tools_View( $vm );
		$layout = new Botblocker_Layout_View( $vm );

		$this->render_page( 'tools', $view, $layout );
		do_action( 'bbcs_show_support_button' );
	}

	public function integrations_page(): void {
		require_once BOTBLOCKER_DIR . 'includes/viewmodels/class-integrations-viewmodel.php';
		require_once BOTBLOCKER_DIR . 'includes/components/component-loader.php';
		require_once BOTBLOCKER_DIR . 'admin/views/class-integrations-view.php';
		require_once BOTBLOCKER_DIR . 'admin/views/class-layout-view.php';

		BotBlockerUI::enforce_recaptcha_v3_dependencies();

		$user_id = get_current_user_id();

		$bbcs_secret = get_user_meta( $user_id, '_2fa_secret_temp', true );
		if ( empty( $bbcs_secret ) ) {
			$bbcs_new_secret = BotBlockerTwoFactorAuth::instance()->createSecret();
			add_user_meta( $user_id, '_2fa_secret_temp', $bbcs_new_secret, true );
		}

		$bbcs_backup_codes = get_user_meta( $user_id, '_2fa_backup_codes_temp', true );
		if ( empty( $bbcs_backup_codes ) ) {
			$bbcs_new_codes = BotBlockerTwoFactorAuth::generateBackupCodes();
			add_user_meta( $user_id, '_2fa_backup_codes_temp', $bbcs_new_codes, true );
		}

		$vm     = new Botblocker_IntegrationsViewModel();
		$view   = new Botblocker_Integrations_View( $vm );
		$layout = new Botblocker_Layout_View( $vm );

		$this->render_page( 'integrations', $view, $layout );
		do_action( 'bbcs_show_support_button' );
	}

	public function cloud_api_page(): void {
		require_once BOTBLOCKER_DIR . 'includes/viewmodels/class-cloud-api-viewmodel.php';
		require_once BOTBLOCKER_DIR . 'includes/components/component-loader.php';
		require_once BOTBLOCKER_DIR . 'admin/views/class-cloud-api-view.php';
		require_once BOTBLOCKER_DIR . 'admin/views/class-layout-view.php';

		$vm     = new Botblocker_CloudApiViewModel();
		$vm->connect_nonce    = wp_create_nonce( 'bbcs_connect_cloud_api_action' );
		$vm->deactivate_nonce = wp_create_nonce( 'bbcs_deactivate_cloud_api_action' );
		$vm->fetch_key_nonce  = wp_create_nonce( 'bbcs_fetch_cloud_api_key_action' );
		$view   = new Botblocker_CloudApi_View( $vm );
		$layout = new Botblocker_Layout_View( $vm );

		$this->render_page( 'cloud-api', $view, $layout );
		do_action( 'bbcs_show_support_button' );
	}

	public function addons_page(): void {
		require_once BOTBLOCKER_DIR . 'includes/viewmodels/class-addons-viewmodel.php';
		require_once BOTBLOCKER_DIR . 'includes/components/component-loader.php';
		require_once BOTBLOCKER_DIR . 'admin/views/class-addons-view.php';
		require_once BOTBLOCKER_DIR . 'admin/views/class-layout-view.php';

		$vm     = new Botblocker_AddonsViewModel();
		$view   = new Botblocker_Addons_View( $vm );
		$layout = new Botblocker_Layout_View( $vm );

		$this->render_page( 'addons', $view, $layout );
		do_action( 'bbcs_show_support_button' );
	}

	public function setup_guide_page(): void {
		require_once BOTBLOCKER_DIR . 'includes/viewmodels/class-setup-guide-viewmodel.php';
		require_once BOTBLOCKER_DIR . 'includes/components/component-loader.php';
		require_once BOTBLOCKER_DIR . 'admin/views/class-setup-guide-view.php';
		require_once BOTBLOCKER_DIR . 'admin/views/class-layout-view.php';
		BotBlockerHealthShortcodes::collectStatisticData();
		$bbcs_setup = BotBlocker::getInstance();
		if ( isset( $bbcs_setup->settings->cache_ui_data ) && $bbcs_setup->settings->cache_ui_data == false ) {
			BotBlockerCache::clearTransients();
		}
		$vm     = new Botblocker_SetupGuideViewModel();
		$view   = new Botblocker_SetupGuide_View( $vm );
		$layout = new Botblocker_Layout_View( $vm );
		$this->render_page( 'setup-guide', $view, $layout );
		do_action( 'bbcs_show_support_button' );
	}

	public function about_page(): void {
		require_once BOTBLOCKER_DIR . 'includes/viewmodels/class-about-viewmodel.php';
		require_once BOTBLOCKER_DIR . 'includes/components/component-loader.php';
		require_once BOTBLOCKER_DIR . 'admin/views/class-about-view.php';
		require_once BOTBLOCKER_DIR . 'admin/views/class-layout-view.php';

		$vm     = new Botblocker_AboutViewModel();
		$view   = new Botblocker_About_View( $vm );
		$layout = new Botblocker_Layout_View( $vm );

		$this->render_page( 'about', $view, $layout );
		do_action( 'bbcs_show_support_button' );
	}

	public function add_to_admin_bar( WP_Admin_Bar $wp_admin_bar ): void {
		if ( is_network_admin() ) {
			return;
		}
		if ( ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			return;
		}

		$wp_admin_bar->add_node(
			array(
				'id'    => 'bbcs_admin_bar',
				'title' => '<span class="ab-icon dashicons-shield-alt"></span> BotBlocker',
				'href'  => BotBlockerMultisite::getAdminPageUrl( 'bbcs_dashboard' ),
				'meta'  => array(
					'title' => __( 'Go to BotBlocker Dashboard', 'botblocker-security' ),
				),
			)
		);

		$wp_admin_bar->add_node(
			array(
				'id'     => 'bbcs_admin_bar_dashboard',
				'parent' => 'bbcs_admin_bar',
				'title'  => __( 'Dashboard', 'botblocker-security' ),
				'href'   => BotBlockerMultisite::getAdminPageUrl( 'bbcs_dashboard' ),
			)
		);

		$wp_admin_bar->add_node(
			array(
				'id'     => 'bbcs_admin_bar_settings',
				'parent' => 'bbcs_admin_bar',
				'title'  => __( 'Settings', 'botblocker-security' ),
				'href'   => BotBlockerMultisite::getAdminPageUrl( 'bbcs_settings' ),
			)
		);
	}

	public function register_admin_bar(): void {
		add_action( 'admin_bar_menu', array( $this, 'add_to_admin_bar' ), 100 );
	}

	public function plugin_action_links( array $links ): array {
		$dashboard_link = '<a href="' . esc_url( BotBlockerMultisite::getAdminPageUrl( 'bbcs_dashboard' ) ) . '" style="color: #2271b1; font-weight: 600;">' . esc_html__( 'Dashboard', 'botblocker-security' ) . '</a>';
		array_unshift( $links, $dashboard_link );
		return $links;
	}

	public function run(): void {
		$this->load_settings();
		add_filter( 'plugin_action_links_' . BOTBLOCKER_BASENAME, array( $this, 'plugin_action_links' ) );
	}
}
