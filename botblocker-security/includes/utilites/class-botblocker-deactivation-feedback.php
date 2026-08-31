<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BotBlockerDeactivationFeedback {

	public const FIRST_ACTIVATED_OPTION = 'bbcs_first_activated_at';

	private static $reason_keys = array(
		'temporary',
		'too_complex',
		'performance',
		'false_positives',
		'better_alternative',
		'missing_feature',
		'technical_issue',
		'other',
	);

	public static function register(): void {
		add_action( 'admin_enqueue_scripts', array( self::class, 'enqueueAssets' ) );
		add_action( 'admin_footer', array( self::class, 'renderModal' ) );
		add_action( 'wp_ajax_bbcs_store_deactivation_feedback', array( self::class, 'handleStoreFeedback' ) );
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
				'i18n'           => array(
					'title'           => __( 'Before you go', 'botblocker-security' ),
					'intro'           => __( 'Help us improve BotBlocker. Why are you deactivating the plugin?', 'botblocker-security' ),
					'details'         => __( 'Additional details (optional)', 'botblocker-security' ),
					'skip'            => __( 'Skip and deactivate', 'botblocker-security' ),
					'submit'          => __( 'Send and deactivate', 'botblocker-security' ),
					'submitting'      => __( 'Saving...', 'botblocker-security' ),
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
		if ( ! in_array( $reason, self::$reason_keys, true ) ) {
			// Invalid reason: skip sending to cloud, don't error out the deactivation flow.
			wp_send_json_success( array( 'stored' => false ) );
		}

		$details = isset( $_POST['details'] ) ? sanitize_textarea_field( wp_unslash( $_POST['details'] ) ) : '';
		if ( mb_strlen( $details ) > 2000 ) {
			$details = mb_substr( $details, 0, 2000 );
		}

		$payload = array(
			'reason'             => $reason,
			'details'            => $details,
			'site_url'           => BotBlockerMultisite::getCurrentSiteUrl(),
			'first_activated_at' => (int) BotBlockerMultisite::getOption( self::FIRST_ACTIVATED_OPTION, 0 ),
		);

		$cloud = BotBlockerWpRequest::send_to_cloud( $payload, BOTBLOCKER_API_URL, 'deactivation_feedback' );
		if ( $cloud === false ) {
			BotBlockerWpRequest::send_to_cloud( $payload, BOTBLOCKER_API_GS_URL, 'deactivation_feedback' );
		}

		wp_send_json_success( array( 'stored' => true ) );
	}

	private static function getReasonLabels(): array {
		return array(
			'temporary'          => __( 'Temporary - I will turn it back on later', 'botblocker-security' ),
			'too_complex'        => __( 'Too complex or hard to configure', 'botblocker-security' ),
			'performance'        => __( 'Site performance issues', 'botblocker-security' ),
			'false_positives'    => __( 'Blocked legitimate traffic (false positives)', 'botblocker-security' ),
			'better_alternative' => __( 'Found a better alternative', 'botblocker-security' ),
			'missing_feature'    => __( 'Missing a feature I need', 'botblocker-security' ),
			'technical_issue'    => __( 'Technical issues or bugs', 'botblocker-security' ),
			'other'              => __( 'Other', 'botblocker-security' ),
		);
	}
}
