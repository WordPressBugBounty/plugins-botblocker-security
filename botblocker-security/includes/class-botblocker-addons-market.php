<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-botblocker-addons.php';
require_once __DIR__ . '/dto/class-addons-market-context.php';

class BotBlockerAddonsMarket {

	const CACHE_TTL    = 600;
	const FAIL_TTL     = 60;
	const HTTP_TIMEOUT = 8;

	const CACHE_KEY_PREFIX     = 'bbcs_market_';
	const LAST_GOOD_KEY_PREFIX = 'bbcs_market_last_good_';

	const STATUS_UNKNOWN     = 'unknown';
	const STATUS_LOCAL       = 'local';
	const STATUS_CACHED      = 'cached';
	const STATUS_FRESH       = 'fresh';
	const STATUS_LAST_GOOD   = 'last_good';
	const STATUS_UNAVAILABLE = 'unavailable';

	/**
	 * Remote channels that get cached. Local mode has no cache (disk only).
	 *
	 * @var array<int,string>
	 */
	const REMOTE_CHANNELS = array( BotBlockerAddons::CHANNEL_STABLE, BotBlockerAddons::CHANNEL_DEV );

	/**
	 * Per-request memo, keyed by channel. Stops repeat fetches on one page load.
	 *
	 * @var array<string,array<int,array<string,mixed>>>
	 */
	private static $memo = array();

	/** @var string */
	private static $status = self::STATUS_UNKNOWN;

	public static function getLoadStatus(): string {
		return self::$status;
	}

	private static function cacheKey( string $channel ): string {
		return self::CACHE_KEY_PREFIX . $channel;
	}

	private static function lastGoodKey( string $channel ): string {
		return self::LAST_GOOD_KEY_PREFIX . $channel;
	}

	/**
	 * Drop the cached market feed of every remote channel.
	 *
	 * Call this after an install, update or delete, so the page shows the new state at once.
	 */
	public static function flushCache(): void {
		self::$memo = array();
		foreach ( self::REMOTE_CHANNELS as $channel ) {
			self::flushChannelCache( $channel );
		}
	}

	private static function flushChannelCache( string $channel ): void {
		unset( self::$memo[ $channel ] );
		delete_transient( self::cacheKey( $channel ) );
	}

	/**
	 * Flush the cache of the currently active channel only, dev channel only
	 * (mirrors the restriction in load()). Used by the ?force=1 page param.
	 */
	public static function flushActiveChannelCacheIfDev(): void {
		if ( BotBlockerAddons::isLocalMode() ) {
			return;
		}
		$channel = BotBlockerAddons::getChannel();
		if ( BotBlockerAddons::CHANNEL_DEV === $channel ) {
			self::flushChannelCache( $channel );
		}
	}

	/**
	 * Cloud feeds key an add-on by "id"; disk-built items carry an explicit slug.
	 *
	 * @param array<string,mixed> $item
	 */
	public static function itemSlug( array $item ): string {
		if ( ! empty( $item['slug'] ) ) {
			return sanitize_key( (string) $item['slug'] );
		}
		if ( ! empty( $item['id'] ) ) {
			return sanitize_key( (string) $item['id'] );
		}
		if ( empty( $item['url'] ) ) {
			return '';
		}
		$path = wp_parse_url( (string) $item['url'], PHP_URL_PATH );
		return sanitize_key( preg_replace( '/\.zip$/', '', basename( (string) $path ) ) );
	}

	/**
	 * @param array<int,array<string,mixed>> $addons
	 * @return array<int,array<string,mixed>>
	 */
	private static function filterEnabled( array $addons ): array {
		return array_values(
			array_filter(
				$addons,
				function ( $item ) {
					return ( $item['enabled'] ?? true ) !== false;
				}
			)
		);
	}

	/**
	 * A channel has exactly two sources, tried in order: the configured remote
	 * feed, then the bundled catalog file shipped in wp-content/plugins/bbcs-addons.
	 * The file exists so dev/local boxes with no outbound network still work.
	 *
	 * @return array<int,array<string,string>>
	 */
	private static function fetchSources( string $channel ): array {
		$sources = array();

		$url = BotBlockerAddons::getMarketUrl( $channel );
		if ( '' !== $url ) {
			$sources[] = array(
				'type' => 'url',
				'src'  => $url,
			);
		}

		$relative  = BotBlockerAddons::CHANNEL_DEV === $channel ? 'dev/master.json' : 'master.json';
		$sources[] = array(
			'type' => 'file',
			'src'  => WP_CONTENT_DIR . '/plugins/bbcs-addons/' . $relative,
		);

		return $sources;
	}

	private static function debugLog( string $channel, string $message ): void {
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[BBCS Addons Market][' . $channel . '] ' . $message );
		}
	}

	/**
	 * Read the first source that answers with a valid feed.
	 *
	 * @return array{0:array<int,array<string,mixed>>,1:bool} Market items and a success flag.
	 */
	private static function fetchRemote( string $channel ): array {
		foreach ( self::fetchSources( $channel ) as $source ) {
			$json = null;

			if ( 'file' === $source['type'] ) {
				if ( ! file_exists( $source['src'] ) ) {
					self::debugLog( $channel, 'file not found: ' . $source['src'] );
					continue;
				}
				// REVIEWER NOTE: Local add-ons catalogue file, not a remote resource.
				// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
				$raw = file_get_contents( $source['src'] );
				if ( ! is_string( $raw ) || '' === trim( $raw ) ) {
					self::debugLog( $channel, 'file empty/unreadable: ' . $source['src'] );
					continue;
				}
				$json = json_decode( $raw, true );
			} else {
				$res = wp_remote_get( $source['src'], array( 'timeout' => self::HTTP_TIMEOUT ) );
				if ( is_wp_error( $res ) ) {
					self::debugLog( $channel, 'HTTP error on ' . $source['src'] . ': ' . $res->get_error_message() );
					continue;
				}
				if ( 200 !== wp_remote_retrieve_response_code( $res ) ) {
					self::debugLog( $channel, 'HTTP ' . wp_remote_retrieve_response_code( $res ) . ' on ' . $source['src'] );
					continue;
				}
				$json = json_decode( wp_remote_retrieve_body( $res ), true );
			}

			if ( ! is_array( $json ) || ! isset( $json['addons'] ) || ! is_array( $json['addons'] ) ) {
				self::debugLog( $channel, 'invalid feed shape from ' . $source['src'] );
			}

			if ( is_array( $json ) && isset( $json['addons'] ) && is_array( $json['addons'] ) ) {
				return array( self::filterEnabled( $json['addons'] ), true );
			}
		}

		return array( array(), false );
	}

	/**
	 * Cheap check for an already-cached market feed, with no fetch, no side effects.
	 * Used by the page render to decide whether it can skip the lazy AJAX load.
	 */
	public static function hasCache(): bool {
		if ( BotBlockerAddons::isLocalMode() ) {
			return true;
		}
		$channel = BotBlockerAddons::getChannel();
		if ( isset( self::$memo[ $channel ] ) ) {
			return true;
		}
		$cached = get_transient( self::cacheKey( $channel ) );
		// An empty addons list is the negative transient written on a failed fetch
		// (FAIL_TTL). Treating it as a usable catalog would skip the lazy AJAX load
		// and render a blank marketplace for the whole fail window.
		return is_array( $cached ) && ! empty( $cached['addons'] );
	}

	/**
	 * Load the active channel's market feed.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	public static function load( bool $force = false ): array {
		if ( BotBlockerAddons::isLocalMode() ) {
			self::$status = self::STATUS_LOCAL;
			return BotBlockerAddons::buildMarketFromDisk( BotBlockerAddons::scanAll() );
		}

		$channel = BotBlockerAddons::getChannel();

		if ( $force && BotBlockerAddons::CHANNEL_DEV === $channel ) {
			self::flushChannelCache( $channel );
		}

		if ( isset( self::$memo[ $channel ] ) ) {
			if ( self::STATUS_UNKNOWN === self::$status ) {
				self::$status = self::STATUS_CACHED;
			}
			return self::$memo[ $channel ];
		}

		$cached = get_transient( self::cacheKey( $channel ) );
		if ( is_array( $cached ) && ! empty( $cached['addons'] ) ) {
			self::$memo[ $channel ] = $cached['addons'];
			self::$status           = self::STATUS_CACHED;
			return $cached['addons'];
		}

		list( $market, $ok ) = self::fetchRemote( $channel );

		if ( $ok ) {
			set_transient( self::cacheKey( $channel ), array( 'addons' => $market ), self::CACHE_TTL );
			update_option( self::lastGoodKey( $channel ), $market, false );
			self::$status = self::STATUS_FRESH;
		} else {
			$last   = get_option( self::lastGoodKey( $channel ), array() );
			$market = is_array( $last ) ? $last : array();
			set_transient( self::cacheKey( $channel ), array( 'addons' => $market ), self::FAIL_TTL );
			self::$status = ! empty( $market ) ? self::STATUS_LAST_GOOD : self::STATUS_UNAVAILABLE;
		}

		self::$memo[ $channel ] = $market;
		return $market;
	}

	/**
	 * Merge installed add-ons with the market feed: install/update flags, compatibility, etc.
	 *
	 * @param array<string,array<string,mixed>> $addons
	 * @param array<int,string>                 $active
	 * @param array<int,array<string,mixed>>    $market
	 */
	public static function buildContext( array $addons, array $active, array $market, bool $addons_local_mode ): Botblocker_AddonsMarketContext {
		$marketBySlug = array();
		foreach ( $market as $it ) {
			$slug = self::itemSlug( $it );
			if ( '' !== $slug ) {
				$marketBySlug[ $slug ] = $it;
			}
		}
		foreach ( $market as $i => $it ) {
			$slug           = self::itemSlug( $it );
			$is_installed   = isset( $addons[ $slug ] );
			$installed_ver  = $is_installed ? ( $addons[ $slug ]['version'] ?? '' ) : '';
			$remote_ver     = $it['version'] ?? '';
			$item_req       = $it['requires_core'] ?? '';
			$req_compatible = empty( $item_req ) || ! defined( 'BOTBLOCKER_VERSION' ) || version_compare( BOTBLOCKER_VERSION, $item_req, '>=' );
			$has_newer_ver  = $is_installed && $installed_ver && $remote_ver && version_compare( $remote_ver, $installed_ver, '>' );
			$market[ $i ]   = array_merge(
				$it,
				array(
					'slug'               => $slug,
					'is_installed'       => $is_installed,
					'installed_ver'      => $installed_ver,
					'remote_ver'         => $remote_ver,
					'update_avail'       => $has_newer_ver && $req_compatible,
					'update_blocked'     => $has_newer_ver && ! $req_compatible,
					'show_installed_ver' => $is_installed && $installed_ver && $installed_ver !== $remote_ver,
					'is_active'          => in_array( $slug, $active, true ),
					'is_incompatible'    => ! $is_installed && ! empty( $item_req ) && defined( 'BOTBLOCKER_VERSION' ) && version_compare( BOTBLOCKER_VERSION, $item_req, '<' ),
				)
			);
		}
		foreach ( $addons as $slug => $addon ) {
			$remote              = $marketBySlug[ $slug ] ?? null;
			$broken              = ! $addon['valid'];
			$core_ver            = defined( 'BOTBLOCKER_VERSION' ) ? BOTBLOCKER_VERSION : '';
			$req_local           = trim( $addon['requires_core'] ?? '' );
			$req_remote          = trim( $remote['requires_core'] ?? '' );
			$local_req_met       = ! empty( $req_local ) && ! empty( $core_ver ) && version_compare( $core_ver, $req_local, '>=' );
			$remote_req_met      = empty( $req_remote ) || ( ! empty( $core_ver ) && version_compare( $core_ver, $req_remote, '>=' ) );
			$incompatible        = ! $broken && ! empty( $req_local ) && ! $local_req_met;
			$remote_ver          = trim( $remote['version'] ?? '' );
			$local_ver           = trim( $addon['version'] ?? '' );
			$has_newer           = ! empty( $remote_ver ) && ! empty( $local_ver ) && version_compare( $remote_ver, $local_ver, '>' );
			$update_avail        = ! $broken && $has_newer && $remote_req_met;
			$update_repair       = ! $broken && empty( $req_local ) && ! empty( $remote['url'] ) && $remote_req_met;
			$incompatible_remote = ! $broken && ! empty( $req_remote ) && ! $remote_req_met;
			$addons[ $slug ]     = array_merge(
				$addon,
				array(
					'broken'               => $broken,
					'req_core'             => ! empty( $req_local ) ? $req_local : $req_remote,
					'req_core_local'       => $req_local,
					'req_core_remote'      => $req_remote,
					'incompatible'         => $incompatible,
					'incompatible_remote'  => $incompatible_remote,
					'can_activate'         => ! $broken && $local_req_met,
					'is_active'            => in_array( $slug, $active, true ),
					'update_avail'         => $update_avail,
					'update_repair'        => $update_repair,
					'update_url'           => ( $update_avail || $update_repair ) ? ( $remote['url'] ?? '' ) : '',
					'update_requires_core' => ( $update_avail || $update_repair ) ? $req_remote : '',
				)
			);
		}
		$updates_count = 0;
		foreach ( $addons as $addon ) {
			if ( $addon['update_avail'] ) {
				++$updates_count;
			}
		}
		$has_cloud_api = class_exists( 'BotBlockerPro' ) && BotBlockerPro::isActive();
		$addons_locked = ! $has_cloud_api && ! $addons_local_mode;

		return new Botblocker_AddonsMarketContext(
			$addons,
			$active,
			$market,
			$marketBySlug,
			$addons_locked,
			$has_cloud_api,
			$updates_count,
			$addons_local_mode
		);
	}

	public static function getContext( bool $defer_market = false ): Botblocker_AddonsMarketContext {
		$addons            = BotBlockerAddons::scanAll();
		$active            = BotBlockerAddons::getActive();
		$addons_local_mode = BotBlockerAddons::isLocalMode();
		$market            = ( $defer_market && ! $addons_local_mode ) ? array() : self::load();
		return self::buildContext( $addons, $active, $market, $addons_local_mode );
	}

	/**
	 * @return array<string,mixed>
	 */
	public static function getAjaxPayload( bool $force = false ): array {
		self::$status = self::STATUS_UNKNOWN;

		if ( BotBlockerAddons::isLocalMode() ) {
			$ctx = self::getContext( false );
			return array(
				'market'         => $ctx->market,
				'updates_count'  => $ctx->updates_count,
				'catalog_status' => self::STATUS_LOCAL,
				'channel'        => BotBlockerAddons::getChannel(),
				'message'        => '',
			);
		}

		if ( $force && BotBlockerAddons::CHANNEL_DEV !== BotBlockerAddons::getChannel() ) {
			$force = false;
		}

		$addons = BotBlockerAddons::scanAll();
		$active = BotBlockerAddons::getActive();
		$market = self::load( $force );
		$ctx    = self::buildContext( $addons, $active, $market, false );

		$status  = self::$status;
		$message = '';
		if ( self::STATUS_LAST_GOOD === $status ) {
			$message = __( 'Catalog is temporarily unavailable. Showing the last known list.', 'botblocker-security' );
		} elseif ( self::STATUS_UNAVAILABLE === $status ) {
			$message = __( 'Add-on catalog is currently unavailable.', 'botblocker-security' );
		}

		return array(
			'market'         => $ctx->market,
			'updates_count'  => $ctx->updates_count,
			'catalog_status' => $status,
			'channel'        => BotBlockerAddons::getChannel(),
			'message'        => $message,
		);
	}
}
