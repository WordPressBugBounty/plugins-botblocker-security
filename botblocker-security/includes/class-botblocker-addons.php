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
		$upload_file = $base . '/inc-botblocker-upload.php';
		if ( file_exists( $upload_file ) && ! function_exists( 'bbcs_get_protected_upload_dir' ) ) {
			require_once $upload_file;
		}
	}

	const CHANNEL_LOCAL  = 'local';
	const CHANNEL_DEV    = 'dev';
	const CHANNEL_STABLE = 'stable';

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
		);
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
			$loaded = bbcs_safe_load_data_file( $dataFile );
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
		BotBlockerMultisite::updateOption( self::getActiveOptionName(), array_values( $active ) );
	}

	public static function isActive( string $slug ): bool {
		$slug = sanitize_key( $slug );
		return in_array( $slug, self::getActive(), true );
	}

	public static function isCompatible( array $addon, string $core_version = '' ): bool {
		if ( empty( $addon['requires_core'] ) ) {
			return false;
		}
		$version = $core_version !== '' ? $core_version : BOTBLOCKER_VERSION;
		static $compatCache = array();
		$cache_key = $addon['requires_core'] . '|' . $version;
		if ( array_key_exists( $cache_key, $compatCache ) ) {
			return $compatCache[ $cache_key ];
		}
		$result = version_compare( $version, $addon['requires_core'], '>=' );
		$compatCache[ $cache_key ] = $result;
		return $result;
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
		static $included = array();

		$lifecycle = isset( $addon['lifecycle'] ) && is_array( $addon['lifecycle'] ) ? $addon['lifecycle'] : array();
		if ( empty( $lifecycle['file'] ) ) {
			return;
		}

		$file = $lifecycle['file'];
		if ( array_key_exists( $file, $included ) ) {
			return;
		}

		$path = self::absPath( $addon['base'] ?? '', $file );
		if ( $path !== '' && file_exists( $path ) ) {
			$included[ $file ] = true;
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

		if ( ! class_exists( 'BotBlockerTrafficDecisions' ) ) {
			require_once BOTBLOCKER_DIR . 'includes/class-botblocker-traffic-decisions.php';
		}
		BotBlockerTrafficDecisions::reset();

		$addons = self::scanAll();
		$active = self::getActive();
		$loaded = array();
		if ( ! isset( $GLOBALS['bbcs_pre_run_addons_loaded'] ) || ! is_array( $GLOBALS['bbcs_pre_run_addons_loaded'] ) ) {
			$GLOBALS['bbcs_pre_run_addons_loaded'] = array();
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
		}

		return $loaded;
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
			self::loadCore( $addons[ $slug ] );
			$sanitize = $addons[ $slug ]['settings_sanitize'] ?? '';
			$clean    = is_callable( $sanitize ) ? call_user_func( $sanitize, $raw ) : self::sanitizeSettingsValue( $raw );
			update_option( $option, $clean );
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

		$active = self::getActive();
		$active_changed = false;
		$reactivate     = array();

		foreach ( $addons as $slug => $addon ) {
			if ( ! isset( $marketBySlug[ $slug ] ) ) {
				continue;
			}
			$remote    = $marketBySlug[ $slug ];
			$remoteVer = $remote['version'] ?? '';
			$localVer  = $addon['version'] ?? '';

			if ( ! $remoteVer || ! $localVer ) {
				continue;
			}
			if ( ! version_compare( $remoteVer, $localVer, '>' ) ) {
				continue;
			}

			$url = $remote['url'] ?? '';
			if ( empty( $url ) || ! function_exists( 'bbcs_is_allowed_addon_url' ) || ! bbcs_is_allowed_addon_url( $url ) ) {
				continue;
			}

			if ( ! empty( $remote['requires_core'] ) && version_compare( $version, $remote['requires_core'], '<' ) ) {
				continue;
			}

			$tmp = download_url( $url );
			if ( is_wp_error( $tmp ) ) {
				$result['failed'][] = array(
					'slug'  => $slug,
					'name'  => $addon['name'] ?: $slug,
					'error' => $tmp->get_error_message(),
				);
				continue;
			}

			$wasActive = in_array( $slug, $active, true );
			if ( $wasActive ) {
				self::dispatchLifecycle( $slug, 'deactivate', $addon, array( 'reason' => 'auto_update' ) );
				do_action( 'bbcs_addon_toggled', $slug, false );
				$active         = array_values( array_diff( $active, array( $slug ) ) );
				$active_changed = true;
			}

			if ( ! function_exists( 'bbcs_install_addon_package' ) ) {
				if ( file_exists( $tmp ) ) {
					wp_delete_file( $tmp );
				}
				if ( $wasActive ) {
					$active[] = $slug; }
				$result['failed'][] = array(
					'slug'  => $slug,
					'name'  => $addon['name'] ?: $slug,
					'error' => 'Add-on package installer is unavailable',
				);
				continue;
			}

			$installed = bbcs_install_addon_package(
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
				if ( $wasActive ) {
					$active[] = $slug;
					self::dispatchLifecycle( $slug, 'activate', $addon, array( 'reason' => 'auto_update_rollback' ) );
					do_action( 'bbcs_addon_toggled', $slug, true );
				}
				$result['failed'][] = array(
					'slug'  => $slug,
					'name'  => $addon['name'] ?: $slug,
					'error' => $installed->get_error_message(),
				);
				continue;
			}

			$result['updated'][] = $slug;
			if ( $wasActive ) {
				$reactivate[] = $slug;
			}
		}

		if ( ! empty( $reactivate ) ) {
			$updated_addons = self::scanAll();
			foreach ( $reactivate as $slug ) {
				if ( isset( $updated_addons[ $slug ] ) && $updated_addons[ $slug ]['valid'] && self::isCompatible( $updated_addons[ $slug ], $version ) ) {
					$active[] = $slug;
					self::dispatchLifecycle( $slug, 'activate', $updated_addons[ $slug ], array( 'reason' => 'auto_update' ) );
					do_action( 'bbcs_addon_toggled', $slug, true );
				}
			}
			$active         = array_values( array_unique( $active ) );
			$active_changed = true;
		}

		if ( $active_changed ) {
			self::setActive( $active );
		}

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [Addons] autoUpdate: complete updated=' . count( $result['updated'] ?? array() ) . ' failed=' . count( $result['failed'] ?? array() ) );
		}

		return $result;
	}

	public static function includeAll(): void {
		$addons = self::scanAll();
		$active = self::getActive();

		self::registerGatewayConfigs( $addons );

		$incompatible       = array();
		$incompatible_slugs = array();
		$loaded             = array();

		foreach ( $active as $slug ) {
			if ( ! isset( $addons[ $slug ] ) || ! $addons[ $slug ]['valid'] ) {
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

			self::loadCore( $addons[ $slug ] );
			$loaded[] = $slug;
			self::dispatchLifecycle( $slug, 'load', $addons[ $slug ] );
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

			foreach ( $incompatible_slugs as $slug ) {
				if ( isset( $addons[ $slug ] ) && ! empty( $addons[ $slug ]['core'] ) ) {
					$gateway = $addons[ $slug ]['gateway'] ?? array();
					if ( ! empty( $gateway['early_init'] ) ) {
						self::loadCore( $addons[ $slug ] );
					}
				}
			}

			foreach ( $incompatible_slugs as $slug ) {
				if ( isset( $addons[ $slug ] ) ) {
					self::dispatchLifecycle( $slug, 'deactivate', $addons[ $slug ], array( 'reason' => 'incompatible' ) );
				}
			}
		}

		foreach ( $loaded as $slug ) {
			if ( isset( $addons[ $slug ] ) ) {
				self::dispatchLifecycle( $slug, 'health_check', $addons[ $slug ] );
			}
		}
		$cloud_api_active = ( class_exists( 'BotBlockerPro' ) && BotBlockerPro::isActive() );
		self::restoreGatewayStates();
		$early_init_loaded = class_exists( 'BotBlockerGateway' ) && BotBlockerGateway::isRegistered( 'early_init' );

		if ( $early_init_loaded && is_multisite() && get_site_option( 'bbcs_sites_map_dirty' ) ) {
			if ( function_exists( 'bbcs_generateSitesMapFile' ) ) {
				bbcs_generateSitesMapFile( true );
			}
		}

		if ( ! $early_init_loaded || ! $cloud_api_active ) {
			if ( $early_init_loaded && function_exists( 'bbcs_early_init_check_consistency' ) ) {
				bbcs_early_init_check_consistency();
			} elseif ( method_exists( 'BotBlockerInstall', 'setEarlyInitEnabled' ) ) {
				BotBlockerInstall::setEarlyInitEnabled( false );
			}
			return;
		}

	}

	public static function deactivateAll(): void {

		$addons = self::scanAll();
		$active = self::getActive();

		foreach ( $active as $slug ) {
			if ( ! isset( $addons[ $slug ] ) || ! $addons[ $slug ]['valid'] ) {
				continue;
			}
			self::loadCore( $addons[ $slug ] );
			self::dispatchLifecycle( $slug, 'deactivate', $addons[ $slug ], array( 'reason' => 'plugin_deactivation' ) );
			do_action( 'bbcs_addon_toggled', $slug, false );
		}
	}
}
