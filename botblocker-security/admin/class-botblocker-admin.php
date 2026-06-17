<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

if ( defined( 'BOTBLOCKER_WIDGETS' ) && BOTBLOCKER_WIDGETS ) {
	include 'partials/botblocker-admin-dashboard-widgets.php';
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

	/**
	 * Register the stylesheets for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_styles(): void {
		global $pagenow;
		$screen = get_current_screen();

		if ( $pagenow === 'admin.php' && in_array( $screen->id, $this->get_botblocker_screen_ids() ) ) {
			$deps = array( 'common' );
			wp_enqueue_style( 'google-fonts-poppins', 'https://fonts.googleapis.com/css2?family=Poppins:wght@100;200;300;400;500;600;700&display=swap', array(), BOTBLOCKER_VERSION );
			wp_enqueue_style( BOTBLOCKER_SHORT_NAME . '-bootstrap', plugin_dir_url( __FILE__ ) . 'css/bootstrap/bootstrap.min.css', $deps, BOTBLOCKER_VERSION, 'all' );
			wp_enqueue_style( BOTBLOCKER_SHORT_NAME . '-theme', plugin_dir_url( __FILE__ ) . 'css/theme.css', $deps, BOTBLOCKER_VERSION, 'all' );
			wp_enqueue_style( BOTBLOCKER_SHORT_NAME . '-default', plugin_dir_url( __FILE__ ) . 'css/default.css', $deps, BOTBLOCKER_VERSION, 'all' );
			wp_enqueue_style( BOTBLOCKER_SHORT_NAME . '-fa', plugin_dir_url( __FILE__ ) . 'css/all.min.css', $deps, BOTBLOCKER_VERSION, 'all' );
			wp_enqueue_style( BOTBLOCKER_SHORT_NAME . '-flags', plugin_dir_url( __FILE__ ) . 'css/flags/flags.css', $deps, BOTBLOCKER_VERSION, 'all' );
			wp_enqueue_style( BOTBLOCKER_SHORT_NAME . '-datatables', plugin_dir_url( __FILE__ ) . 'css/datatables/datatables.min.css', $deps, BOTBLOCKER_VERSION, 'all' );
			wp_enqueue_style( BOTBLOCKER_SHORT_NAME . '-admin', plugin_dir_url( __FILE__ ) . 'css/botblocker-admin.css', $deps, BOTBLOCKER_VERSION, 'all' );
			wp_enqueue_style( BOTBLOCKER_SHORT_NAME . '-admin-mobile', plugin_dir_url( __FILE__ ) . 'css/botblocker-admin-mobile.css', $deps, BOTBLOCKER_VERSION, 'all' );

			wp_enqueue_style( BOTBLOCKER_SHORT_NAME . '-support-component', plugin_dir_url( __FILE__ ) . 'css/botblocker-support-component.css', $deps, BOTBLOCKER_VERSION, 'all' );
		}
		if ( $this->is_screen( $screen->id, 'toplevel_page_bbcs_dashboard' ) ) {
			wp_enqueue_style( BOTBLOCKER_SHORT_NAME . '-jsvectormap', plugin_dir_url( __FILE__ ) . 'css/jsvectormap/jsvectormap.min.css', array( 'common' ), BOTBLOCKER_VERSION );
		}
	}

	/**
	 * Register the JavaScript for the admin area.
	 *
	 * @since    1.0.0
	 */
	public function enqueue_scripts(): void {
		global $pagenow;
		$screen = get_current_screen();
		$locale = determine_locale();

		if ( $pagenow === 'admin.php' && in_array( $screen->id, $this->get_botblocker_screen_ids() ) ) {
			wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-modernizr', plugin_dir_url( __FILE__ ) . 'js/modernizr/modernizr.min.js', array( 'jquery' ), BOTBLOCKER_VERSION, false );
			wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-bootstrap-js', plugin_dir_url( __FILE__ ) . 'js/bootstrap/bootstrap.bundle.min.js', array( 'jquery' ), BOTBLOCKER_VERSION, false );
			wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-chartjs', plugin_dir_url( __FILE__ ) . 'js/chartjs/chart.umd.js', array(), BOTBLOCKER_VERSION, false );
			wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-datatables-js', plugin_dir_url( __FILE__ ) . 'js/datatables/datatables.min.js', array( 'jquery' ), BOTBLOCKER_VERSION, false );
			wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-common-js', plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-common.js', array( 'jquery' ), BOTBLOCKER_VERSION, true );
			wp_localize_script(
				BOTBLOCKER_SHORT_NAME . '-common-js',
				'botblockerData',
				array(
					'ajaxurl' => admin_url( 'admin-ajax.php' ),
					'nonce'   => wp_create_nonce( 'botblocker_nonce' ),
				)
			);
			wp_localize_script(
				BOTBLOCKER_SHORT_NAME . '-common-js',
				'bbcsCommonL10n',
				array(
					'ajax_error'         => __( 'AJAX Error: ', 'botblocker-security' ),
					'early_init_confirm' => __( 'Early Init requires active BotBlocker PRO and the Early Init add-on. Open Add-ons page?', 'botblocker-security' ),
					'failed_copy'        => __( 'Failed to copy: ', 'botblocker-security' ),
					'error_prefix'       => __( 'Error: ', 'botblocker-security' ),
				)
			);
			wp_localize_script(
				BOTBLOCKER_SHORT_NAME . '-common-js',
				'botblockerRedisMemcachedAvailability',
				array(
					'redisAvailable'     => BotBlockerCache::isRedisAvailable(),
					'memcachedAvailable' => BotBlockerCache::isMemcachedAvailable(),
				)
			);
			wp_localize_script(
				BOTBLOCKER_SHORT_NAME . '-common-js',
				'botblockerCurrentLocale',
				array(
					'locale' => self::$instance->check_translate_for_this_locale( $locale ),
				)
			);

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

			if ( $this->is_screen( $screen->id, 'toplevel_page_bbcs_dashboard' ) || $this->is_screen( $screen->id, 'botblocker_page_bbcs_setup_guide' ) ) {
				wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-health-gauge-js', plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-health-gauge.js', array( 'jquery', BOTBLOCKER_SHORT_NAME . '-chartjs' ), BOTBLOCKER_VERSION, true );
			}

			if ( $this->is_screen( $screen->id, 'botblocker_page_bbcs_reports' ) ) {
				wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-hits-js', plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-hits.js', array( 'jquery' ), BOTBLOCKER_VERSION, true );
				wp_localize_script(
					BOTBLOCKER_SHORT_NAME . '-hits-js',
					'botblockerData',
					array(
						'ajaxurl' => admin_url( 'admin-ajax.php' ),
						'nonce'   => wp_create_nonce( 'botblocker_nonce' ),
					)
				);
				wp_localize_script(
					BOTBLOCKER_SHORT_NAME . '-hits-js',
					'bbcsHitsL10n',
					array(
						'failed_create_rule' => __( 'Failed to create rule: ', 'botblocker-security' ),
					)
				);
			}

			if ( $this->is_screen( $screen->id, 'botblocker_page_bbcs_rules' ) ) {
				wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-rules-js', plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-rules.js', array( 'jquery' ), BOTBLOCKER_VERSION, true );
				wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-rules-ipv4-js', plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-rules-ipv4.js', array( 'jquery' ), BOTBLOCKER_VERSION, true );
				wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-rules-ipv6-js', plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-rules-ipv6.js', array( 'jquery' ), BOTBLOCKER_VERSION, true );
				wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-rules-white-js', plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-white.js', array( 'jquery' ), BOTBLOCKER_VERSION, true );
				wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-rules-path-js', plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-path.js', array( 'jquery' ), BOTBLOCKER_VERSION, true );
				wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-proxy-js', plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-proxy.js', array( 'jquery' ), BOTBLOCKER_VERSION, true );
				wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-asn-js', plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-asn.js', array( 'jquery' ), BOTBLOCKER_VERSION, true );
				wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-geo-js', plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-geo.js', array( 'jquery' ), BOTBLOCKER_VERSION, true );
				wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-llm-js', plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-llm.js', array( 'jquery' ), BOTBLOCKER_VERSION, true );
				wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-chart-js', plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-charts.js', array( 'jquery' ), BOTBLOCKER_VERSION, false );

				wp_localize_script(
					BOTBLOCKER_SHORT_NAME . '-geo-js',
					'botblockerGeoData',
					array(
						'ajaxurl' => admin_url( 'admin-ajax.php' ),
						'nonce'   => wp_create_nonce( 'botblocker_nonce' ),
						'i18n'    => array(
							'please_select_country'       => __( 'Please select a country.', 'botblocker-security' ),
							'country_already_added'       => __( 'Country already added.', 'botblocker-security' ),
							'invalid_country'             => __( 'Invalid country.', 'botblocker-security' ),
							'no_countries_selected'       => __( 'No countries selected', 'botblocker-security' ),
							'country_list_loaded'         => __( 'Country list loaded.', 'botblocker-security' ),
							'failed_to_load_saved_countries' => __( 'Failed to load saved countries.', 'botblocker-security' ),
							'server_error_loading_saved_countries' => __( 'Server error while loading saved countries.', 'botblocker-security' ),
							'country_list_saved'          => __( 'Country list saved.', 'botblocker-security' ),
							'failed_to_save_country_list' => __( 'Failed to save country list.', 'botblocker-security' ),
							'server_error_saving_country_list' => __( 'Server error while saving country list.', 'botblocker-security' ),
							'remove_country'              => __( 'Remove country', 'botblocker-security' ),
							'reloading'                   => __( 'Reloading...', 'botblocker-security' ),
							'saving'                      => __( 'Saving...', 'botblocker-security' ),
						),
					)
				);

				wp_localize_script(
					BOTBLOCKER_SHORT_NAME . '-llm-js',
					'bbcsLLML10n',
					array(
						'failed_update'  => __( 'Failed to update: ', 'botblocker-security' ),
						'failed_load'    => __( 'Failed to load: ', 'botblocker-security' ),
						'edit'           => __( 'Edit', 'botblocker-security' ),
						'enable'         => __( 'Enable', 'botblocker-security' ),
						'disable'        => __( 'Disable', 'botblocker-security' ),
						'enabled'        => __( 'Enabled', 'botblocker-security' ),
						'disabled'       => __( 'Disabled', 'botblocker-security' ),
						'no_providers'   => __( 'No LLM providers found', 'botblocker-security' ),
						'sync_cloud'     => __( 'Sync from Cloud', 'botblocker-security' ),
						'syncing'        => __( 'Syncing...', 'botblocker-security' ),
						'sync_scheduled' => __( 'Sync scheduled.', 'botblocker-security' ),
						'sync_failed'    => __( 'Sync failed: ', 'botblocker-security' ),
						'download_json'  => __( 'Download as JSON', 'botblocker-security' ),
						'to_php'         => __( 'Save LLM rules to PHP file', 'botblocker-security' ),
					)
				);
				wp_localize_script(
					BOTBLOCKER_SHORT_NAME . '-rules-js',
					'botblockerData',
					array(
						'ajaxurl' => admin_url( 'admin-ajax.php' ),
						'nonce'   => wp_create_nonce( 'botblocker_nonce' ),
					)
				);
				wp_localize_script(
					BOTBLOCKER_SHORT_NAME . '-rules-js',
					'bbcsRulesL10n',
					array(
						'invalid_json'   => __( 'Invalid JSON file: ', 'botblocker-security' ),
						'failed_update'  => __( 'Failed to update rule: ', 'botblocker-security' ),
						'failed_load'    => __( 'Failed to load rule details: ', 'botblocker-security' ),
						'confirm_delete' => __( 'Are you sure you want to delete this rule?', 'botblocker-security' ),
						'failed_create'  => __( 'Failed to create rule: ', 'botblocker-security' ),
						'failed_export'  => __( 'Failed to export rules: ', 'botblocker-security' ),
						'failed_import'  => __( 'Failed to import rules: ', 'botblocker-security' ),
						'failed_clear'   => __( 'Failed to clear rules: ', 'botblocker-security' ),
					)
				);
				wp_localize_script(
					BOTBLOCKER_SHORT_NAME . '-rules-ipv4-js',
					'botblockerData',
					array(
						'ajaxurl' => admin_url( 'admin-ajax.php' ),
						'nonce'   => wp_create_nonce( 'botblocker_nonce' ),
					)
				);
				wp_localize_script(
					BOTBLOCKER_SHORT_NAME . '-rules-ipv4-js',
					'bbcsIpv4L10n',
					array(
						'invalid_json'            => __( 'Invalid JSON file: ', 'botblocker-security' ),
						'failed_load'             => __( 'Failed to load IPv4 rule details: ', 'botblocker-security' ),
						'failed_update'           => __( 'Failed to update IPv4 rule: ', 'botblocker-security' ),
						'confirm_delete'          => __( 'Are you sure you want to delete this IPv4 rule?', 'botblocker-security' ),
						'failed_create'           => __( 'Failed to create IPv4 rule: ', 'botblocker-security' ),
						'failed_export'           => __( 'Failed to export IPv4 rules: ', 'botblocker-security' ),
						'failed_import'           => __( 'Failed to import IPv4 rules: ', 'botblocker-security' ),
						'failed_clear'            => __( 'Failed to clear IPv4 rules: ', 'botblocker-security' ),
						'failed_import_whitelist' => __( 'Failed to import IPv4 whitelist: ', 'botblocker-security' ),
						'failed_import_blacklist' => __( 'Failed to import IPv4 blacklist: ', 'botblocker-security' ),
					)
				);
				wp_localize_script(
					BOTBLOCKER_SHORT_NAME . '-rules-ipv6-js',
					'botblockerData',
					array(
						'ajaxurl' => admin_url( 'admin-ajax.php' ),
						'nonce'   => wp_create_nonce( 'botblocker_nonce' ),
					)
				);
				wp_localize_script(
					BOTBLOCKER_SHORT_NAME . '-rules-ipv6-js',
					'bbcsIpv6L10n',
					array(
						'invalid_json'            => __( 'Invalid JSON file: ', 'botblocker-security' ),
						'failed_load'             => __( 'Failed to load IPv6 rule details: ', 'botblocker-security' ),
						'failed_update'           => __( 'Failed to update IPv6 rule: ', 'botblocker-security' ),
						'confirm_delete'          => __( 'Are you sure you want to delete this IPv6 rule?', 'botblocker-security' ),
						'failed_create'           => __( 'Failed to create IPv6 rule: ', 'botblocker-security' ),
						'failed_export'           => __( 'Failed to export IPv6 rules: ', 'botblocker-security' ),
						'failed_import'           => __( 'Failed to import IPv6 rules: ', 'botblocker-security' ),
						'failed_clear'            => __( 'Failed to clear IPv6 rules: ', 'botblocker-security' ),
						'failed_import_whitelist' => __( 'Failed to import IPv6 whitelist: ', 'botblocker-security' ),
						'failed_import_blacklist' => __( 'Failed to import IPv6 blacklist: ', 'botblocker-security' ),
					)
				);
				wp_localize_script(
					BOTBLOCKER_SHORT_NAME . '-rules-white-js',
					'botblockerData',
					array(
						'ajaxurl' => admin_url( 'admin-ajax.php' ),
						'nonce'   => wp_create_nonce( 'botblocker_nonce' ),
					)
				);
				wp_localize_script(
					BOTBLOCKER_SHORT_NAME . '-rules-white-js',
					'bbcsWhiteL10n',
					array(
						'invalid_json'   => __( 'Invalid JSON file: ', 'botblocker-security' ),
						'failed_update'  => __( 'Failed to update white bot: ', 'botblocker-security' ),
						'failed_load'    => __( 'Failed to load white bot details: ', 'botblocker-security' ),
						'confirm_delete' => __( 'Are you sure you want to delete this white bot?', 'botblocker-security' ),
						'failed_create'  => __( 'Failed to create white bot: ', 'botblocker-security' ),
						'failed_export'  => __( 'Failed to export white bots: ', 'botblocker-security' ),
						'failed_import'  => __( 'Failed to import white bots: ', 'botblocker-security' ),
						'failed_clear'   => __( 'Failed to clear white bots: ', 'botblocker-security' ),
					)
				);
				wp_localize_script(
					BOTBLOCKER_SHORT_NAME . '-rules-path-js',
					'botblockerData',
					array(
						'ajaxurl' => admin_url( 'admin-ajax.php' ),
						'nonce'   => wp_create_nonce( 'botblocker_nonce' ),
					)
				);
				wp_localize_script(
					BOTBLOCKER_SHORT_NAME . '-rules-path-js',
					'bbcsPathL10n',
					array(
						'invalid_json'   => __( 'Invalid JSON file: ', 'botblocker-security' ),
						'failed_update'  => __( 'Failed to update path: ', 'botblocker-security' ),
						'failed_load'    => __( 'Failed to load path details: ', 'botblocker-security' ),
						'confirm_delete' => __( 'Are you sure you want to delete this path?', 'botblocker-security' ),
						'failed_create'  => __( 'Failed to create path: ', 'botblocker-security' ),
						'failed_export'  => __( 'Failed to export paths: ', 'botblocker-security' ),
						'failed_import'  => __( 'Failed to import paths: ', 'botblocker-security' ),
						'failed_clear'   => __( 'Failed to clear paths: ', 'botblocker-security' ),
					)
				);
				wp_localize_script(
					BOTBLOCKER_SHORT_NAME . '-proxy-js',
					'bbcsProxyL10n',
					array(
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
					)
				);
				wp_localize_script(
					BOTBLOCKER_SHORT_NAME . '-asn-js',
					'botblockerData',
					array(
						'ajaxurl' => admin_url( 'admin-ajax.php' ),
						'nonce'   => wp_create_nonce( 'botblocker_nonce' ),
					)
				);
				wp_localize_script(
					BOTBLOCKER_SHORT_NAME . '-asn-js',
					'bbcsAsnL10n',
					array(
						'invalid_json'   => __( 'Invalid JSON file: ', 'botblocker-security' ),
						'failed_update'  => __( 'Failed to update ASN rule: ', 'botblocker-security' ),
						'failed_load'    => __( 'Failed to load ASN details: ', 'botblocker-security' ),
						'confirm_delete' => __( 'Are you sure you want to delete this ASN rule?', 'botblocker-security' ),
						'failed_create'  => __( 'Failed to create ASN rule: ', 'botblocker-security' ),
						'failed_export'  => __( 'Failed to export ASN rules: ', 'botblocker-security' ),
						'failed_import'  => __( 'Failed to import ASN rules: ', 'botblocker-security' ),
						'failed_clear'   => __( 'Failed to clear ASN rules: ', 'botblocker-security' ),
					)
				);
			}

			if ( $this->is_screen( $screen->id, 'botblocker_page_bbcs_cloud_api' ) ) {
				wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-cloud-api-js', plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-cloud-api.js', array( 'jquery' ), BOTBLOCKER_VERSION, true );
				wp_localize_script(
					BOTBLOCKER_SHORT_NAME . '-cloud-api-js',
					'bbcsCloudApiL10n',
					array(
						'refreshed'      => __( 'BotBlocker PRO information refreshed successfully!', 'botblocker-security' ),
						'failed_refresh' => __( 'Failed to refresh BotBlocker PRO information.', 'botblocker-security' ),
						'ajax_error'     => __( 'AJAX Error: ', 'botblocker-security' ),
					)
				);
			}

			if ( $this->is_screen( $screen->id, 'botblocker_page_bbcs_setup_guide' ) ) {
				wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-chart-js', plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-charts.js', array( 'jquery' ), BOTBLOCKER_VERSION, false );
			}

			if ( $this->is_screen( $screen->id, 'botblocker_page_bbcs_integrations' ) ) {
				wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-integrations-js', plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-integrations.js', array( 'jquery' ), BOTBLOCKER_VERSION, true );
				wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-2fa-js', plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-2fa.js', array( 'jquery' ), BOTBLOCKER_VERSION, true );
				wp_localize_script(
					BOTBLOCKER_SHORT_NAME . '-2fa-js',
					'botblockerData',
					array(
						'ajaxurl' => admin_url( 'admin-ajax.php' ),
						'nonce'   => wp_create_nonce( 'botblocker_nonce' ),
					)
				);
				wp_localize_script(
					BOTBLOCKER_SHORT_NAME . '-2fa-js',
					'bbcs2faL10n',
					array(
						'reset_failed' => __( 'Reset failed', 'botblocker-security' ),
					)
				);
			}

			if ( $this->is_screen( $screen->id, 'botblocker_page_bbcs_settings' ) ) {
				wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-settings-js', plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-settings.js', array( 'jquery' ), BOTBLOCKER_VERSION, true );
				wp_add_inline_script(
					BOTBLOCKER_SHORT_NAME . '-settings-js',
					'var bbcsUnsavedLabel = ' . wp_json_encode( __( 'Not saved!', 'botblocker-security' ) ) . ';',
					'before'
				);
			}

			if ( $this->is_screen( $screen->id, 'botblocker_page_bbcs_tools' ) ) {
				wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-tools-js', plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-tools.js', array( 'jquery' ), BOTBLOCKER_VERSION, true );
				wp_add_inline_script(
					BOTBLOCKER_SHORT_NAME . '-tools-js',
					'var bbcsUnsavedLabel = ' . wp_json_encode( __( 'Not saved!', 'botblocker-security' ) ) . ';',
					'before'
				);
				wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-maintenance-js', plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-maintenance.js', array( 'jquery' ), BOTBLOCKER_VERSION, true );
				wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-malware-js', plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-malware.js', array( 'jquery' ), BOTBLOCKER_VERSION, true );
				wp_localize_script(
					BOTBLOCKER_SHORT_NAME . '-malware-js',
					'botblockerData',
					array(
						'adminUrl' => is_network_admin() ? network_admin_url( '' ) : admin_url( '' ),
						'ajaxurl'  => admin_url( 'admin-ajax.php' ),
						'nonce'    => wp_create_nonce( 'botblocker_nonce' ),
					)
				);
				wp_localize_script(
					BOTBLOCKER_SHORT_NAME . '-maintenance-js',
					'botblockerData',
					array(
						'adminUrl' => is_network_admin() ? network_admin_url( '' ) : admin_url( '' ),
						'ajaxurl'  => admin_url( 'admin-ajax.php' ),
						'nonce'    => wp_create_nonce( 'botblocker_nonce' ),
					)
				);
				wp_localize_script(
					BOTBLOCKER_SHORT_NAME . '-maintenance-js',
					'bbcsMaintenanceL10n',
					array(
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
					)
				);
			}

			if ( $this->is_screen( $screen->id, 'botblocker_page_bbcs_addons' ) ) {
				wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-addons-js', plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-addons.js', array( 'jquery' ), BOTBLOCKER_VERSION, true );
			}

			if ( $this->is_screen( $screen->id, 'toplevel_page_bbcs_dashboard' ) ) {
				wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-jsvectormap', plugin_dir_url( __FILE__ ) . 'js/jsvectormap/jsvectormap.js', array(), BOTBLOCKER_VERSION, false );
				wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-jsvectormap-world', plugin_dir_url( __FILE__ ) . 'js/jsvectormap/maps/world.js', array( BOTBLOCKER_SHORT_NAME . '-jsvectormap' ), BOTBLOCKER_VERSION, false );

				wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-chart-js', plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-charts.js', array( 'jquery' ), BOTBLOCKER_VERSION, false );

				wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-dash-js', plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-dashboard.js', array( 'jquery' ), BOTBLOCKER_VERSION, true );
				wp_localize_script(
					BOTBLOCKER_SHORT_NAME . '-dash-js',
					'botblockerData',
					array(
						'ajaxurl' => admin_url( 'admin-ajax.php' ),
						'nonce'   => wp_create_nonce( 'botblocker_nonce' ),
					)
				);
				wp_localize_script(
					BOTBLOCKER_SHORT_NAME . '-dash-js',
					'bbcsDashL10n',
					array(
						'error_prefix' => __( 'Error: ', 'botblocker-security' ),
						'ajax_error'   => __( 'AJAX Error: ', 'botblocker-security' ),
					)
				);
			}

			if ( $this->is_screen( $screen->id, 'botblocker_page_bbcs_setup_guide' ) ) {
				wp_enqueue_script( BOTBLOCKER_SHORT_NAME . '-setup-js', plugin_dir_url( __FILE__ ) . 'js/bbcs-js/bbcs-setup.js', array( 'jquery' ), BOTBLOCKER_VERSION, true );
				wp_localize_script(
					BOTBLOCKER_SHORT_NAME . '-setup-js',
					'bbcsSetupL10n',
					array(
						'pro_required'   => __( 'Full Protection requires PRO license. Please upgrade to PRO first.', 'botblocker-security' ),
						'error_apply'    => __( 'Error applying profile', 'botblocker-security' ),
						'request_failed' => __( 'Request failed. Please try again.', 'botblocker-security' ),
						'please_wait'    => __( 'Please wait...', 'botblocker-security' ),
						'apply_now'      => __( 'Apply Now', 'botblocker-security' ),
					)
				);
			}
		}
	}

	public function add_admin_menu(): void {
		$cap = BotBlockerMultisite::canManage();

		add_menu_page(
			'BotBlocker',
			'BotBlocker',
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

		// // Setup Wizard
		// add_submenu_page(
		//     null, 
		//     __('Setup Wizard', 'botblocker-security'),
		//     __('Setup Wizard', 'botblocker-security'),
		//     'manage_options',
		//     'bbcs_setup_wizard',
		//     '__return_false' 
		// );

		add_menu_page(
			__( 'Setup Wizard', 'botblocker-security' ),
			__( 'Setup Wizard', 'botblocker-security' ),
			$cap,
			'bbcs_setup_wizard',
			'bbcs_render_setup_wizard',
			'',
			null
		);

		remove_menu_page( 'bbcs_setup_wizard' );
	}

	public function dashboard_page(): void {
		global $BBCS;
		$BBCS = BotBlocker::getInstance();
		require plugin_dir_path( __FILE__ ) . 'partials/botblocker-admin-display-dashboard.php';
		do_action( 'bbcs_show_support_button' );
	}

	public function settings_page(): void {
		require plugin_dir_path( __FILE__ ) . 'partials/botblocker-admin-display-settings.php';
		do_action( 'bbcs_show_support_button' );
	}

	public function reports_page(): void {
		require plugin_dir_path( __FILE__ ) . 'partials/botblocker-admin-display-reports.php';
		do_action( 'bbcs_show_support_button' );
	}

	public function rules_page(): void {
		require plugin_dir_path( __FILE__ ) . 'partials/botblocker-admin-display-rules.php';
		do_action( 'bbcs_show_support_button' );
	}

	public function tools_page(): void {
		require plugin_dir_path( __FILE__ ) . 'partials/botblocker-admin-display-tools.php';
		do_action( 'bbcs_show_support_button' );
	}

	public function integrations_page(): void {
		require plugin_dir_path( __FILE__ ) . 'partials/botblocker-admin-display-integrations.php';
		do_action( 'bbcs_show_support_button' );
	}

	public function cloud_api_page(): void {
		require plugin_dir_path( __FILE__ ) . 'partials/botblocker-admin-display-cloud-api.php';
		do_action( 'bbcs_show_support_button' );
	}

	public function addons_page(): void {
		require plugin_dir_path( __FILE__ ) . 'partials/botblocker-admin-display-addons.php';
		do_action( 'bbcs_show_support_button' );
	}

	public function setup_guide_page(): void {
		require plugin_dir_path( __FILE__ ) . 'partials/botblocker-admin-display-setup-guide.php';
		do_action( 'bbcs_show_support_button' );
	}

	public function about_page(): void {
		require plugin_dir_path( __FILE__ ) . 'partials/botblocker-admin-display-about.php';
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

	private function check_translate_for_this_locale( string $locale ): string {
		$mo_file = BOTBLOCKER_DIR . 'languages/botblocker-security-' . $locale . '.mo';
		return file_exists( $mo_file ) ? $locale : '';
	}
}
