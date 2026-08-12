<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * The BotBlocker engine class.
 *
 * This class is responsible for all the operations against bots.
 * It handles detections, logging, and blocking of suspicious bot activities.
 *
 * @version    1.7.4
 * @author     BotBlocker Team
 * @package    Botblocker
 * @subpackage Botblocker/includes
 */

require_once BOTBLOCKER_DIR . 'includes/botblocker/class-botblocker-base.php';
require_once BOTBLOCKER_DIR . 'includes/botblocker/class-botblocker-settings.php';
require_once BOTBLOCKER_DIR . 'includes/botblocker/class-botblocker-security-page-assets.php';
require_once BOTBLOCKER_DIR . 'includes/botblocker/traits/class-botblocker-core-trait.php';
require_once BOTBLOCKER_DIR . 'includes/botblocker/traits/class-botblocker-visitor-trait.php';
require_once BOTBLOCKER_DIR . 'includes/botblocker/traits/class-botblocker-rules-trait.php';
require_once BOTBLOCKER_DIR . 'includes/botblocker/traits/class-botblocker-payment-trait.php';
require_once BOTBLOCKER_DIR . 'includes/botblocker/traits/class-botblocker-response-trait.php';
require_once BOTBLOCKER_DIR . 'includes/botblocker/traits/class-botblocker-local-data-trait.php';
require_once BOTBLOCKER_DIR . 'includes/botblocker/traits/class-botblocker-local-validation-trait.php';
require_once BOTBLOCKER_DIR . 'includes/botblocker/traits/class-botblocker-local-trait.php';
require_once BOTBLOCKER_DIR . 'includes/botblocker/traits/class-botblocker-local-recaptcha-trait.php';
require_once BOTBLOCKER_DIR . 'includes/botblocker/traits/class-botblocker-local-cloud-trait.php';
require_once BOTBLOCKER_DIR . 'includes/botblocker/traits/class-botblocker-post-trait.php';
require_once BOTBLOCKER_DIR . 'includes/botblocker/traits/class-botblocker-cookie-trait.php';
require_once BOTBLOCKER_DIR . 'includes/botblocker/traits/class-botblocker-header-trait.php';
require_once BOTBLOCKER_DIR . 'includes/botblocker/traits/class-botblocker-addon-decision-trait.php';
require_once BOTBLOCKER_DIR . 'includes/botblocker/traits/class-botblocker-check-page-trait.php';
require_once BOTBLOCKER_DIR . 'includes/botblocker/traits/class-botblocker-block-page-trait.php';
require_once BOTBLOCKER_DIR . 'includes/botblocker/traits/class-botblocker-denied-page-trait.php';
require_once BOTBLOCKER_DIR . 'includes/botblocker/traits/class-botblocker-response-signing-trait.php';
require_once BOTBLOCKER_DIR . 'includes/botblocker/traits/class-botblocker-core-rate-trait.php';
require_once BOTBLOCKER_DIR . 'includes/botblocker/traits/class-botblocker-tls-trait.php';

class BotBlocker extends BotBlockerBase {

	use BotBlockerCoreTrait;
	use BotBlockerVisitorTrait;
	use BotBlockerRulesTrait;
	use BotBlockerPaymentTrait;
	use BotBlockerResponseTrait;
	use BotBlockerLocalDataTrait;
	use BotBlockerLocalValidationTrait;
	use BotBlockerLocalTrait;
	use BotBlockerLocalReCaptchaTrait;
	use BotBlockerLocalCloudTrait;
	use BotBlockerPostTrait;
	use BotBlockerCookieTrait;
	use BotBlockerHeaderTrait;
	use BotBlockerAddonDecisionTrait;
	use BotBlockerCheckPageTrait;
	use BotBlockerBlockPageTrait;
	use BotBlockerDeniedPageTrait;
	use BotBlockerResponseSigningTrait;
	use BotBlockerCoreRateTrait;
	use BotBlockerTlsTrait;

	public static function getInstance(): self {
		if ( self::$instance === null ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	public function __construct() {
		$this->time                   = time();
		$this->should_show_check_page = false;
	}

	public function initialize(): void {
		$this->start_bbcs();
		$this->check_admin_status();
		$this->load_directories();
		$this->generate_missing_files();
		$this->load_data();
		$this->initialize_config();
		$this->load_settings();
		$this->generate_connection_id();

		if ( $this->check_secret_parameter() ) {
			$this->finalize_allowed_headers();
			return;
		}
		if ( $this->process_disabled_state() ) {
			$this->finalize_allowed_headers();
			return;
		}

		$guard = $this->start_output_guard();
		$this->run();
		$this->finalize_allowed_headers();
		$this->end_output_guard( $guard );
	}

	public function run(): void {

		do_action( 'bbcs_botblocker_before_request_check', $this );
		if ( $this->apply_addon_traffic_decisions( 'before_prefly_checks' ) ) {
			return;
		}
		if ( $this->perform_prefly_checks() ) {
			return;
		}
		$this->collect_basic_request_data();
		if ( $this->addon_traffic_decision_stops_request() ) {
			return;
		}
		if ( $this->check_options_preflight() ) {
			return;
		}
		if ( $this->is_safe_request() ) {
			return;
		}
		$this->collect_visitor_data();
		$this->update_settings_based_on_visitor_data();
		$this->check_tls_fingerprint();
		do_action( 'bbcs_botblocker_after_visitor_data', $this );
		if ( $this->apply_addon_traffic_decisions( 'after_visitor_data' ) ) {
			return;
		}
		if ( $this->check_payment_bypass() ) {
			if ( ! $this->payment_bypass_partial ) {
				return;
			}
		}

		$this->select_request_mode();

		$this->resolve_cookie_identity();

		if ( $this->apply_addon_traffic_decisions( 'pre_core_rules' ) ) {
			return;
		}
		// LLM fetchers are verified by published IP CIDR ranges, not reverse DNS. Run before check_white_bot()
		if ( $this->check_llm_bot() ) {
			return;
		}
		if ( $this->check_white_bot() ) {
			return;
		}
		if ( $this->check_ip_rules() ) {
			return;
		}
		if ( $this->check_rugov_rules() ) {
			return;
		}
		if ( $this->check_asn_rules() ) {
			return;
		}
		if ( $this->check_rules_database() ) {
			return;
		}
		if ( $this->check_path_rules() ) {
			return;
		}
		if ( $this->apply_addon_traffic_decisions( 'post_core_rules' ) ) {
			return;
		}
		if ( $this->apply_core_rate_limit() ) {
			return;
		}
		if ( $this->apply_addon_traffic_decisions( 'post_rate_limit' ) ) {
			return;
		}

		if ( $this->payment_bypass_partial ) {
			$this->visitorType = self::VISITOR_LEGALBOT;
			$this->white_bot   = 'payment-gateway';
			$this->set_x_robot_headers();
			return;
		}

		$this->process_headers();

		if ( $this->process_cookies() ) {
			$this->send_vary_cookie_header();
			$this->set_x_robot_headers();
			return;
		}

		$this->perform_simple_bot_checks();
		$this->validate_referer();
		$this->check_referer_get_params();
		$this->check_hosting();
		$this->check_language_mismatch();

		//if ($this->check_last_rule()) return;

		if ( $this->settings->botblocker_force_check == 1 && ! $this->should_show_check_page ) {
			$this->redirect_to_dark( 'Force check event' );
		}

		if ( $this->apply_addon_traffic_decisions( 'before_final_allow' ) ) {
			return;
		}
		do_action( 'bbcs_botblocker_allowed_request', null, $this, 'final_allow', array() );
		$this->set_x_robot_headers();
	}
	public function get_bot_blocker_hive(): array {
		$properties = get_object_vars( $this );
		$bbcs       = array();
		foreach ( $properties as $key => $value ) {
			if ( ! in_array( $key, array( 'instance' ) ) ) {
				$bbcs[ $key ] = $value;
			}
		}
		return $this->mask_sensitive_data( $bbcs );
	}

	public function sensitive_hive_keys(): array {
		$keys = array(
			'cloud_api_key',
			'cloud_api_pass',
			'cloud_api_secret',
			'cloud_api_email',
			'recaptcha_key2',
			'recaptcha_key3',
			'recaptcha_secret2',
			'recaptcha_secret3',
			'salt',
			'salt_pz',
			'redis_password',
			'memcached_password',
			'password',
			'secret',
			'api_key',
			'token',
		);

		/**
		 * Filter the list of sensitive hive keys that should be masked in diagnostic output.
		 *
		 * @param string[] $keys Lowercase key names to mask.
		 */
		return apply_filters( 'bbcs_sensitive_hive_keys', $keys );
	}

	public function mask_sensitive_data( array $data ): array {
		$sensitive_keys = $this->sensitive_hive_keys();

		foreach ( $data as $key => $value ) {
			$lower_key = strtolower( (string) $key );

			if ( is_object( $value ) ) {
				$value = get_object_vars( $value );
			}

			if ( is_array( $value ) ) {
				$data[ $key ] = $this->mask_sensitive_data( $value );
				continue;
			}

			foreach ( $sensitive_keys as $sensitive ) {
				if ( strpos( $lower_key, $sensitive ) !== false ) {
					$data[ $key ] = '****';
					continue 2;
				}
			}
		}

		return $data;
	}

	public function print_hive(): void {
		/**
		 * REVIEWER NOTE:
		 * print_r() is used here for displaying diagnostic information on admin pages only,
		 * not for debug logging. This method displays the hive state in the admin interface
		 * for troubleshooting purposes and is not used in production error logging.
		 * The PHPCS warning WordPress.PHP.DevelopmentFunctions.error_log_print_r is a false positive.
		 */
        /* phpcs:disable WordPress.PHP.DevelopmentFunctions.error_log_print_r */
		$BBCS = $this->get_bot_blocker_hive();
		echo '<small><pre>';
		print_r( $BBCS );
		echo '</pre></small>';
        /* phpcs:enable WordPress.PHP.DevelopmentFunctions.error_log_print_r */
	}

	// Native die() - not wp_die() - defense-in-depth: prevents third-party wp_die_handler filter bypass of the security barrier.
	public function process_die( string $message = '' ): void {
		BotBlockerCounters::flushHits();
		if ( apply_filters( 'bbcs_test_intercept_termination', false, $message, $this ) ) {
			return;
		}
		$this->maybe_spawn_cron();
		if ( ! empty( $message ) ) {
			die( wp_kses_post( $message ) );
		} elseif ( BBCS_DIE_MESSAGE ) {
				$this->print_hive();
				// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				die( did_action( 'init' ) ? __( 'WordPress stopped by BotBlocker', 'botblocker-security' ) : 'WordPress stopped by BotBlocker' );
		} else {
			die();
		}
	}

	private function maybe_spawn_cron(): void {
		$mode = $this->cron_dispatch_mode(
			defined( 'DOING_CRON' ),
			(bool) ( defined( 'DISABLE_WP_CRON' ) && DISABLE_WP_CRON ),
			(bool) ( defined( 'ALTERNATE_WP_CRON' ) && ALTERNATE_WP_CRON )
		);

		if ( BBCS_CRON_MODE_FALLBACK === $mode ) {
			add_action( 'shutdown', array( $this, 'run_cron_fallback' ), 20 );
			return;
		}

		if ( BBCS_CRON_MODE_SPAWN !== $mode || ! function_exists( 'spawn_cron' ) ) {
			return;
		}
		$this->ensure_cron_lock_timeout( defined( 'WP_CRON_LOCK_TIMEOUT' ) );
		spawn_cron();
	}

	private function cron_dispatch_mode( bool $doing_cron, bool $cron_disabled, bool $alternate ): string {
		if ( $doing_cron || $cron_disabled ) {
			return BBCS_CRON_MODE_NONE;
		}
		return $alternate ? BBCS_CRON_MODE_FALLBACK : BBCS_CRON_MODE_SPAWN;
	}

	public function run_cron_fallback(): void {
		if ( ! class_exists( 'BotBlockerCron' ) ) {
			require_once BOTBLOCKER_DIR . 'includes/cron/class-botblocker-cron.php';
		}
		if ( ! class_exists( 'BotBlockerCron' ) ) {
			return;
		}
		ignore_user_abort( true );
		BotBlockerCron::fallbackRunner();
	}

	private function ensure_cron_lock_timeout( bool $already_defined ): void {
		// Core defines WP_CRON_LOCK_TIMEOUT after plugins_loaded - too late for process_die().
		if ( ! $already_defined ) {
			define( 'WP_CRON_LOCK_TIMEOUT', MINUTE_IN_SECONDS );
		}
	}
}
