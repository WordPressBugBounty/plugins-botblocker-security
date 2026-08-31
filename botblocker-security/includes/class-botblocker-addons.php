<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BotBlockerAddons {

	/**
	 * @var array<string,bool>
	 */
	private static $loaded_cores = array();

	public static function isLocalMode(): bool {
		$override = apply_filters( 'bbcs_addons_channel', '' );
		if ( self::CHANNEL_LOCAL === $override ) {
			return true;
		}
		if ( in_array( $override, array( self::CHANNEL_DEV, self::CHANNEL_STABLE ), true ) ) {
			return false;
		}
		self::ensureMultisiteLoaded();
		return BotBlockerMultisite::isAddonsLocalMode();
	}

	public static function getActiveOptionName(): string {
		return self::isLocalMode() ? 'bbcs_dev_active_addons' : 'bbcs_active_addons';
	}

	/**
	 * @param array<string,array<string,mixed>> $addons
	 * @return array<int,array<string,mixed>>
	 */
	public static function buildMarketFromDisk( array $addons ): array {
		$market = array();
		foreach ( $addons as $slug => $addon ) {
			if ( empty( $addon['valid'] ) ) {
				continue;
			}
			$slug   = sanitize_key( (string) $slug );
			$market[] = array(
				'name'          => isset( $addon['name'] ) ? (string) $addon['name'] : $slug,
				'slug'          => $slug,
				'version'       => isset( $addon['version'] ) ? (string) $addon['version'] : '',
				'description'   => isset( $addon['description'] ) ? (string) $addon['description'] : '',
				'icon'          => isset( $addon['icon'] ) ? (string) $addon['icon'] : '',
				'requires_core' => isset( $addon['requires_core'] ) ? (string) $addon['requires_core'] : '',
				'url'           => '',
				'enabled'       => true,
			);
		}
		return $market;
	}

	public static function loadCore( array $addon ): void {
		if ( isset( $GLOBALS['bbcs_upgrade_swap_in_progress'] ) && $GLOBALS['bbcs_upgrade_swap_in_progress'] ) {
			return;
		}
		$slug = isset( $addon['slug'] ) ? sanitize_key( (string) $addon['slug'] ) : '';
		$core = ! empty( $addon['core'] ) ? (string) $addon['core'] : '';

		if ( $slug === '' ) {
			// No slug to dedup on; fall back to path-keyed include_once.
			if ( $core !== '' && file_exists( $core ) ) {
				include_once $core;
			}
			return;
		}

		if ( isset( self::$loaded_cores[ $slug ] ) ) {
			return;
		}

		if ( $core !== '' && file_exists( $core ) ) {
			self::$loaded_cores[ $slug ] = true;
			include_once $core;
		}
	}

	public static function rootDir(): string {
		self::ensureMultisiteLoaded();
		return BotBlockerMultisite::getAddonsDir();
	}

	public static function rootUrl(): string {
		self::ensureMultisiteLoaded();
		return BotBlockerMultisite::getAddonsUrl();
	}

	private static function ensureMultisiteLoaded(): void {
		if ( class_exists( 'BotBlockerMultisite' ) ) {
			return;
		}

		$base = dirname( __FILE__ );

		// Load the multisite class (cascades to core-helpers.php).
		$multisite_file = $base . '/class-botblocker-multisite.php';
		if ( file_exists( $multisite_file ) ) {
			require_once $multisite_file;
		}

		// Load upload helpers used by BotBlockerMultisite::getAddonsDir().
		$upload_file = $base . '/class-botblocker-uploads.php';
		if ( file_exists( $upload_file ) && ! class_exists( 'BotBlockerUploads' ) ) {
			require_once $upload_file;
		}
	}

	const CHANNEL_LOCAL  = 'local';
	const CHANNEL_DEV    = 'dev';
	const CHANNEL_STABLE = 'stable';

	const LEDGER_OPTION = 'bbcs_addon_ledger';

	const LEDGER_LOCK_FILE = 'bbcs-ledger-lock';

	const LEDGER_LOCK_HASH_LEN = 12;

	const CEILING_OPTION = 'bbcs_maxcore_deactivated';

	const STATE_OK           = 'ok';
	const STATE_TOO_OLD_CORE = 'too_old_core';
	const STATE_TOO_NEW_CORE = 'too_new_core';

	const FAIL_LIFECYCLE_THROW    = 'lifecycle_throw';
	const FAIL_VERSION_SUSPICIOUS = 'version_suspicious';
	const FAIL_MANIFEST_INVALID   = 'manifest_invalid';
	const FAIL_INSTALLER_MISSING  = 'installer_unavailable';
	const FAIL_DOWNLOAD           = 'download';

	const FAIL_INSTALL            = 'install';

	const SHARED_CLASS_FILES = array(
		'class-botblocker-data-file.php',
		'class-botblocker-mu-path-resolver.php',
		'class-botblocker-mu-geo.php',
	);
	private static $ledger_lock_handle = null;

	public static function getLedger(): array {
		$raw = BotBlockerMultisite::getOption( self::LEDGER_OPTION, array() );
		if ( ! is_array( $raw ) ) {
			$raw = array();
		}

		$addons = ( isset( $raw['addons'] ) && is_array( $raw['addons'] ) ) ? $raw['addons'] : array();
		$clean  = array();
		foreach ( $addons as $slug => $version ) {
			$slug = sanitize_key( (string) $slug );
			if ( $slug !== '' && ( is_string( $version ) || is_numeric( $version ) ) ) {
				$clean[ $slug ] = (string) $version;
			}
		}

		$fingerprint = ( isset( $raw['fingerprint'] ) && is_string( $raw['fingerprint'] ) ) ? $raw['fingerprint'] : '';

		return array(
			'addons'      => $clean,
			'fingerprint' => $fingerprint,
		);
	}

	private static function saveLedger( array $ledger ): bool {
		return BotBlockerMultisite::updateOption( self::LEDGER_OPTION, $ledger, true );
	}

	public static function forgetLedgerSlug( string $slug ): void {
		$slug = sanitize_key( $slug );
		if ( $slug === '' ) {
			return;
		}
		$ledger = self::getLedger();
		if ( ! isset( $ledger['addons'][ $slug ] ) ) {
			return;
		}
		unset( $ledger['addons'][ $slug ] );
		self::saveLedger( $ledger );
	}

	/**
	 * @param array<int,string> $slugs
	 */
	private static function recordCeilingDeactivated( array $slugs ): void {
		$stored = BotBlockerMultisite::getOption( self::CEILING_OPTION, array() );
		if ( ! is_array( $stored ) ) {
			$stored = array();
		}
		foreach ( $slugs as $slug ) {
			$slug = sanitize_key( (string) $slug );
			if ( $slug !== '' && ! in_array( $slug, $stored, true ) ) {
				$stored[] = $slug;
			}
		}
		BotBlockerMultisite::updateOption( self::CEILING_OPTION, $stored );
	}

	/**
	 * @param array<int,string> $slugs
	 */
	private static function forgetCeilingSlug( array $slugs ): void {
		$stored = BotBlockerMultisite::getOption( self::CEILING_OPTION, array() );
		if ( ! is_array( $stored ) ) {
			return;
		}
		$stored = array_values( array_diff( $stored, $slugs ) );
		BotBlockerMultisite::updateOption( self::CEILING_OPTION, $stored );
	}

	/**
	 * Switch broken add-ons off and alert. Site stays up. No retry, no repair.
	 *
	 * @param array<int,string>               $slugs
	 * @param array<int,array<string,string>> $failed
	 */
	public static function panic( array $slugs, array $failed ): void {
		$clean = array();
		foreach ( $slugs as $slug ) {
			$slug = sanitize_key( (string) $slug );
			if ( $slug !== '' ) {
				$clean[] = $slug;
			}
		}
		if ( ! empty( $clean ) ) {
			self::setActive( array_values( array_diff( self::getActive(), $clean ) ) );
		}
		if ( ! empty( $failed ) ) {
			BotBlockerAlerts::setAddonFailed( $failed );
		}
	}

	/**
	 * Layer-1 / boot throw: drop early-init, strip wp-config, keep the site up.
	 */
	public static function panicEarlyInitLayer( \Throwable $e ): void {
		$early_slug = defined( 'BBCS_EARLY_INIT_SLUG' ) ? BBCS_EARLY_INIT_SLUG : 'bbcs-early-init';
		$slugs      = array();
		foreach ( self::getActive() as $slug ) {
			$slug = sanitize_key( (string) $slug );
			if ( $slug === $early_slug ) {
				$slugs[] = $slug;
			}
		}
		if ( class_exists( 'BotBlockerGateway' ) ) {
			foreach ( array_keys( BotBlockerGateway::listByType( 'early_init' ) ) as $gw_slug ) {
				$gw_slug = sanitize_key( (string) $gw_slug );
				if ( $gw_slug !== '' && in_array( $gw_slug, self::getActive(), true ) ) {
					$slugs[] = $gw_slug;
				}
			}
		}
		$slugs = array_values( array_unique( $slugs ) );
		if ( ! empty( $slugs ) ) {
			$failed = array();
			foreach ( $slugs as $slug ) {
				$failed[] = array(
					'name'  => $slug,
					'error' => self::FAIL_LIFECYCLE_THROW,
				);
			}
			self::panic( $slugs, $failed );
		}
		if ( method_exists( 'BotBlockerInstall', 'setEarlyInitEnabled' ) ) {
			try {
				BotBlockerInstall::setEarlyInitEnabled( false, array( 'force_cleanup' => true ) );
			} catch ( \Throwable $ignore ) {
				unset( $ignore );
			}
		}
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- a fatal early-init must always be recorded
		error_log( '[BBCS] [Addons] panicEarlyInitLayer: early-init threw and was switched off: ' . $e->getMessage() );
	}

	/**
	 * Swap failed (download/install WP_Error): drop from active, update-failed alert.
	 *
	 * @param array<int,array<string,string>> $failed
	 */
	public static function failUpdates( array $failed ): void {
		if ( empty( $failed ) ) {
			return;
		}
		$slugs = array();
		foreach ( $failed as $item ) {
			$slug = isset( $item['slug'] ) ? sanitize_key( (string) $item['slug'] ) : '';
			if ( $slug !== '' ) {
				$slugs[] = $slug;
			}
		}
		if ( ! empty( $slugs ) ) {
			self::setActive( array_values( array_diff( self::getActive(), $slugs ) ) );
		}
		BotBlockerAlerts::setAddonUpdateFailed( $failed );
	}

	public static function getLedgerLockPath(): string {
		$hash = substr( md5( (string) get_current_blog_id() . '|' . get_site_url() ), 0, self::LEDGER_LOCK_HASH_LEN );
		return trailingslashit( get_temp_dir() ) . self::LEDGER_LOCK_FILE . '-' . $hash . '.lock';
	}

	private static function claimLedgerLock(): bool {
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fopen -- temporary lock requires native fopen and flock before filesystem abstraction is available
		$handle = @fopen( self::getLedgerLockPath(), 'c' );
		if ( ! $handle ) {
			return true;
		}

		if ( ! flock( $handle, LOCK_EX | LOCK_NB ) ) {
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- release native lock handle
			fclose( $handle );
			return false;
		}

		self::$ledger_lock_handle = $handle;
		return true;
	}

	private static function releaseLedgerLock(): void {
		if ( is_resource( self::$ledger_lock_handle ) ) {
			flock( self::$ledger_lock_handle, LOCK_UN );
			// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fclose -- release native lock handle
			fclose( self::$ledger_lock_handle );
		}
		self::$ledger_lock_handle = null;
	}

	private static function runLedgerMigrations( array $addons, array $loaded ): void {
		$fp_pairs = array();
		foreach ( $loaded as $slug ) {
			if ( isset( $addons[ $slug ] ) ) {
				$fp_pairs[ $slug ] = (string) ( $addons[ $slug ]['version'] ?? '' );
			}
		}
		ksort( $fp_pairs );
		$fingerprint = md5( wp_json_encode( $fp_pairs ) );

		$ledger = self::getLedger();
		if ( $ledger['fingerprint'] === $fingerprint ) {
			return;
		}
		$ledger['fingerprint'] = $fingerprint;

		self::debugLog( 'runLedgerMigrations: start loaded=' . count( $loaded ) );

		$pending     = array();
		$baseline    = array();
		$suspicious  = array();
		foreach ( $loaded as $slug ) {
			if ( ! isset( $addons[ $slug ] ) ) {
				continue;
			}
			$disk = (string) ( $addons[ $slug ]['version'] ?? '' );
			if ( ! isset( $ledger['addons'][ $slug ] ) ) {
				$baseline[ $slug ] = $disk;
				continue;
			}
			$seen = $ledger['addons'][ $slug ];
			if ( version_compare( $disk, $seen, '>' ) ) {
				$pending[ $slug ] = array(
					'from' => $seen,
					'to'   => $disk,
				);
			} elseif ( $seen !== $disk ) {
				$suspicious[] = $slug;
			}
		}

		self::debugLog( 'runLedgerMigrations: pending=' . implode( ',', array_keys( $pending ) ) . ' baseline=' . implode( ',', array_keys( $baseline ) ) . ' suspicious=' . implode( ',', $suspicious ) );

		if ( empty( $pending ) && empty( $baseline ) && empty( $suspicious ) ) {
			self::saveLedger( $ledger );
			return;
		}

		if ( ! self::claimLedgerLock() ) {
			self::debugLog( 'runLedgerMigrations: lock held by another request, pass skipped' );
			return;
		}

		self::debugLog( 'runLedgerMigrations: lock acquired' );

		$failed       = array();
		$failed_slugs = array();

		try {
			foreach ( $baseline as $slug => $version ) {
				self::debugLog( 'runLedgerMigrations: baseline ' . $slug . '=' . $version );
				$ledger['addons'][ $slug ] = $version;
			}
			if ( ! empty( $baseline ) ) {
				self::saveLedger( $ledger );
			}

			foreach ( $pending as $slug => $versions ) {
				try {
					self::debugLog( 'runLedgerMigrations: dispatch update ' . $slug . ' from=' . $versions['from'] . ' to=' . $versions['to'] );
					self::dispatchLifecycle(
						$slug,
						'update',
						$addons[ $slug ],
						array(
							'from' => $versions['from'],
							'to'   => $versions['to'],
						)
					);
					$ledger['addons'][ $slug ] = $versions['to'];
					self::saveLedger( $ledger );
					self::debugLog( 'runLedgerMigrations: advanced ' . $slug . ' to=' . $versions['to'] );
				} catch ( \Throwable $e ) {
					$failed[]       = array(
						'name'  => $addons[ $slug ]['name'] ?: $slug,
						'error' => self::FAIL_LIFECYCLE_THROW,
					);
					$failed_slugs[] = $slug;
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- a failed migration must always be recorded
					error_log( '[BBCS] [Addons] ledger migration of "' . $slug . '" threw and the add-on was switched off: ' . $e->getMessage() );
				}
			}

			foreach ( $suspicious as $slug ) {
				$failed[]       = array(
					'name'  => $addons[ $slug ]['name'] ?: $slug,
					'error' => self::FAIL_VERSION_SUSPICIOUS,
				);
				$failed_slugs[] = $slug;
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- a suspicious version change must always be recorded
				error_log( '[BBCS] [Addons] ledger of "' . $slug . '" saw a non-upgrade version change and the add-on was switched off' );
			}
		} finally {
			self::releaseLedgerLock();
		}

		if ( ! empty( $failed_slugs ) ) {
			self::panic( $failed_slugs, $failed );
		}
	}

	public static function getChannel(): string {
		$override = apply_filters( 'bbcs_addons_channel', '' );
		if ( in_array( $override, array( self::CHANNEL_LOCAL, self::CHANNEL_DEV, self::CHANNEL_STABLE ), true ) ) {
			return $override;
		}

		if ( self::isLocalMode() ) {
			return self::CHANNEL_LOCAL;
		}

		$mode = defined( 'BOTBLOCKER_ADDONS_MODE' ) ? (string) BOTBLOCKER_ADDONS_MODE : self::CHANNEL_STABLE;
		return $mode === self::CHANNEL_DEV ? self::CHANNEL_DEV : self::CHANNEL_STABLE;
	}

	public static function getMarketUrl( string $channel = '' ): string {
		$channel = $channel !== '' ? $channel : self::getChannel();

		if ( $channel === self::CHANNEL_LOCAL ) {
			return '';
		}
		if ( $channel === self::CHANNEL_DEV ) {
			return defined( 'BOTBLOCKER_ADDONS_DEV' ) ? BOTBLOCKER_ADDONS_DEV : '';
		}
		return defined( 'BOTBLOCKER_ADDONS' ) ? BOTBLOCKER_ADDONS : '';
	}

	public static function safeRelativePath( $path ): string {
		static $resolved = array();

		if ( array_key_exists( $path, $resolved ) ) {
			return $resolved[ $path ];
		}

		if ( ! is_string( $path ) || trim( $path ) === '' ) {
			$resolved[ $path ] = '';
			return '';
		}

		$path = str_replace( '\\', '/', trim( $path ) );
		$path = ltrim( $path, '/' );

		if (
			$path === ''
			|| $path === '.'
			|| strpos( $path, "\0" ) !== false
			|| strpos( $path, ':' ) !== false
			|| preg_match( '#(^|/)\.\.(/|$)#', $path )
		) {
			$resolved[ $path ] = '';
			return '';
		}

		$resolved[ $path ] = $path;
		return $path;
	}

	public static function safeSymbolName( $value ): string {

		if ( ! is_string( $value ) ) {
			return '';
		}

		$value = trim( $value );
		if ( $value === '' ) {
			return '';
		}

		return preg_match( '/^[A-Za-z_][A-Za-z0-9_]*$/', $value ) ? $value : '';
	}

	public static function safeCallableName( $value ): string {

		if ( ! is_string( $value ) ) {
			return '';
		}

		$value = trim( $value );
		if ( $value === '' ) {
			return '';
		}

		return preg_match( '/^[A-Za-z_\\\\][A-Za-z0-9_\\\\]*(::[A-Za-z_][A-Za-z0-9_]*)?$/', $value ) ? $value : '';
	}

	public static function absPath( string $base, string $relative ): string {
		static $resolved = array();

		$key = $base . '|' . $relative;
		if ( array_key_exists( $key, $resolved ) ) {
			return $resolved[ $key ];
		}

		$relative = self::safeRelativePath( $relative );
		if ( $relative === '' ) {
			$resolved[ $key ] = '';
			return '';
		}
		$result = trailingslashit( $base ) . str_replace( '/', DIRECTORY_SEPARATOR, $relative );
		$resolved[ $key ] = $result;
		return $result;
	}

	public static function fileUrl( string $slug, string $relative ): string {
		$relative = self::safeRelativePath( $relative );
		if ( $relative === '' ) {
			return '';
		}
		return trailingslashit( self::rootUrl() ) . rawurlencode( $slug ) . '/' . str_replace( '%2F', '/', rawurlencode( $relative ) );
	}

	public static function parseManifest( string $base, string $slug = '' ): array {
		static $cache = array();

		$manifest_file = trailingslashit( $base ) . 'bbcs-addon.json';
		if ( ! file_exists( $manifest_file ) ) {
			return array();
		}

		clearstatcache( true, $manifest_file );
		$mtime = (int) @filemtime( $manifest_file );
		$key   = $manifest_file . '|' . $mtime;

		if ( array_key_exists( $key, $cache ) ) {
			return $cache[ $key ];
		}

		$raw = file_get_contents( $manifest_file );
		if ( ! is_string( $raw ) || trim( $raw ) === '' ) {
			return array();
		}

		$manifest = json_decode( $raw, true );
		if ( ! is_array( $manifest ) ) {
			return array();
		}

		$result = self::normalizeManifest( $manifest, $base, $slug );
		$cache[ $key ] = $result;
		return $result;
	}

	public static function normalizeManifest( array $manifest, string $base, string $folder_slug = '' ): array {

		$declared_slug = isset( $manifest['slug'] ) ? sanitize_key( (string) $manifest['slug'] ) : '';
		$folder_slug   = sanitize_key( $folder_slug );
		$slug          = $declared_slug !== '' ? $declared_slug : $folder_slug;

		$settings     = isset( $manifest['settings'] ) && is_array( $manifest['settings'] ) ? $manifest['settings'] : array();
		$assets       = isset( $manifest['assets'] ) && is_array( $manifest['assets'] ) ? $manifest['assets'] : array();
		$lifecycle    = isset( $manifest['lifecycle'] ) && is_array( $manifest['lifecycle'] ) ? $manifest['lifecycle'] : array();
		$runtime      = isset( $manifest['runtime'] ) && is_array( $manifest['runtime'] ) ? $manifest['runtime'] : array();
		$main_rel     = isset( $manifest['main'] ) ? self::safeRelativePath( $manifest['main'] ) : '';
		$core_rel     = isset( $manifest['core'] ) ? self::safeRelativePath( $manifest['core'] ) : '';
		$settings_rel = isset( $settings['view'] ) ? self::safeRelativePath( $settings['view'] ) : '';
		$icon_rel     = '';
		if ( isset( $assets['icon'] ) ) {
			$icon_rel = self::safeRelativePath( $assets['icon'] );
		} elseif ( isset( $manifest['icon'] ) ) {
			$icon_rel = self::safeRelativePath( $manifest['icon'] );
		}
		$readme_rel = isset( $assets['readme'] ) ? self::safeRelativePath( $assets['readme'] ) : 'readme.txt';

		$root          = $main_rel !== '' ? self::absPath( $base, $main_rel ) : ( $slug !== '' ? trailingslashit( $base ) . $slug . '.php' : '' );
		$core          = $core_rel !== '' ? self::absPath( $base, $core_rel ) : '';
		$settings_path = $settings_rel !== '' ? self::absPath( $base, $settings_rel ) : '';
		$icon_path     = $icon_rel !== '' ? self::absPath( $base, $icon_rel ) : '';
		$readme_path   = $readme_rel !== '' ? self::absPath( $base, $readme_rel ) : '';

		$features = array();
		if ( isset( $manifest['features'] ) && is_array( $manifest['features'] ) ) {
			foreach ( $manifest['features'] as $feature ) {
				$feature = sanitize_key( (string) $feature );
				if ( $feature !== '' ) {
					$features[] = $feature;
				}
			}
		}

		$normalized_lifecycle = array();
		foreach ( array( 'file', 'install', 'activate', 'deactivate', 'delete', 'update', 'load', 'health_check' ) as $key ) {
			if ( ! isset( $lifecycle[ $key ] ) || ! is_string( $lifecycle[ $key ] ) || trim( $lifecycle[ $key ] ) === '' ) {
				continue;
			}
			$normalized_lifecycle[ $key ] = $key === 'file'
					? self::safeRelativePath( $lifecycle[ $key ] )
					: trim( $lifecycle[ $key ] );
		}

		$pre_run_manifest  = isset( $runtime['pre_run'] ) && is_array( $runtime['pre_run'] ) ? $runtime['pre_run'] : array();
		$pre_run_file_rel  = isset( $pre_run_manifest['file'] ) ? self::safeRelativePath( $pre_run_manifest['file'] ) : '';
		$pre_run_file      = $pre_run_file_rel !== '' ? self::absPath( $base, $pre_run_file_rel ) : '';
		$pre_run           = array(
			'enabled'        => ! empty( $pre_run_manifest['enabled'] ),
			'file'           => ( $pre_run_file !== '' && file_exists( $pre_run_file ) ) ? $pre_run_file : '',
			'file_relative'  => $pre_run_file_rel,
			'contract'       => isset( $pre_run_manifest['contract'] ) ? sanitize_key( (string) $pre_run_manifest['contract'] ) : '',
			'ready_constant' => isset( $pre_run_manifest['ready_constant'] ) ? self::safeSymbolName( $pre_run_manifest['ready_constant'] ) : '',
			'ready_callback' => isset( $pre_run_manifest['ready_callback'] ) ? self::safeCallableName( $pre_run_manifest['ready_callback'] ) : '',
			'register'       => isset( $pre_run_manifest['register'] ) ? self::safeCallableName( $pre_run_manifest['register'] ) : '',
		);
		$requires_core     = isset( $manifest['requires_core'] ) ? trim( (string) $manifest['requires_core'] ) : '';
		$requires_php      = isset( $manifest['requires_php'] ) ? trim( (string) $manifest['requires_php'] ) : '';
		$max_core          = isset( $manifest['max_core'] ) ? trim( (string) $manifest['max_core'] ) : '';
		$schema            = isset( $manifest['schema'] ) ? trim( (string) $manifest['schema'] ) : '2.0';
		$settings_option   = isset( $settings['option'] ) ? sanitize_key( (string) $settings['option'] ) : '';
		$settings_sanitize = isset( $settings['sanitize'] ) && is_string( $settings['sanitize'] ) ? trim( $settings['sanitize'] ) : '';
		$name              = isset( $manifest['name'] ) ? trim( (string) $manifest['name'] ) : $slug;

		$gateway = self::normalizeGateway( $manifest );
		$ui      = self::normalizeUi( $manifest, $name );
		$storage = self::normalizeStorage( $manifest );

		$valid = $slug !== ''
			&& $slug === sanitize_key( $slug )
			&& ( $folder_slug === '' || $folder_slug === $slug )
			&& $schema !== ''
			&& trim( (string) ( $manifest['name'] ?? '' ) ) !== ''
			&& trim( (string) ( $manifest['version'] ?? '' ) ) !== ''
			&& $requires_core !== ''
			&& $core !== ''
			&& file_exists( $core );

		if ( $valid && $requires_php !== '' && version_compare( PHP_VERSION, $requires_php, '<' ) ) {
			$valid = false;
		}

		return array(
			'slug'              => $slug,
			'base'              => trailingslashit( $base ),
			'root'              => $root,
			'core'              => $core,
			'settings'          => $settings_path,
			'icon'              => ( $icon_rel !== '' && file_exists( $icon_path ) ) ? self::fileUrl( $slug, $icon_rel ) : '',
			'valid'             => $valid,
			'name'              => $name,
			'author'            => isset( $manifest['author'] ) ? trim( (string) $manifest['author'] ) : '',
			'description'       => isset( $manifest['description'] ) ? trim( (string) $manifest['description'] ) : '',
			'version'           => isset( $manifest['version'] ) ? trim( (string) $manifest['version'] ) : '',
			'requires_core'     => $requires_core,
			'requires_php'      => $requires_php,
			'max_core'          => $max_core,
			'schema'            => $schema,
			'source_format'     => 'v2',
			'has_settings'      => $settings_path !== '' && file_exists( $settings_path ),
			'settings_option'   => $settings_option,
			'settings_sanitize' => $settings_sanitize,
			'lifecycle'         => $normalized_lifecycle,
			'pre_run'           => $pre_run,
			'features'          => array_values( array_unique( $features ) ),
			'manifest'          => trailingslashit( $base ) . 'bbcs-addon.json',
			'readme'            => ( $readme_path !== '' && file_exists( $readme_path ) ) ? $readme_path : '',
			'gateway'           => $gateway,
			'ui'                => $ui,
			'storage'           => $storage,
			'captcha_modes'     => self::normalizeCaptcha( $manifest, $base ),
		);
	}

	private static function normalizeCaptcha( array $manifest, string $base ): array {
		$raw   = isset( $manifest['captcha']['modes'] ) && is_array( $manifest['captcha']['modes'] ) ? $manifest['captcha']['modes'] : array();
		$modes = array();

		foreach ( $raw as $mode_cfg ) {
			if ( ! is_array( $mode_cfg ) ) {
				continue;
			}
			if ( ! isset( $mode_cfg['id'] ) || ! is_numeric( $mode_cfg['id'] ) || (int) $mode_cfg['id'] < 90 ) {
				self::debugLog( 'normalizeCaptcha: dropped mode without valid id (>= 90 required)' );
				continue;
			}
			$id = (int) $mode_cfg['id'];
			if ( isset( $modes[ $id ] ) ) {
				self::debugLog( 'normalizeCaptcha: duplicate id ' . $id . ' within manifest' );
				continue;
			}
			$name = isset( $mode_cfg['name'] ) ? trim( (string) $mode_cfg['name'] ) : '';
			if ( '' === $name ) {
				self::debugLog( 'normalizeCaptcha: dropped mode ' . $id . ' with empty name' );
				continue;
			}
			$params_callback = isset( $mode_cfg['params_callback'] ) ? self::safeCallableName( $mode_cfg['params_callback'] ) : '';
			$verify_callback = isset( $mode_cfg['verify_callback'] ) ? self::safeCallableName( $mode_cfg['verify_callback'] ) : '';
			if ( '' === $params_callback || '' === $verify_callback ) {
				self::debugLog( 'normalizeCaptcha: dropped mode ' . $id . ' with invalid callback name' );
				continue;
			}
			$keys_callback = '';
			if ( isset( $mode_cfg['keys_callback'] ) ) {
				$keys_callback = self::safeCallableName( $mode_cfg['keys_callback'] );
				if ( '' === $keys_callback ) {
					self::debugLog( 'normalizeCaptcha: dropped mode ' . $id . ' with invalid keys_callback' );
					continue;
				}
			}
			$js_relative = isset( $mode_cfg['assets']['js'] ) ? self::safeRelativePath( $mode_cfg['assets']['js'] ) : '';
			$js          = '' !== $js_relative ? self::absPath( $base, $js_relative ) : '';
			if ( '' === $js || ! file_exists( $js ) ) {
				self::debugLog( 'normalizeCaptcha: dropped mode ' . $id . ' with missing js file' );
				continue;
			}

			$external = array();
			if ( isset( $mode_cfg['assets']['external'] ) && is_array( $mode_cfg['assets']['external'] ) ) {
				foreach ( $mode_cfg['assets']['external'] as $external_url ) {
					$safe_url = esc_url_raw( (string) $external_url );
					if ( '' !== $safe_url && strpos( $safe_url, 'https://' ) === 0 ) {
						$external[] = $safe_url;
					}
				}
			}

			$wizard_icon     = '';
			$wizard_subtitle = '';
			if ( isset( $mode_cfg['wizard'] ) && is_array( $mode_cfg['wizard'] ) ) {
				$wizard_icon_rel = isset( $mode_cfg['wizard']['icon'] ) ? self::safeRelativePath( $mode_cfg['wizard']['icon'] ) : '';
				$wizard_icon_abs = '' !== $wizard_icon_rel ? self::absPath( $base, $wizard_icon_rel ) : '';
				if ( '' !== $wizard_icon_abs && file_exists( $wizard_icon_abs ) ) {
					$wizard_icon = $wizard_icon_rel;
				}
				$wizard_subtitle = isset( $mode_cfg['wizard']['subtitle'] ) ? sanitize_text_field( (string) $mode_cfg['wizard']['subtitle'] ) : '';
			}

			$modes[ $id ] = array(
				'id'               => $id,
				'name'             => $name,
				'params_callback'  => $params_callback,
				'verify_callback'  => $verify_callback,
				'keys_callback'    => $keys_callback,
				'js_relative'      => $js_relative,
				'js'               => $js,
				'external'         => $external,
				'wizard_icon'      => $wizard_icon,
				'wizard_subtitle'  => $wizard_subtitle,
			);
		}

		return array_values( $modes );
	}

	private static function normalizeGateway( array $manifest ): array {
		$raw     = isset( $manifest['gateway'] ) && is_array( $manifest['gateway'] ) ? $manifest['gateway'] : array();
		$gateway = array();

		if ( isset( $raw['early_init'] ) && is_array( $raw['early_init'] ) ) {
			$ei = $raw['early_init'];
			$gateway['early_init'] = array(
				'router_file'        => isset( $ei['router_file'] ) ? self::safeRelativePath( $ei['router_file'] ) : '',
				'entry_file'         => isset( $ei['entry_file'] ) ? self::safeRelativePath( $ei['entry_file'] ) : '',
				'entry_class'        => isset( $ei['entry_class'] ) ? trim( (string) $ei['entry_class'] ) : '',
				'deploy_target'      => isset( $ei['deploy_target'] ) ? sanitize_key( (string) $ei['deploy_target'] ) : '',
				'wp_config_block'    => ! empty( $ei['wp_config_block'] ),
				'consistency_check'  => isset( $ei['consistency_check'] ) ? self::safeCallableName( $ei['consistency_check'] ) : '',
				'data_file_probe'    => isset( $ei['data_file_probe'] ) ? self::safeRelativePath( $ei['data_file_probe'] ) : '',
			);
		}

		if ( isset( $raw['mu_plugin'] ) && is_array( $raw['mu_plugin'] ) ) {
			$mp = $raw['mu_plugin'];
			$gateway['mu_plugin'] = array(
				'source_file'      => isset( $mp['source_file'] ) ? self::safeRelativePath( $mp['source_file'] ) : '',
				'target_filename'  => isset( $mp['target_filename'] ) ? trim( (string) $mp['target_filename'] ) : '',
				'auto_deploy'      => ! empty( $mp['auto_deploy'] ),
			);
		}

		return $gateway;
	}

	private static function normalizeUi( array $manifest, string $name ): array {
		$raw = isset( $manifest['ui'] ) && is_array( $manifest['ui'] ) ? $manifest['ui'] : array();
		$ui  = array();

		$palette = isset( $raw['palette'] ) && is_array( $raw['palette'] ) ? $raw['palette'] : array();
		$ui['palette'] = array(
			'icon'     => isset( $palette['icon'] ) ? sanitize_key( (string) $palette['icon'] ) : 'puzzle',
			'title'    => isset( $palette['title'] ) ? trim( (string) $palette['title'] ) : $name,
			'priority' => isset( $palette['priority'] ) && is_numeric( $palette['priority'] ) ? (int) $palette['priority'] : 50,
		);

		$ui['settings_sections'] = array();
		if ( isset( $raw['settings_sections'] ) && is_array( $raw['settings_sections'] ) ) {
			foreach ( $raw['settings_sections'] as $section_name => $section_cfg ) {
				$section_name = sanitize_key( (string) $section_name );
				if ( $section_name === '' || ! is_array( $section_cfg ) ) {
					continue;
				}
				$ui['settings_sections'][ $section_name ] = array(
					'callback' => isset( $section_cfg['callback'] ) ? self::safeCallableName( $section_cfg['callback'] ) : '',
				);
			}
		}

		return $ui;
	}

	private static function normalizeStorage( array $manifest ): array {
		$raw     = isset( $manifest['storage'] ) && is_array( $manifest['storage'] ) ? $manifest['storage'] : array();
		$storage = array(
			'cache_dirs'          => array(),
			'cleanup_on_uninstall' => false,
		);

		if ( isset( $raw['cache_dirs'] ) && is_array( $raw['cache_dirs'] ) ) {
			foreach ( $raw['cache_dirs'] as $dir ) {
				$dir = self::safeRelativePath( $dir );
				if ( $dir !== '' ) {
					$storage['cache_dirs'][] = $dir;
				}
			}
		}

		if ( isset( $raw['cleanup_on_uninstall'] ) ) {
			$storage['cleanup_on_uninstall'] = ! empty( $raw['cleanup_on_uninstall'] );
		}

		return $storage;
	}

	public static function scanLegacy( string $base, string $slug ): array {
		$root     = trailingslashit( $base ) . $slug . '.php';
		$core     = trailingslashit( $base ) . 'inc' . DIRECTORY_SEPARATOR . $slug . '-core.php';
		$settings = trailingslashit( $base ) . 'inc' . DIRECTORY_SEPARATOR . $slug . '-settings.php';
		$iconSvg  = trailingslashit( $base ) . $slug . '.svg';
		$iconPng  = trailingslashit( $base ) . $slug . '.png';
		$readme   = trailingslashit( $base ) . 'readme.txt';
		$iconPath = file_exists( $iconSvg ) ? $iconSvg : ( file_exists( $iconPng ) ? $iconPng : '' );
		$iconUrl  = $iconPath ? self::rootUrl() . $slug . '/' . basename( $iconPath ) : '';
		$valid    = file_exists( $root ) && file_exists( $core ) && file_exists( $settings ) && $iconPath && file_exists( $readme );
		$headers  = array(
			'Name'         => 'Plugin Name',
			'Author'       => 'Author',
			'Description'  => 'Description',
			'Version'      => 'Version',
			'RequiresCore' => 'Requires-Core',
		);
		$meta     = file_exists( $root ) ? get_file_data( $root, $headers ) : array(
			'Name'         => '',
			'Author'       => '',
			'Description'  => '',
			'Version'      => '',
			'RequiresCore' => '',
		);

		return array(
			'slug'              => $slug,
			'base'              => trailingslashit( $base ),
			'root'              => $root,
			'core'              => $core,
			'settings'          => $settings,
			'icon'              => $iconUrl,
			'valid'             => $valid,
			'name'              => isset( $meta['Name'] ) ? $meta['Name'] : $slug,
			'author'            => isset( $meta['Author'] ) ? $meta['Author'] : '',
			'description'       => isset( $meta['Description'] ) ? $meta['Description'] : '',
			'version'           => isset( $meta['Version'] ) ? $meta['Version'] : '',
			'requires_core'     => isset( $meta['RequiresCore'] ) ? $meta['RequiresCore'] : '',
			'requires_php'      => '',
			'schema'            => '1.0',
			'source_format'     => 'v1',
			'has_settings'      => file_exists( $settings ),
			'settings_option'   => '',
			'settings_sanitize' => '',
			'lifecycle'         => array(),
			'features'          => array(),
			'manifest'          => '',
			'readme'            => file_exists( $readme ) ? $readme : '',
			'gateway'           => array(),
			'ui'                => array(
				'palette' => array(
					'icon'     => 'puzzle',
					'title'    => isset( $meta['Name'] ) ? $meta['Name'] : $slug,
					'priority' => 50,
				),
				'settings_sections' => array(),
			),
			'storage'           => array(
				'cache_dirs'          => array(),
				'cleanup_on_uninstall' => false,
			),
		);
	}

	/**
	 * @var array<string,array>|null
	 */
	private static $scanCache = null;

	public static function scanAll(): array {
		try {
			return self::scanAllUnchecked();
		} catch ( \Throwable $e ) {
			self::panicEarlyInitLayer( $e );
			try {
				return self::scanAllRaw();
			} catch ( \Throwable $e2 ) {
				unset( $e2 );
				return array();
			}
		}
	}

	private static function scanAllUnchecked(): array {
		if ( defined( 'WP_TESTS_DOMAIN' ) ) {
			return self::scanAllRaw();
		}

		if ( self::$scanCache !== null ) {
			return self::$scanCache;
		}

		if ( is_admin() ) {
			self::$scanCache = self::scanAllRaw();
			BotBlockerFileRenderer::renderAddons();
			return self::$scanCache;
		}

		$dataFile = BotBlockerMultisite::getDataDir() . 'addons.php';
		if ( file_exists( $dataFile ) ) {
			$loaded = BotBlockerDataFile::safeLoad( $dataFile );
			if ( is_array( $loaded ) ) {
				self::$scanCache = $loaded;
				return self::$scanCache;
			}
		}

		self::$scanCache = self::scanAllRaw();
		return self::$scanCache;
	}

	public static function scanAllRaw(): array {
		$dir = self::rootDir();
		if ( ! is_dir( $dir ) ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [Addons] scanAll: addons directory not found: ' . $dir );
			}
			return array();
		}
		$entries = array_diff( scandir( $dir ), array( '.', '..' ) );
		$addons  = array();
		foreach ( $entries as $entry ) {
			if ( preg_match( '/_bbcs_bak$/', $entry ) ) {
				continue;
			}
			$slug = sanitize_key( $entry );
			$base = $dir . $slug . DIRECTORY_SEPARATOR;
			if ( ! is_dir( $base ) ) {
				continue;
			}
			$manifest        = self::parseManifest( $base, $slug );
			$addons[ $slug ] = ! empty( $manifest ) ? $manifest : self::scanLegacy( $base, $slug );
		}
		return $addons;
	}

	/**
	 * @return array<int,string>
	 */
	public static function getActive(): array {
		self::ensureMultisiteLoaded();
		$active = BotBlockerMultisite::getOption( self::getActiveOptionName(), array() );
		return is_array( $active ) ? array_values( $active ) : array();
	}

	/**
	 * @param array<int,string> $active
	 */
	public static function setActive( array $active ): void {
		self::ensureMultisiteLoaded();
		$previous = self::getActive();
		BotBlockerMultisite::updateOption( self::getActiveOptionName(), array_values( $active ) );
		// A captcha provider that just left the active list must not keep
		// owning the selected captcha mode (deactivate, delete, panic, bulk).
		foreach ( array_diff( $previous, array_values( $active ) ) as $removed_slug ) {
			self::maybeResetCaptchaModeFor( (string) $removed_slug );
		}
	}

	public static function maybeResetCaptchaModeFor( string $slug ): void {
		$slug = sanitize_key( $slug );
		if ( '' === $slug ) {
			return;
		}
		try {
			global $wpdb;
			if ( ! ( isset( $wpdb ) && property_exists( $wpdb, 'bbcs_settings' ) && $wpdb->bbcs_settings ) ) {
				return;
			}
			if ( ! class_exists( 'BotBlockerSettingsHooks' ) ) {
				require_once BOTBLOCKER_DIR . 'includes/hook/class-botblocker-settings-hooks.php';
			}
			BotBlockerSettingsHooks::handleAddonCaptchaFallback( $slug, false );
		} catch ( \Throwable $e ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [Addons] captcha mode reset failed for slug ' . $slug . ': ' . $e->getMessage() );
			}
		}
	}

	public static function isActive( string $slug ): bool {
		$slug = sanitize_key( $slug );
		return in_array( $slug, self::getActive(), true );
	}

	/**
	 * Three-state compatibility: ok | too_old_core | too_new_core.
	 * too_old_core: core_version < requires_core (or requires_core missing).
	 * too_new_core: max_core set and core_version > max_core.
	 */
	public static function compatibilityState( array $addon, string $core_version = '' ): string {
		$requires_core = isset( $addon['requires_core'] ) ? (string) $addon['requires_core'] : '';
		$max_core      = isset( $addon['max_core'] ) ? (string) $addon['max_core'] : '';
		$version       = $core_version !== '' ? $core_version : BOTBLOCKER_VERSION;
		static $stateCache = array();
		$cache_key = $requires_core . '|' . $max_core . '|' . $version;
		if ( array_key_exists( $cache_key, $stateCache ) ) {
			return $stateCache[ $cache_key ];
		}
		if ( $requires_core === '' || version_compare( $version, $requires_core, '<' ) ) {
			$stateCache[ $cache_key ] = self::STATE_TOO_OLD_CORE;
			return self::STATE_TOO_OLD_CORE;
		}
		if ( $max_core !== '' && version_compare( $version, $max_core, '>' ) ) {
			$stateCache[ $cache_key ] = self::STATE_TOO_NEW_CORE;
			return self::STATE_TOO_NEW_CORE;
		}
		$stateCache[ $cache_key ] = self::STATE_OK;
		return self::STATE_OK;
	}

	public static function isCompatible( array $addon, string $core_version = '' ): bool {
		return self::compatibilityState( $addon, $core_version ) === self::STATE_OK;
	}

	public static function fileRequiresCore( string $slug ): string {
		$slug     = sanitize_key( $slug );
		$base     = trailingslashit( self::rootDir() ) . $slug . DIRECTORY_SEPARATOR;
		$manifest = self::parseManifest( $base, $slug );
		if ( ! empty( $manifest ) ) {
			return $manifest['requires_core'] ?? '';
		}

		$root = $base . $slug . '.php';
		if ( ! file_exists( $root ) ) {
			return '';
		}
		$meta = get_file_data( $root, array( 'RequiresCore' => 'Requires-Core' ) );
		return $meta['RequiresCore'] ?? '';
	}

	public static function includeLifecycleFile( array $addon ): void {
		if ( isset( $GLOBALS['bbcs_upgrade_swap_in_progress'] ) && $GLOBALS['bbcs_upgrade_swap_in_progress'] ) {
			return;
		}
		static $included = array();

		$lifecycle = isset( $addon['lifecycle'] ) && is_array( $addon['lifecycle'] ) ? $addon['lifecycle'] : array();
		if ( empty( $lifecycle['file'] ) ) {
			return;
		}

		$path = self::absPath( $addon['base'] ?? '', $lifecycle['file'] );
		if ( $path === '' || array_key_exists( $path, $included ) ) {
			return;
		}

		if ( file_exists( $path ) ) {
			$included[ $path ] = true;
			include_once $path;
		}
	}

	public static function dispatchLifecycle( string $slug, string $event, array $addon, array $context = array() ): void {
		$slug  = sanitize_key( $slug );
		$event = sanitize_key( $event );
		if ( $slug === '' || $event === '' ) {
			return;
		}

		self::includeLifecycleFile( $addon );

		$callback = '';
		if ( isset( $addon['lifecycle'] ) && is_array( $addon['lifecycle'] ) && ! empty( $addon['lifecycle'][ $event ] ) ) {
			$callback = (string) $addon['lifecycle'][ $event ];
		}

		if ( $callback !== '' && ! is_callable( $callback ) && ! empty( $addon['core'] ) ) {
			self::loadCore( $addon );
		}

		if ( $callback !== '' && is_callable( $callback ) ) {
			if ( function_exists( $callback ) && ( new \ReflectionFunction( $callback ) )->isInternal() ) {
			} else {
				call_user_func( $callback, $addon, $context, $event, $slug );
			}
		}

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG && in_array( $event, array( 'install', 'activate', 'deactivate', 'delete', 'update' ), true ) ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [Addons] Lifecycle: slug=' . $slug . ' event=' . $event );
		}

		do_action( 'bbcs_addon_lifecycle', $event, $slug, $addon, $context );
		do_action( "bbcs_addon_{$event}", $slug, $addon, $context );
		do_action( "bbcs_addon_{$slug}_{$event}", $addon, $context );
	}

	public static function getActiveFeatures(): array {
		$addons   = self::scanAll();
		$features = array();
		foreach ( self::getActive() as $slug ) {
			if ( ! isset( $addons[ $slug ] ) || empty( $addons[ $slug ]['valid'] ) || ! self::isCompatible( $addons[ $slug ] ) ) {
				continue;
			}
			foreach ( ( $addons[ $slug ]['features'] ?? array() ) as $feature ) {
				$feature = sanitize_key( (string) $feature );
				if ( $feature !== '' ) {
					$features[] = $feature;
				}
			}
		}
		return array_values( array_unique( $features ) );
	}

	public static function declaresFeature( array $addon, string $feature ): bool {
		$feature = sanitize_key( $feature );
		if ( $feature === '' || empty( $addon['features'] ) || ! is_array( $addon['features'] ) ) {
			return false;
		}
		return in_array( $feature, array_map( 'sanitize_key', $addon['features'] ), true );
	}

	public static function hasActiveFeature( string $feature ): bool {
		return in_array( sanitize_key( $feature ), self::getActiveFeatures(), true );
	}

	public static function hasActiveProvider( string $feature, string $legacy_filter = '' ): bool {
		if ( self::hasActiveFeature( $feature ) ) {
			return true;
		}

		if ( $legacy_filter !== '' && strpos( $legacy_filter, 'bbcs_' ) === 0 ) {
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound
			return (bool) apply_filters( $legacy_filter, false );
		}

		return false;
	}

	public static function getByFeature( string $feature ): array {

		$feature = sanitize_key( $feature );
		$addons  = self::scanAll();
		$active  = self::getActive();
		$matches = array();
		foreach ( $addons as $slug => $addon ) {
			if ( ! in_array( $slug, $active, true ) || empty( $addon['valid'] ) || ! self::isCompatible( $addon ) ) {
				continue;
			}
			if ( in_array( $feature, $addon['features'] ?? array(), true ) ) {
				$matches[ $slug ] = $addon;
			}
		}
		return $matches;
	}

	public static function registerTrafficDecisionProvider( string $slug, $callback, int $priority = 10 ): bool {
		if ( ! class_exists( 'BotBlockerTrafficDecisions' ) ) {
			require_once BOTBLOCKER_DIR . 'includes/class-botblocker-traffic-decisions.php';
		}
		return BotBlockerTrafficDecisions::register( $slug, $callback, $priority );
	}

	public static function getTrafficDecisionProviders(): array {
		if ( ! class_exists( 'BotBlockerTrafficDecisions' ) ) {
			require_once BOTBLOCKER_DIR . 'includes/class-botblocker-traffic-decisions.php';
		}
		return BotBlockerTrafficDecisions::getAll();
	}

	public static function isPreRunMarkerReady( array $pre_run, array $addon, string $slug ): bool {

		$ready_constant = $pre_run['ready_constant'] ?? '';
		if ( $ready_constant !== '' && defined( $ready_constant ) && constant( $ready_constant ) ) {
			return true;
		}

		$ready_callback = $pre_run['ready_callback'] ?? '';
		if ( $ready_callback !== '' && is_callable( $ready_callback ) ) {
			return (bool) call_user_func( $ready_callback, $addon, array( 'phase' => 'pre_run' ), 'pre_run', $slug );
		}

		return false;
	}

	public static function includePreRunAddons(): array {

		if ( isset( $GLOBALS['bbcs_upgrade_swap_in_progress'] ) && $GLOBALS['bbcs_upgrade_swap_in_progress'] ) {
			return array();
		}

		if ( ! class_exists( 'BotBlockerTrafficDecisions' ) ) {
			require_once BOTBLOCKER_DIR . 'includes/class-botblocker-traffic-decisions.php';
		}

		$addons = self::scanAll();
		$active = self::getActive();
		$loaded = array();
		if ( ! isset( $GLOBALS['bbcs_pre_run_addons_loaded'] ) || ! is_array( $GLOBALS['bbcs_pre_run_addons_loaded'] ) ) {
			$GLOBALS['bbcs_pre_run_addons_loaded'] = array();
			BotBlockerTrafficDecisions::reset();
		}

		foreach ( $active as $slug ) {
			$slug = sanitize_key( (string) $slug );
			if ( $slug === '' || isset( $GLOBALS['bbcs_pre_run_addons_loaded'][ $slug ] ) ) {
				continue;
			}

			if ( ! isset( $addons[ $slug ] ) || empty( $addons[ $slug ]['valid'] ) || ( $addons[ $slug ]['source_format'] ?? '' ) !== 'v2' || ! self::isCompatible( $addons[ $slug ] ) || ! self::declaresFeature( $addons[ $slug ], 'traffic_decision_provider' ) ) {
				continue;
			}

			$pre_run = isset( $addons[ $slug ]['pre_run'] ) && is_array( $addons[ $slug ]['pre_run'] ) ? $addons[ $slug ]['pre_run'] : array();
			if ( empty( $pre_run['enabled'] ) || empty( $pre_run['file'] ) || ( $pre_run['contract'] ?? '' ) !== 'traffic_decision_provider' || empty( $pre_run['register'] ) || ( empty( $pre_run['ready_constant'] ) && empty( $pre_run['ready_callback'] ) ) ) {
				continue;
			}

			try {
				include_once $pre_run['file'];
				if ( ! self::isPreRunMarkerReady( $pre_run, $addons[ $slug ], $slug ) ) {
					continue;
				}

				if ( ! is_callable( $pre_run['register'] ) ) {
					continue;
				}

				call_user_func( $pre_run['register'], $addons[ $slug ], array( 'phase' => 'pre_run' ), 'pre_run', $slug );
				$GLOBALS['bbcs_pre_run_addons_loaded'][ $slug ] = true;
				$loaded[]                                       = $slug;
				do_action( 'bbcs_addon_pre_run_loaded', $slug, $addons[ $slug ] );
			} catch ( \Throwable $e ) {
				self::panic(
					array( $slug ),
					array(
					array(
						'name'  => $addons[ $slug ]['name'] ?: $slug,
						'error' => self::FAIL_LIFECYCLE_THROW,
					),
					)
				);
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- a fatal add-on must always be recorded
				error_log( '[BBCS] [Addons] includePreRunAddons: add-on "' . $slug . '" threw and was switched off: ' . $e->getMessage() );
			}
		}

		// Captcha callbacks live in addon core files; load them pre-run so the
		// check page (rendered inside run()) and the POST verify can call them.
		self::preRunLoadCaptchaAddonCores( $addons, $active );
		self::registerCaptchaModes( $addons, $active );

		return $loaded;
	}

	public static function registerCaptchaModes( array $addons, $active = null ): void {
		if ( ! class_exists( 'BotBlockerCaptchaRegistry' ) ) {
			require_once BOTBLOCKER_DIR . 'includes/class-botblocker-captcha-registry.php';
		}
		BotBlockerCaptchaRegistry::reset();

		// Iterate ACTIVE addons in activation order (getActive()) — the spec
		// §6.1 order: the first ACTIVATED addon to claim an id wins a collision.
		$active_slugs = is_array( $active ) ? array_values( $active ) : self::getActive();
		foreach ( $active_slugs as $slug ) {
			$slug = sanitize_key( (string) $slug );
			if ( $slug === '' || ! isset( $addons[ $slug ] ) || ! is_array( $addons[ $slug ] ) ) {
				continue; // ACTIVE addons only — an inactive provider must never verify
			}
			$configs = isset( $addons[ $slug ]['captcha_modes'] ) && is_array( $addons[ $slug ]['captcha_modes'] ) ? $addons[ $slug ]['captcha_modes'] : array();
			foreach ( $configs as $config ) {
				BotBlockerCaptchaRegistry::register(
					(int) $config['id'],
					(string) $slug,
					(string) $config['name'],
					$config
				);
			}
		}
	}

	private static function preRunLoadCaptchaAddonCores( array $addons, array $active ): void {
		$active_flip = array_flip( array_values( $active ) );
		foreach ( $addons as $slug => $addon ) {
			if ( ! isset( $active_flip[ $slug ] ) || ! is_array( $addon ) ) {
				continue;
			}
			if ( empty( $addon['valid'] ) || empty( $addon['captcha_modes'] ) || ! self::isCompatible( $addon ) ) {
				continue;
			}
			try {
				self::loadCore( $addon );
			} catch ( \Throwable $e ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- a fatal add-on must always be recorded
				error_log( '[BBCS] [Addons] includePreRunAddons: captcha add-on "' . $slug . '" core threw: ' . $e->getMessage() );
			}
		}
	}

	public static function registerGatewayConfigs( array $addons ): void {
		if ( ! class_exists( 'BotBlockerGateway' ) ) {
			require_once BOTBLOCKER_DIR . 'includes/class-botblocker-gateway.php';
		}

		foreach ( $addons as $slug => $addon ) {
			$gateway = $addon['gateway'] ?? array();
			if ( ! is_array( $gateway ) || empty( $gateway ) ) {
				continue;
			}

			foreach ( $gateway as $type => $config ) {
				if ( ! is_array( $config ) ) {
					continue;
				}
				BotBlockerGateway::register( $slug, $type, $config );
			}
		}
	}

	private static function restoreGatewayStates(): void {
		if ( ! class_exists( 'BotBlockerGateway' ) ) {
			require_once BOTBLOCKER_DIR . 'includes/class-botblocker-gateway.php';
		}

		global $wpdb;
		if ( ! isset( $wpdb->bbcs_settings ) ) {
			return;
		}

		$gateway_settings = array(
			'early_init_enable' => 'early_init',
			'mu_enable'         => 'mu_plugin',
		);

		foreach ( $gateway_settings as $db_key => $gateway_type ) {
			if ( BotBlockerGateway::isEnabled( $gateway_type ) !== null ) {
				continue;
			}

			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$enabled = (int) $wpdb->get_var(
				$wpdb->prepare( "SELECT value FROM {$wpdb->bbcs_settings} WHERE `key` = %s", $db_key )
			);

			if ( 1 !== $enabled ) {
				continue;
			}

			$slug = BotBlockerGateway::firstSlug( $gateway_type );
			if ( $slug !== '' ) {
				BotBlockerGateway::restoreState( $gateway_type, $slug );
				if ( $gateway_type === 'mu_plugin' && self::isActive( $slug ) ) {
					$config = BotBlockerGateway::getConfig( $slug, 'mu_plugin' );
					if ( ! empty( $config['auto_deploy'] ) ) {
						do_action( 'bbcs_gateway_mu_plugin_restored', $slug, $config );
					}
				}
			}
		}
	}

	public static function sanitizeSettingsValue( $value ) {

		if ( is_array( $value ) ) {
				$clean = array();
			foreach ( $value as $key => $item ) {
				$clean[ sanitize_key( (string) $key ) ] = self::sanitizeSettingsValue( $item );
			}
				return $clean;
		}
		if ( is_bool( $value ) ) {
			return $value ? 1 : 0;
		}
		if ( is_numeric( $value ) ) {
			return sanitize_text_field( (string) $value );
		}
			return sanitize_textarea_field( (string) $value );
	}

	public static function saveSettingsFromPost( array $post ): void {
		$addons = self::scanAll();
		foreach ( self::getActive() as $slug ) {
			if ( ! isset( $addons[ $slug ] ) || empty( $addons[ $slug ]['valid'] ) || empty( $addons[ $slug ]['settings_option'] ) ) {
				continue;
			}
			$option = $addons[ $slug ]['settings_option'];
			if ( ! array_key_exists( $option, $post ) ) {
				continue;
			}
			$raw = wp_unslash( $post[ $option ] );
			try {
				self::loadCore( $addons[ $slug ] );
				$sanitize = $addons[ $slug ]['settings_sanitize'] ?? '';
				$clean    = is_callable( $sanitize ) ? call_user_func( $sanitize, $raw ) : self::sanitizeSettingsValue( $raw );
				update_option( $option, $clean );
			} catch ( \Throwable $e ) {
				self::panic(
					array( $slug ),
					array(
						array(
							'name'  => $addons[ $slug ]['name'] ?: $slug,
							'error' => self::FAIL_LIFECYCLE_THROW,
						),
					)
				);
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- a fatal add-on must always be recorded
				error_log( '[BBCS] [Addons] saveSettingsFromPost: add-on "' . $slug . '" threw and was switched off: ' . $e->getMessage() );
			}
		}
	}

	private static function debugLog( string $msg ): void {
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [Addons] ' . $msg );
		}
	}

	private static function debugLogAutoUpdateSkip( string $slug, string $reason ): void {
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [Addons] autoUpdate: skipped ' . $slug . ' (' . $reason . ')' );
		}
	}

	public static function fetchMarket(): array {
		if ( self::isLocalMode() ) {
			return self::buildMarketFromDisk( self::scanAll() );
		}
		$url = self::getMarketUrl();
		if ( empty( $url ) ) {
			return array();
		}

		$res = wp_remote_get( $url, array( 'timeout' => 10 ) );
		if ( ! is_wp_error( $res ) && wp_remote_retrieve_response_code( $res ) === 200 ) {
			$json = json_decode( wp_remote_retrieve_body( $res ), true );
			if ( is_array( $json ) && isset( $json['addons'] ) && is_array( $json['addons'] ) ) {
				return $json['addons'];
			}
		}

		return array();
	}

	public static function autoUpdate( string $core_version = '' ): array {
		$result = array(
			'updated' => array(),
			'failed'  => array(),
		);
		if ( self::isLocalMode() ) {
			return $result;
		}
		$version = $core_version !== '' ? $core_version : BOTBLOCKER_VERSION;

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [Addons] autoUpdate: start' );
		}

		$market = self::fetchMarket();
		if ( empty( $market ) ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [Addons] autoUpdate: market empty, skipped' );
			}
			return $result;
		}

		$addons = self::scanAll();
		if ( empty( $addons ) ) {
			return $result;
		}

		if ( ! function_exists( 'download_url' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		if ( ! WP_Filesystem() ) {
			return $result;
		}

		$marketBySlug = array();
		foreach ( $market as $item ) {
			if ( ! empty( $item['url'] ) ) {
				$slug                  = preg_replace( '/\.zip$/', '', basename( (string) wp_parse_url( $item['url'], PHP_URL_PATH ) ) );
				$marketBySlug[ $slug ] = $item;
			}
		}

		foreach ( $addons as $slug => $addon ) {
			self::debugLog( 'autoUpdate: consider ' . $slug . ' local=' . ( $addon['version'] ?? '' ) );
			try {
				if ( ! isset( $marketBySlug[ $slug ] ) ) {
					self::debugLogAutoUpdateSkip( $slug, 'not present in market' );
					continue;
				}
				$remote    = $marketBySlug[ $slug ];
				$remoteVer = $remote['version'] ?? '';
				$localVer  = $addon['version'] ?? '';

				if ( ! $remoteVer || ! $localVer ) {
					self::debugLogAutoUpdateSkip( $slug, 'empty remote or local version' );
					continue;
				}
				if ( ! version_compare( $remoteVer, $localVer, '>' ) ) {
					self::debugLogAutoUpdateSkip( $slug, 'no newer version available' );
					continue;
				}

				$url = $remote['url'] ?? '';
				if ( empty( $url ) || ! BotBlockerAddonFiles::isAllowedAddonUrl( $url ) ) {
					self::debugLogAutoUpdateSkip( $slug, 'url missing or not allowed' );
					continue;
				}

				if ( ! empty( $remote['requires_core'] ) && version_compare( $version, $remote['requires_core'], '<' ) ) {
					self::debugLogAutoUpdateSkip( $slug, 'requires_core newer than core' );
					continue;
				}

				self::debugLog( 'autoUpdate: download ' . $slug . ' from ' . $url );
				$tmp = download_url( $url );
				if ( is_wp_error( $tmp ) ) {
					self::debugLog( 'autoUpdate: download failed ' . $slug . ': ' . $tmp->get_error_message() );
					$result['failed'][] = array(
						'slug'  => $slug,
						'name'  => $addon['name'] ?: $slug,
						'error' => self::FAIL_DOWNLOAD,
					);
					continue;
				}
				self::debugLog( 'autoUpdate: downloaded ' . $slug . ' to ' . $tmp );

				$installed = BotBlockerAddonFiles::installAddonPackage(
					$tmp,
					array(
						'slug'            => $slug,
						'filename'        => basename( (string) wp_parse_url( $url, PHP_URL_PATH ) ),
						'core_version'    => $version,
						'lifecycle_event' => 'update',
					)
				);
				if ( file_exists( $tmp ) ) {
					wp_delete_file( $tmp );
				}

				if ( is_wp_error( $installed ) ) {
					self::debugLog( 'autoUpdate: install failed ' . $slug . ': ' . $installed->get_error_code() . ' ' . $installed->get_error_message() );
					$result['failed'][] = array(
						'slug'  => $slug,
						'name'  => $addon['name'] ?: $slug,
						'error' => (string) $installed->get_error_code(),
					);
					continue;
				}

				self::debugLog( 'autoUpdate: installed ' . $slug . ' replaced=' . ( $installed['replaced'] ? 'yes' : 'no' ) );
				$result['updated'][] = $slug;

				// Ceiling recovery: a fixed version inside the range comes back on.
				$ceiling = BotBlockerMultisite::getOption( self::CEILING_OPTION, array() );
				if ( is_array( $ceiling ) && in_array( $slug, $ceiling, true ) ) {
					$new_addon = self::parseManifest( trailingslashit( BotBlockerMultisite::getAddonsDir() ) . $slug, $slug );
					if ( ! empty( $new_addon['valid'] ) && self::compatibilityState( $new_addon, $version ) === self::STATE_OK ) {
						$active_now = self::getActive();
						if ( ! in_array( $slug, $active_now, true ) ) {
							$active_now[] = $slug;
							self::setActive( $active_now );
						}
						self::forgetCeilingSlug( array( $slug ) );
					}
				}
			} catch ( \Throwable $e ) {
				self::panic(
					array( $slug ),
					array(
						array(
							'name'  => $addon['name'] ?: $slug,
							'error' => self::FAIL_LIFECYCLE_THROW,
						),
					)
				);
				$result['failed'][] = array(
					'slug'  => $slug,
					'name'  => $addon['name'] ?: $slug,
					'error' => self::FAIL_LIFECYCLE_THROW,
				);
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- a fatal add-on must always be recorded
				error_log( '[BBCS] [Addons] autoUpdate: add-on "' . $slug . '" threw and was switched off: ' . $e->getMessage() );
			}
		}

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [Addons] autoUpdate: complete updated=' . count( $result['updated'] ?? array() ) . ' failed=' . count( $result['failed'] ?? array() ) );
		}

		return $result;
	}

	public static function includeAll(): void {
		// Request boundary: a new boot starts a fresh request; the swap freeze dies with it.
		unset( $GLOBALS['bbcs_upgrade_swap_in_progress'] );
		self::debugLog( 'includeAll: start' );
		$addons = self::scanAll();
		$active = self::getActive();
		self::debugLog( 'includeAll: active=' . implode( ',', $active ) );

		$incompatible       = array();
		$incompatible_slugs = array();

		// max_core ceiling: refuse before any PHP or gateway registration runs.
		foreach ( $active as $slug ) {
			if ( isset( $addons[ $slug ] ) && $addons[ $slug ]['valid']
				&& self::compatibilityState( $addons[ $slug ] ) === self::STATE_TOO_NEW_CORE ) {
				$incompatible[]       = array(
					'name'     => $addons[ $slug ]['name'] ?: $slug,
					'max_core' => $addons[ $slug ]['max_core'],
				);
				$incompatible_slugs[] = $slug;
			}
		}
		if ( ! empty( $incompatible_slugs ) ) {
			$active = array_values( array_diff( $active, $incompatible_slugs ) );
			self::setActive( $active );
			self::recordCeilingDeactivated( $incompatible_slugs );
		}
		$gateway_addons = $addons;
		foreach ( $incompatible_slugs as $ceiling_slug ) {
			unset( $gateway_addons[ $ceiling_slug ] );
		}
		self::registerGatewayConfigs( $gateway_addons );

		$loaded             = array();
		$failed             = array();
		$failed_slugs       = array();

		foreach ( $active as $slug ) {
			if ( ! isset( $addons[ $slug ] ) || ! $addons[ $slug ]['valid'] ) {
				$name = $slug;
				if ( isset( $addons[ $slug ]['name'] ) && $addons[ $slug ]['name'] !== '' ) {
					$name = $addons[ $slug ]['name'];
				}
				$failed[]       = array(
					'name'  => $name,
					'error' => self::FAIL_MANIFEST_INVALID,
				);
				$failed_slugs[] = $slug;
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- a broken add-on must always be recorded
				error_log( '[BBCS] [Addons] includeAll: add-on "' . $slug . '" is invalid or missing and was switched off' );
				continue;
			}

			if ( ! self::isCompatible( $addons[ $slug ] ) ) {
				$incompatible[]       = array(
					'name'          => $addons[ $slug ]['name'] ?: $slug,
					'requires_core' => $addons[ $slug ]['requires_core'],
				);
				$incompatible_slugs[] = $slug;
				continue;
			}

			try {
				self::loadCore( $addons[ $slug ] );
				$loaded[] = $slug;
				self::dispatchLifecycle( $slug, 'load', $addons[ $slug ] );
				self::debugLog( 'includeAll: loaded ' . $slug );
			} catch ( \Throwable $e ) {
				$loaded         = array_values( array_diff( $loaded, array( $slug ) ) );
				$failed[]       = array(
					'name'  => $addons[ $slug ]['name'] ?: $slug,
					'error' => self::FAIL_LIFECYCLE_THROW,
				);
				$failed_slugs[] = $slug;
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- a fatal add-on must always be recorded
				error_log( '[BBCS] [Addons] includeAll: add-on "' . $slug . '" failed to load and was switched off: ' . $e->getMessage() );
			}
		}

		if ( ! empty( $failed_slugs ) ) {
			self::panic( $failed_slugs, $failed );
			$active = array_values( array_diff( $active, $failed_slugs ) );
		}

		if ( ! empty( $incompatible ) ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [Addons] includeAll: deactivated incompatible addons: ' . implode( ', ', $incompatible_slugs ) );
			}
			$new_active = array_values(
				array_filter(
					$active,
					function ( $s ) use ( $addons ) {
								return isset( $addons[ $s ] ) && $addons[ $s ]['valid'] && self::isCompatible( $addons[ $s ] );
					}
				)
			);
			if ( $new_active !== $active ) {
				self::setActive( $new_active );
			}
			BotBlockerAlerts::setAddonIncompatible( $incompatible );
		}

		foreach ( $loaded as $slug ) {
			if ( ! isset( $addons[ $slug ] ) ) {
				continue;
			}
			try {
				self::dispatchLifecycle( $slug, 'health_check', $addons[ $slug ] );
			} catch ( \Throwable $e ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- a fatal add-on must always be recorded
				error_log( '[BBCS] [Addons] includeAll: health_check of "' . $slug . '" threw: ' . $e->getMessage() );
			}
		}
		self::runLedgerMigrations( $addons, $loaded );
		self::debugLog( 'includeAll: complete loaded=' . implode( ',', $loaded ) );

		$cloud_api_active = ( class_exists( 'BotBlockerPro' ) && BotBlockerPro::isActive() );
		self::restoreGatewayStates();

		// Layer 1 panic stamp: a thrown early-init must panic, not silently skip.
		if ( class_exists( 'BBCS_Early_Init_Core' ) ) {
			$panic_stamp = BBCS_Early_Init_Core::getMainDataDir() . BBCS_EARLY_INIT_PANIC_STAMP;
			if ( is_file( $panic_stamp ) ) {
				$stamp_mtime = (int) @filemtime( $panic_stamp );
				if ( $stamp_mtime === 0 || $stamp_mtime > time() - HOUR_IN_SECONDS ) {
					$stamp_text = (string) @file_get_contents( $panic_stamp );
					self::panic(
						array( 'bbcs-early-init' ),
						array(
							array(
								'name'  => 'bbcs-early-init',
								'error' => 'Layer 1 early-init threw and was switched off: ' . sanitize_text_field( $stamp_text ),
							),
						)
					);
					if ( method_exists( 'BotBlockerInstall', 'setEarlyInitEnabled' ) ) {
						try {
							BotBlockerInstall::setEarlyInitEnabled( false, array( 'force_cleanup' => true ) );
						} catch ( \Throwable $ignore ) {
							unset( $ignore );
						}
					}
				}
				// phpcs:ignore WordPress.WP.AlternativeFunctions.unlink_unlink -- remove native early-init panic marker
				@unlink( $panic_stamp );
			}
		}

		$early_init_ok = false;
		if ( class_exists( 'BotBlockerGateway' ) ) {
			foreach ( array_keys( BotBlockerGateway::listByType( 'early_init' ) ) as $early_slug ) {
				if ( in_array( $early_slug, $loaded, true ) ) {
					$early_init_ok = true;
					break;
				}
			}
		}

		if ( is_multisite() && get_site_option( 'bbcs_sites_map_dirty' ) ) {
			if ( class_exists( 'BBCS_Early_Init_Core' ) ) {
				try {
					BBCS_Early_Init_Core::generateSitesMapFile( true );
				} catch ( \Throwable $e ) {
					// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- a broken early-init must always be recorded
					error_log( '[BBCS] [Addons] includeAll: sites-map rebuild threw: ' . $e->getMessage() );
				}
			}
		}

		if ( ! $early_init_ok || ! $cloud_api_active ) {
			try {
				if ( $early_init_ok && class_exists( 'BBCS_Early_Init_Core' ) ) {
					BBCS_Early_Init_Core::checkConsistency();
				} elseif ( method_exists( 'BotBlockerInstall', 'setEarlyInitEnabled' ) ) {
					BotBlockerInstall::setEarlyInitEnabled( false );
				}
			} catch ( \Throwable $e ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- a broken early-init must always be recorded
				error_log( '[BBCS] [Addons] includeAll: early-init cleanup threw: ' . $e->getMessage() );
				if ( method_exists( 'BotBlockerInstall', 'setEarlyInitEnabled' ) ) {
					try {
						BotBlockerInstall::setEarlyInitEnabled( false );
					} catch ( \Throwable $ignore ) {
						unset( $ignore );
					}
				}
			}
			return;
		}

	}

	public static function maybeRedeployBuiltins(): void {
		$class_stale = ! BotBlockerCompiledFile::isCurrent();

		if ( $class_stale ) {
			if ( false === get_transient( 'bbcs_class_drift_notice' ) ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- a shadowed shared class is a live-site hazard, always record it
				error_log( '[BBCS] [Addons] shared class drift detected (BotBlockerDataFile not current) - refreshing runtime addon copies' );
				set_transient( 'bbcs_class_drift_notice', 1, HOUR_IN_SECONDS );
			}
			if ( false === get_transient( 'bbcs_class_drift_refresh_retry' ) ) {
				self::refreshSharedClassCopies();
				set_transient( 'bbcs_class_drift_refresh_retry', 1, 5 * MINUTE_IN_SECONDS );
			}
		} else {
			if ( false !== get_transient( 'bbcs_class_drift_notice' ) ) {
				delete_transient( 'bbcs_class_drift_notice' );
				delete_transient( 'bbcs_class_drift_refresh_retry' );
			}
		}

		if ( get_option( 'bbcs_plugin_version' ) !== BOTBLOCKER_VERSION
			&& false === get_transient( 'bbcs_addon_redeploy_retry' ) ) {
			$redeploy = self::redeployBuiltins();
			if ( ! empty( $redeploy['failed'] ) ) {
				$names = array();
				foreach ( self::scanAll() as $scan_slug => $scan_addon ) {
					if ( in_array( $scan_slug, $redeploy['failed'], true ) ) {
						$names[] = $scan_addon['name'] ?: $scan_slug;
					}
				}
				foreach ( $redeploy['failed'] as $failed_slug ) {
					self::deactivateAddon( $failed_slug );
				}
				if ( class_exists( 'BotBlockerAlerts' ) ) {
					BotBlockerAlerts::setCustom(
						'bbcs_addon_redeploy_failed_alert',
						self::redeployFailedAlert( $names )
					);
				}
				set_transient( 'bbcs_addon_redeploy_retry', 1, 15 * MINUTE_IN_SECONDS );
			} else {
				delete_transient( 'bbcs_addon_redeploy_failed_alert' );
				update_option( 'bbcs_plugin_version', BOTBLOCKER_VERSION, true );
			}
		}
	}

	/**
	 * Alert payload for a failed built-in addon redeploy. Extracted so the
	 * pre-init maybeRedeployBuiltins() path can be tested: it must use the
	 * init-gated BotBlockerAlerts::t() (no premature translation).
	 *
	 * @param array $names Add-on display names.
	 * @return array
	 */
	public static function redeployFailedAlert( array $names ): array {
		return array(
			'type'      => 'addon_redeploy_failed',
			'icon'      => 'fas fa-exclamation-triangle bg-warning text-light',
			'title'     => BotBlockerAlerts::t( 'Add-ons Deactivated' ),
			'message'   => sprintf(
				/* translators: %s: comma-separated list of add-on names. */
				BotBlockerAlerts::t( 'Add-ons were deactivated because their runtime copy is outdated and could not be refreshed automatically: %s. Check uploads write permissions and re-enable them.' ),
				implode( ', ', $names )
			),
			'link'      => method_exists( 'BotBlockerMultisite', 'getAdminPageUrl' ) ? BotBlockerMultisite::getAdminPageUrl( 'bbcs_addons' ) : '',
			'link_text' => BotBlockerAlerts::t( 'View Add-ons' ),
		);
	}

	/**
	 * Refresh the Layer-1 shared classes of runtime addon copies by byte
	 * comparison. Addon version headers do not change with every core release,
	 * so the version-based redeploy can never heal an equal-version runtime
	 * copy that carries an older shared class.
	 *
	 * @param string $source_root Addon source root (defaults to the bundled addons dir).
	 * @param string $dest_root   Runtime addon root (defaults to the uploads addons dir).
	 * @return string[] Slugs of addons whose shared classes were refreshed.
	 */
	public static function refreshSharedClassCopies( string $source_root = '', string $dest_root = '' ): array {
		if ( self::isLocalMode() ) {
			return array();
		}
		if ( $source_root === '' ) {
			$source_root = BOTBLOCKER_DIR . 'addons/';
		}
		if ( $dest_root === '' ) {
			$dest_root = trailingslashit( BotBlockerMultisite::getAddonsDir() );
		}
		$source_root = trailingslashit( $source_root );
		$dest_root   = trailingslashit( $dest_root );

		if ( ! is_dir( $source_root ) || ! is_dir( $dest_root ) ) {
			return array();
		}

		$refreshed = array();
		foreach ( (array) scandir( $source_root ) as $entry ) {
			if ( $entry === '.' || $entry === '..' ) {
				continue;
			}
			$slug = sanitize_key( $entry );
			if ( $slug === '' || $slug !== $entry || ! is_dir( $source_root . $entry ) ) {
				continue;
			}
			$src_dir  = trailingslashit( $source_root . $entry ) . 'inc';
			$dest_dir = trailingslashit( $dest_root . $entry ) . 'inc';
			if ( ! is_dir( $src_dir ) || ! is_dir( $dest_dir ) ) {
				continue;
			}
			$stale = false;
			foreach ( self::SHARED_CLASS_FILES as $file ) {
				$src_file  = trailingslashit( $src_dir ) . $file;
				$dest_file = trailingslashit( $dest_dir ) . $file;
				if ( ! is_file( $src_file ) || ! is_file( $dest_file ) ) {
					continue;
				}
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- shared class refresh, byte compare before write
				$src_content  = (string) file_get_contents( $src_file );
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- shared class refresh, byte compare before write
				$dest_content = (string) file_get_contents( $dest_file );
				if ( $src_content !== $dest_content ) {
					// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents -- runtime addon copy refresh
					$written = @file_put_contents( $dest_file, $src_content, LOCK_EX );
					if ( $written !== false ) {
						$stale = true;
						if ( class_exists( 'BotBlockerCompiledFile' ) ) {
							BotBlockerCompiledFile::invalidate( $dest_file );
						}
					}
				}
			}
			if ( $stale ) {
				$refreshed[] = $entry;
			}
		}

		return $refreshed;
	}

	public static function redeployBuiltins( string $source_root = '', string $dest_root = '' ): array {
		if ( self::isLocalMode() ) {
			return array( 'redeployed' => array(), 'failed' => array() );
		}
		if ( $source_root === '' ) {
			$source_root = BOTBLOCKER_DIR . 'addons/';
		}
		if ( $dest_root === '' ) {
			$dest_root = trailingslashit( BotBlockerMultisite::getAddonsDir() );
		}
		$source_root = trailingslashit( $source_root );
		$dest_root   = trailingslashit( $dest_root );

		if ( ! is_dir( $source_root ) || ! is_dir( $dest_root ) || ! self::ensureCopyFilesystem() ) {
			return array( 'redeployed' => array(), 'failed' => array() );
		}

		$redeployed = array();
		$failed     = array();

		foreach ( (array) scandir( $source_root ) as $entry ) {
			if ( $entry === '.' || $entry === '..' ) {
				continue;
			}
			$src = $source_root . $entry;
			if ( ! is_dir( $src ) ) {
				continue;
			}
			$slug = sanitize_key( $entry );
			if ( $slug === '' || $slug !== $entry ) {
				continue;
			}

			$dest = $dest_root . $entry;
			if ( ! is_dir( $dest ) ) {
				continue; // Not installed in uploads - activation is the client's choice.
			}

			$src_version  = self::addonVersion( $src . '/' . $entry . '.php' );
			$dest_version = self::addonVersion( $dest . '/' . $entry . '.php' );
			if ( $src_version === '' || version_compare( $src_version, $dest_version, '<=' ) ) {
				continue; // No upgrade - never downgrade market-installed addons.
			}

			if ( self::replaceRuntimeCopy( $src, $dest ) ) {
				$redeployed[] = $entry;
			} else {
				$failed[] = $entry;
			}
		}

		return array( 'redeployed' => $redeployed, 'failed' => $failed );
	}

	public static function redeployBuiltin( string $slug, string $source_root = '', string $dest_root = '' ): bool {
		$slug = sanitize_key( $slug );
		if ( $slug === '' || self::isLocalMode() ) {
			return true;
		}
		if ( $source_root === '' ) {
			$source_root = BOTBLOCKER_DIR . 'addons/';
		}
		if ( $dest_root === '' ) {
			$dest_root = trailingslashit( BotBlockerMultisite::getAddonsDir() );
		}
		$src  = trailingslashit( $source_root ) . $slug;
		$dest = trailingslashit( $dest_root ) . $slug;
		if ( ! is_dir( $src ) || ! is_dir( $dest ) || ! self::ensureCopyFilesystem() ) {
			return true; // Not a bundled add-on - market copies are managed by the update flow.
		}

		$src_version  = self::addonVersion( $src . '/' . $slug . '.php' );
		$dest_version = self::addonVersion( $dest . '/' . $slug . '.php' );
		if ( $src_version === '' || version_compare( $src_version, $dest_version, '<=' ) ) {
			return true;
		}
		return self::replaceRuntimeCopy( $src, $dest );
	}

	public static function deactivateAddon( string $slug ): void {
		$slug   = sanitize_key( $slug );
		$active = self::getActive();
		if ( ! in_array( $slug, $active, true ) ) {
			return;
		}
		$active = array_values( array_diff( $active, array( $slug ) ) );
		self::setActive( $active );
		// No lifecycle dispatch: the stale copy itself is what may fatal.
		do_action( 'bbcs_addon_toggled', $slug, false );
	}

	private static function addonVersion( string $root_file ): string {
		if ( ! file_exists( $root_file ) ) {
			return '';
		}
		// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Addon header peek before WP filesystem layer is initialized.
		$contents = (string) file_get_contents( $root_file, false, null, 0, 4096 );
		if ( preg_match( '/^Version:\s*([0-9][0-9a-z.\-]*)/mi', $contents, $m ) ) {
			return trim( (string) $m[1] );
		}
		return '';
	}

	private static function ensureCopyFilesystem(): bool {
		if ( ! function_exists( 'copy_dir' ) || ! function_exists( 'WP_Filesystem' ) ) {
			require_once ABSPATH . 'wp-admin/includes/file.php';
		}
		if ( ! function_exists( 'copy_dir' ) ) {
			return false;
		}
		global $wp_filesystem;
		if ( empty( $wp_filesystem ) && ! WP_Filesystem() ) {
			return false;
		}
		return true;
	}

	private static function replaceRuntimeCopy( string $src, string $dest ): bool {
		$backup = $dest . '_bbcs_bak';
		if ( is_dir( $backup ) ) {
			self::rrmdir( $backup );
		}
		$backed_up = false;
		if ( is_dir( $dest ) ) {
			// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.rename_rename
			$backed_up = @rename( $dest, $backup );
			if ( ! $backed_up ) {
				return false;
			}
		}
		$copied = copy_dir( $src, $dest );
		if ( is_wp_error( $copied ) ) {
			if ( is_dir( $dest ) ) {
				self::rrmdir( $dest );
			}
			if ( $backed_up && is_dir( $backup ) ) {
				// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.rename_rename
				@rename( $backup, $dest );
			}
			return false;
		}
		if ( is_dir( $backup ) ) {
			self::rrmdir( $backup );
		}
		return true;
	}

	private static function rrmdir( string $dir ): void {
		if ( ! is_dir( $dir ) ) {
			return;
		}
		$items = scandir( $dir );
		foreach ( (array) $items as $item ) {
			if ( $item === '.' || $item === '..' ) {
				continue;
			}
			$path = $dir . DIRECTORY_SEPARATOR . $item;
			if ( is_dir( $path ) ) {
				self::rrmdir( $path );
			} elseif ( file_exists( $path ) ) {
				wp_delete_file( $path );
			}
		}
		// phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged, WordPress.WP.AlternativeFunctions.delete_unlink, WordPress.WP.AlternativeFunctions.file_system_operations_rmdir -- native recursive cleanup fallback when WP_Filesystem is unavailable
		@rmdir( $dir );
	}

	public static function deactivateAll(): void {

		$addons = self::scanAll();
		$active = self::getActive();

		foreach ( $active as $slug ) {
			if ( ! isset( $addons[ $slug ] ) || ! $addons[ $slug ]['valid'] ) {
				continue;
			}
			try {
				self::loadCore( $addons[ $slug ] );
				self::dispatchLifecycle( $slug, 'deactivate', $addons[ $slug ], array( 'reason' => 'plugin_deactivation' ) );
				do_action( 'bbcs_addon_toggled', $slug, false );
			} catch ( \Throwable $e ) {
				self::panic(
					array( $slug ),
					array(
					array(
						'name'  => $addons[ $slug ]['name'] ?: $slug,
						'error' => self::FAIL_LIFECYCLE_THROW,
					),
					)
				);
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- a fatal add-on must always be recorded
				error_log( '[BBCS] [Addons] deactivateAll: add-on "' . $slug . '" threw and was switched off: ' . $e->getMessage() );
			}
		}
	}
}
