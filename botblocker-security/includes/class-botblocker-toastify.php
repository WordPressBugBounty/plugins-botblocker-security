<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class BBCS_Toastify 
{
	const TYPE_SUCCESS = 'success';
	const TYPE_ERROR   = 'error';
	const TYPE_WARNING = 'warning';
	const TYPE_INFO    = 'info';

	const PAGE_DASHBOARD    = 'dashboard';
	const PAGE_SETTINGS     = 'settings';
	const PAGE_INTEGRATIONS = 'integrations';
	const PAGE_RULES        = 'rules';
	const PAGE_TOOLS        = 'tools';
	const PAGE_REPORTS      = 'reports';
	const PAGE_CLOUD_API    = 'cloud_api';
	const PAGE_SETUP_GUIDE  = 'setup_guide';
	const PAGE_ABOUT        = 'about';
	const PAGE_ADDONS       = 'addons';
	const PAGE_SETUP_WIZARD = 'setup_wizard';

	/**
	 * Pages that actually receive flash messages (have POST-save handlers).
	 *
	 * @var string[]
	 */
	private const FLASHABLE_PAGES = array(
		self::PAGE_SETTINGS,
		self::PAGE_TOOLS,
		self::PAGE_INTEGRATIONS,
		self::PAGE_CLOUD_API,
		self::PAGE_ADDONS,
	);

	/** @var array<int, array{message:string, type:string, duration?:int}> Toast queue for the current request (non-persistent). */
	private static array $queue = array();

	/** @var bool Whether assets have been enqueued. */
	private static bool $assets_enqueued = false;

	/** @var array<string, string> Map internal type codes to Toastify CSS class names. */
	private const TYPE_CSS_MAP = array(
		self::TYPE_SUCCESS => 'toast-success',
		self::TYPE_ERROR   => 'toast-error',
		self::TYPE_WARNING => 'toast-warning',
		self::TYPE_INFO    => 'toast-info',
	);

	private const DEFAULT_DURATION = 6000;

	public static function init(): void {
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_assets' ) );
		add_action( 'admin_print_footer_scripts', array( __CLASS__, 'render_toasts' ), 5 );
	}

	public static function enqueue_assets(): void {
		if ( self::$assets_enqueued ) {
			return;
		}

		$base_url = BOTBLOCKER_URL . 'admin/';

		wp_enqueue_style(
			BOTBLOCKER_SHORT_NAME . '-toastify',
			$base_url . 'css/toastify/toastify.min.css',
			array(),
			BOTBLOCKER_VERSION
		);

		wp_add_inline_style(
			BOTBLOCKER_SHORT_NAME . '-toastify',
			'.bbcs-app .toastify{position:fixed;font-size:15px;font-weight:500;border-radius:10px;padding:14px 24px;'
			. 'box-shadow:0 8px 32px rgba(0,0,0,.22);z-index:999999;opacity:0;'
			. 'transform-origin:top right;transition:none!important;'
			. 'animation:bbcs-toast-in .45s cubic-bezier(.34,1.56,.64,1) both}'
			. '.toastify.toast-success{background:#1e7e34}'
			. '.toastify.toast-error{background:#c82333}'
			. '.toastify.toast-warning{background:#e0a800}'
			. '.toastify.toast-info{background:#138496}'
			. '@keyframes bbcs-toast-in{'
			. '0%{opacity:0;scale:.85}'
			. '55%{opacity:1;scale:1.03}'
			. '80%{scale:.99}'
			. '100%{opacity:1;scale:1}}'
			. '.bbcs-app .toast-close{position:absolute;right:10px;top:50%;transform:translateY(-50%);padding:4px 8px;font-size:16px;line-height:1}'
			. '.bbcs-app .toastify{padding-right:44px}'
			. '@media(max-width:782px){'
			. '.bbcs-app .toastify{font-size:13px;padding:10px 18px;max-width:calc(100% - 32px)}'
			. '}'
		);

		wp_enqueue_script(
			BOTBLOCKER_SHORT_NAME . '-toastify',
			$base_url . 'js/toastify/toastify.min.js',
			array(),
			BOTBLOCKER_VERSION,
			true
		);

		self::$assets_enqueued = true;
	}

	/**
	 * Attach pending toast JS inline to the enqueued toastify script handle.
	 *
	 * Uses wp_add_inline_script so the toast calls are guaranteed to execute
	 * after toastify.min.js has loaded (no race conditions with hook order).
	 *
	 * Hooked to admin_print_footer_scripts at priority 5 - before the default
	 * priority-10 wp_print_footer_scripts prints the script tags.
	 */
	public static function render_toasts(): void {
		if ( ! BotBlockerWpUtility::is_botblocker_page() ) {
			return;
		}

		$toasts = self::collect_all_pending();

		if ( empty( $toasts ) ) {
			return;
		}

		// Deduplicate identical messages (same text + type).
		$seen    = array();
		$deduped = array();
		foreach ( $toasts as $toast ) {
			$key = md5( $toast['message'] . $toast['type'] );
			if ( isset( $seen[ $key ] ) ) {
				continue;
			}
			$seen[ $key ] = true;
			$deduped[]    = $toast;
		}
		$toasts = $deduped;

		$js = '';
		foreach ( $toasts as $toast ) {
			$class_name = self::TYPE_CSS_MAP[ $toast['type'] ] ?? 'toast-info';
			$duration   = (int) ( $toast['duration'] ?? self::DEFAULT_DURATION );
			$message    = wp_json_encode( $toast['message'], JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE );

			$el = 'document.querySelector(\'.bbcs-app\')||document.body';
			$js .= 'Toastify({text:' . $message
				. ',duration:' . $duration
				. ',close:true'
				. ',gravity:"top"'
				. ',position:"right"'
				. ',offset:{y:65}'
				. ',className:"' . $class_name . '"'
				. ',selector:' . $el
				. ',stopOnFocus:true'
				. '}).showToast();';
		}

		if ( $js !== '' ) {
			wp_add_inline_script( BOTBLOCKER_SHORT_NAME . '-toastify', $js );
		}
	}

	/**
	 * Queue a toast for the current request (rendered in footer, no redirect needed).
	 *
	 * @param string $message  Plain text message.
	 * @param string $type     One of the TYPE_* constants.
	 * @param int    $duration Auto-dismiss after this many ms. 0 = default (4 s), -1 = sticky.
	 */
	public static function queue( string $message, string $type = self::TYPE_INFO, int $duration = 0 ): void {
		self::$queue[] = array(
			'message'  => $message,
			'type'     => $type,
			'duration' => $duration > 0 ? $duration : self::DEFAULT_DURATION,
		);
	}

	/**
	 * Store a flash message to be shown after the next page load (POST-REDIRECT-GET).
	 *
	 * Call this in POST handlers before wp_safe_redirect().
	 *
	 * @param string $message Plain text message.
	 * @param string $type    One of the TYPE_* constants.
	 * @param string $page    One of the PAGE_* constants (must be a FLASHABLE_PAGE).
	 */
	public static function flash( string $message, string $type = self::TYPE_SUCCESS, string $page = self::PAGE_SETTINGS ): void {
		$user_id = get_current_user_id();
		$key     = 'bbcs_toast_' . $page . '_' . $user_id;

		$existing = get_transient( $key );
		$pending  = is_array( $existing ) ? $existing : array();

		$pending[] = array(
			'message' => $message,
			'type'    => $type,
		);

		set_transient( $key, $pending, 120 );
	}

	/**
	 * Gather all toasts: queued + pending transients from every flashable page.
	 *
	 * @return array<int, array{message:string, type:string, duration?:int}>
	 */
	private static function collect_all_pending(): array {
		$toasts  = self::$queue;
		$user_id = get_current_user_id();

		foreach ( self::FLASHABLE_PAGES as $page ) {
			$key     = 'bbcs_toast_' . $page . '_' . $user_id;
			$pending = get_transient( $key );

			if ( is_array( $pending ) && ! empty( $pending ) ) {
				foreach ( $pending as $item ) {
					if ( is_array( $item ) && isset( $item['message'], $item['type'] ) ) {
						$toasts[] = array(
							'message'  => $item['message'],
							'type'     => $item['type'],
							'duration' => self::DEFAULT_DURATION,
						);
					}
				}
				delete_transient( $key );
			}
		}

		return $toasts;
	}

	private static function addon_error_message( string $code, ?string $detail = null ): string {
		$map = array(
			'invalid'              => __( 'The add-on is invalid or broken.', 'botblocker-security' ),
			'install_args'         => __( 'Installation arguments are missing.', 'botblocker-security' ),
			'pro_required'         => __( 'Official BotBlocker add-ons require BotBlocker PRO.', 'botblocker-security' ),
			'url_not_allowed'      => __( 'The add-on download URL is not allowed.', 'botblocker-security' ),
			'upload_missing'       => __( 'Choose an add-on ZIP package first.', 'botblocker-security' ),
			'upload_failed'        => __( 'The add-on upload failed.', 'botblocker-security' ),
			'upload_untrusted'     => __( 'The uploaded file was not accepted by WordPress.', 'botblocker-security' ),
			'zip_extension'        => __( 'The add-on package must be a ZIP file.', 'botblocker-security' ),
			'zip_missing'          => __( 'Add-on package is missing or unreadable.', 'botblocker-security' ),
			'zip_too_large'        => __( 'The add-on package is too large.', 'botblocker-security' ),
			'zip_open'             => __( 'The add-on package cannot be opened.', 'botblocker-security' ),
			'zip_file_count'       => __( 'The add-on package has an invalid file count.', 'botblocker-security' ),
			'zip_unsafe_path'      => __( 'The package contains an unsafe file path.', 'botblocker-security' ),
			'zip_entry_too_large'  => __( 'The package contains an oversized file.', 'botblocker-security' ),
			'extract_missing'      => __( 'The temporary extraction folder is missing.', 'botblocker-security' ),
			'package_root'         => __( 'The package must contain exactly one root folder.', 'botblocker-security' ),
			'package_slug'         => __( 'The package root folder must be a valid slug.', 'botblocker-security' ),
			'package_invalid'      => __( 'The package does not match the BotBlocker add-on contract.', 'botblocker-security' ),
			'requires_core_missing' => __( 'The package must declare Requires-Core.', 'botblocker-security' ),
			'slug_mismatch'        => __( 'The package slug does not match the requested add-on.', 'botblocker-security' ),
			'requires_php'         => __( 'This add-on requires a newer PHP version.', 'botblocker-security' ),
			'file_mods_disabled'   => __( 'File modifications are disabled for this WordPress installation.', 'botblocker-security' ),
			'tmp_failed'           => __( 'Failed to create a temporary add-on folder.', 'botblocker-security' ),
			'move_source'          => __( 'The validated add-on source is missing.', 'botblocker-security' ),
			'backup_failed'        => __( 'Failed to backup the existing add-on.', 'botblocker-security' ),
			'move_failed'          => __( 'Failed to install the add-on package.', 'botblocker-security' ),
			'fs_unavailable'       => __( 'Filesystem API is not available.', 'botblocker-security' ),
			'scan_failed'          => __( 'Failed to scan extraction directory.', 'botblocker-security' ),
			'download'             => __( 'Failed to download the add-on package.', 'botblocker-security' ),
		);

		$msg = $map[ $code ] ?? __( 'Operation failed.', 'botblocker-security' );

		if ( $code === 'requires_core' && $detail !== null ) {
			/* translators: %s: required BotBlocker version */
			$msg = sprintf( __( 'This add-on requires BotBlocker %s or higher. Please update the plugin first.', 'botblocker-security' ), $detail );
		} elseif ( $code === 'requires_php' && $detail !== null ) {
			/* translators: %s: required PHP version */
			$msg = sprintf( __( 'This add-on requires PHP %s or higher.', 'botblocker-security' ), $detail );
		} elseif ( $detail !== null && $detail !== '' ) {
			$msg .= ' ' . $detail;
		}

		return $msg;
	}

	public static function flash_addon_error( string $code, ?string $detail = null ): void {
		self::flash( self::addon_error_message( $code, $detail ), self::TYPE_ERROR, self::PAGE_ADDONS );
	}
}
