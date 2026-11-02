<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BotBlockerDeactivationFeedback {

	public const FIRST_ACTIVATED_OPTION = 'bbcs_first_activated_at';

	public const SUPPORT_URL = 'https://botblocker.org/support';

	// Rendered group by group; options are shuffled inside a group so no single answer wins by position.
	private static $reason_groups = array(
		array( 'technical_issue', 'false_positives', 'performance' ),
		array( 'too_complex', 'missing_feature', 'better_alternative' ),
		array( 'temporary' ),
		array( 'other' ),
	);

	private static $followup_reasons = array( 'technical_issue', 'false_positives', 'temporary' );

	private static $rescue_reasons = array( 'technical_issue', 'false_positives', 'performance', 'temporary' );

	// Option key => reasons it is offered for; empty = every follow-up reason.
	private static $what_broke_reasons = array(
		'visitors_blocked'  => array(),
		'good_bots_blocked' => array(),
		'checkout_forms'    => array(),
		'admin_login'       => array(),
		'api_integrations'  => array(),
		'plugin_conflict'   => array(),
		'nothing'           => array( 'temporary' ),
		'not_sure'          => array(),
	);

	public static function register(): void {
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueueAssets' ) );
		add_action( 'admin_footer', array( self::class, 'renderModal' ) );
		add_action( 'wp_ajax_bbcs_store_deactivation_feedback', array( self::class, 'handleStoreFeedback' ) );
		add_action( 'wp_ajax_bbcs_pause_protection', array( self::class, 'handlePauseProtection' ) );
	}

	public static function enqueueAssets( string $hook_suffix ): void {
		if ( $hook_suffix !== 'plugins.php' ) {
			return;
		}

		$admin_file = BOTBLOCKER_DIR . 'admin/class-botblocker-admin.php';
		$admin_url  = plugin_dir_url( $admin_file );

		wp_enqueue_style( 'bbcs-tokens', $admin_url . 'css/bbcs-tokens.css', array( 'common' ), BOTBLOCKER_VERSION, 'all' );
		wp_enqueue_style( 'bbcs-main', $admin_url . 'css/bbcs.css', array( 'bbcs-tokens' ), BOTBLOCKER_VERSION, 'all' );

		wp_enqueue_script(
			BOTBLOCKER_SHORT_NAME . '-shared-helpers-js',
			$admin_url . 'js/bbcs-js/bbcs-shared-helpers.js',
			array( 'jquery' ),
			BOTBLOCKER_VERSION,
			true
		);

		wp_enqueue_script(
			BOTBLOCKER_SHORT_NAME . '-deactivation-feedback-js',
			$admin_url . 'js/bbcs-js/bbcs-deactivation-feedback.js',
			array( 'jquery', BOTBLOCKER_SHORT_NAME . '-shared-helpers-js' ),
			BOTBLOCKER_VERSION,
			true
		);

		wp_localize_script(
			BOTBLOCKER_SHORT_NAME . '-deactivation-feedback-js',
			'bbcsDeactivationFeedback',
			array(
				'ajaxurl'        => admin_url( 'admin-ajax.php' ),
				'nonce'          => wp_create_nonce( 'bbcs_deactivation_feedback' ),
				'pluginBasename' => BOTBLOCKER_BASENAME,
				'reasons'        => self::getReasonLabels(),
				'followupReasons' => self::$followup_reasons,
				'rescueReasons'   => self::$rescue_reasons,
				'i18n'           => array(
					'title'           => __( 'Before you go', 'botblocker-security' ),
					'intro'           => __( 'Help us improve BotBlocker. Why are you deactivating the plugin?', 'botblocker-security' ),
					'details'         => __( 'Additional details (optional)', 'botblocker-security' ),
					'skip'            => __( 'Skip and deactivate', 'botblocker-security' ),
					'submit'          => __( 'Send and deactivate', 'botblocker-security' ),
					'submitting'      => __( 'Saving...', 'botblocker-security' ),
					'pauseProtection' => __( 'Pause protection instead', 'botblocker-security' ),
					'pausing'         => __( 'Pausing...', 'botblocker-security' ),
					'pauseFailed'     => __( 'Could not pause protection. Try again or deactivate the plugin.', 'botblocker-security' ),
					'detailsOther'    => __( 'Please tell us why you are deactivating', 'botblocker-security' ),
					'questionBroke'   => __( 'What went wrong?', 'botblocker-security' ),
					'questionTemp'    => __( 'Did something break before you turned it off?', 'botblocker-security' ),
				),
			)
		);
	}

	public static function renderModal(): void {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || ! in_array( $screen->id, array( 'plugins', 'plugins-network' ), true ) ) {
			return;
		}

		$icons_file = BOTBLOCKER_DIR . 'admin/templates/shared/icons-sprite.php';
		if ( is_readable( $icons_file ) ) {
			$renderer = require $icons_file;
			if ( is_callable( $renderer ) ) {
				$renderer();
			}
		}

		$modal_file = BOTBLOCKER_DIR . 'admin/templates/plugins/deactivation-feedback-modal.php';
		if ( is_readable( $modal_file ) ) {
			$renderer = require $modal_file;
			if ( is_callable( $renderer ) ) {
				$renderer();
			}
		}
	}

	public static function handlePauseProtection(): void {
		check_ajax_referer( 'bbcs_deactivation_feedback', 'nonce' );

		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_error( array( 'message' => 'Unauthorized' ) );
		}

		BotBlockerDb::togglePower( 1 );
		delete_transient( 'bbcs_site_health_list' );
		delete_transient( 'bbcs_site_health' );

		wp_send_json_success( array( 'paused' => true ) );
	}

	public static function handleStoreFeedback(): void {
		check_ajax_referer( 'bbcs_deactivation_feedback', 'nonce' );

		// Feedback is best-effort: any failure below just skips storage, never errors out
		// the deactivation flow.
		if ( ! current_user_can( 'activate_plugins' ) ) {
			wp_send_json_success( array( 'stored' => false ) );
		}

		if ( ! BotBlockerIp::rateLimit( 'bbcs_store_deactivation_feedback', 5, HOUR_IN_SECONDS ) ) {
			wp_send_json_success( array( 'stored' => false ) );
		}

		$reason = isset( $_POST['reason'] ) ? sanitize_key( wp_unslash( $_POST['reason'] ) ) : '';
		if ( ! in_array( $reason, self::getReasonKeys(), true ) ) {
			// Invalid reason: skip sending to cloud, don't error out the deactivation flow.
			wp_send_json_success( array( 'stored' => false ) );
		}

		$details = isset( $_POST['details'] ) ? sanitize_textarea_field( wp_unslash( $_POST['details'] ) ) : '';
		if ( mb_strlen( $details ) > 2000 ) {
			$details = mb_substr( $details, 0, 2000 );
		}

		$what_broke = isset( $_POST['what_broke'] ) ? sanitize_key( wp_unslash( $_POST['what_broke'] ) ) : '';
		if ( ! self::isWhatBrokeAllowed( $what_broke, $reason ) ) {
			$what_broke = '';
		}

		$payload = array(
			'reason'             => $reason,
			'details'            => $details,
			'what_broke'         => $what_broke,
			'site_url'           => BotBlockerMultisite::getCurrentSiteUrl(),
			'first_activated_at' => (int) BotBlockerMultisite::getOption( self::FIRST_ACTIVATED_OPTION, 0 ),
		);

		BotBlockerBackend::sendToCloud( $payload, 'deactivation_feedback' );

		wp_send_json_success( array( 'stored' => true ) );
	}

	public static function getReasonLabels(): array {
		return array(
			'technical_issue'    => __( 'Technical issues or bugs', 'botblocker-security' ),
			'false_positives'    => __( 'Blocked legitimate traffic (false positives)', 'botblocker-security' ),
			'performance'        => __( 'Site performance issues', 'botblocker-security' ),
			'too_complex'        => __( 'Too complex or hard to configure', 'botblocker-security' ),
			'missing_feature'    => __( 'Missing a feature I need', 'botblocker-security' ),
			'better_alternative' => __( 'Found a better alternative', 'botblocker-security' ),
			'temporary'          => __( 'Temporary - I will turn it back on later', 'botblocker-security' ),
			'other'              => __( 'Other', 'botblocker-security' ),
		);
	}

	public static function getWhatBrokeLabels(): array {
		return array(
			'visitors_blocked'  => __( 'Real visitors were blocked or stuck on a check', 'botblocker-security' ),
			'good_bots_blocked' => __( 'Search engines or other good bots were blocked', 'botblocker-security' ),
			'checkout_forms'    => __( 'Checkout, payments or forms stopped working', 'botblocker-security' ),
			'admin_login'       => __( 'Admin or login access was blocked', 'botblocker-security' ),
			'api_integrations'  => __( 'API, webhooks or integrations failed', 'botblocker-security' ),
			'plugin_conflict'   => __( 'Conflict with another plugin or theme', 'botblocker-security' ),
			'nothing'           => __( 'Nothing broke', 'botblocker-security' ),
			'not_sure'          => __( 'Not sure yet', 'botblocker-security' ),
		);
	}

	public static function getWhatBrokeReasons(): array {
		return self::$what_broke_reasons;
	}

	public static function getShuffledReasonKeys(): array {
		$keys = array();
		foreach ( self::$reason_groups as $group ) {
			shuffle( $group );
			$keys = array_merge( $keys, $group );
		}
		return $keys;
	}

	private static function getReasonKeys(): array {
		return array_merge( ...self::$reason_groups );
	}

	private static function isWhatBrokeAllowed( string $what_broke, string $reason ): bool {
		if ( ! in_array( $reason, self::$followup_reasons, true ) || ! isset( self::$what_broke_reasons[ $what_broke ] ) ) {
			return false;
		}
		$only = self::$what_broke_reasons[ $what_broke ];
		return $only === array() || in_array( $reason, $only, true );
	}
}
