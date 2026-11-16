<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Edge evidence, rule hygiene and opt-in auto-trust.
 *
 * Records only requests that already matched a trusted-bot signature but
 * arrived from an edge that is absent from the proxy map. The table volume
 * is therefore tied to bot traffic, not to all visitors.
 */
class BotBlockerEdgeStats {

	public const MIN_CLIENTS    = 3;
	public const MIN_HITS       = 5;
	public const WINDOW_DAYS    = 7;
	public const TTL_DAYS       = 30;
	public const MAX_ENTRIES    = 20;
	public const SUGGEST_OPTION = 'bbcs_edge_suggestions';

	/** @var bool|null */
	private static $ready = null;

	public static function table(): string {
		global $wpdb;
		$name = isset( $wpdb->bbcs_edge_stats ) ? (string) $wpdb->bbcs_edge_stats : '';
		if ( $name === '' ) {
			return '';
		}
		if ( self::$ready === null ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			self::$ready = (bool) $wpdb->get_var( $wpdb->prepare( 'SHOW TABLES LIKE %s', $wpdb->esc_like( $name ) ) );
		}
		return self::$ready ? $name : '';
	}

	/**
	 * @param object $visitor Visitor object with peer_ip/edge_* properties.
	 */
	public static function record( $visitor, string $signature, bool $verified, string $verified_client = '' ): void {
		if ( ! is_object( $visitor ) || empty( $visitor->peer_ip ) ) {
			return;
		}
		$table = self::table();
		if ( $table === '' ) {
			return;
		}
		$peer   = (string) $visitor->peer_ip;
		$family = BotBlockerIp::getVersion( $peer );
		if ( $family === 0 ) {
			return;
		}
		$throttle = 'bbcs_edge_ev_' . md5( $peer );
		if ( get_transient( $throttle ) ) {
			return;
		}
		set_transient( $throttle, 1, 60 );
		$header  = (string) ( $visitor->edge_header ?? '' );
		$key     = md5( $peer . '|' . $header );
		$now     = time();

		global $wpdb;
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$row = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE peer_key = %s", $key ), ARRAY_A );

		$clients = array();
		if ( is_array( $row ) && ! empty( $row['clients'] ) ) {
			$decoded = json_decode( (string) $row['clients'], true );
			if ( is_array( $decoded ) ) {
				$clients = $decoded;
			}
		}
		if ( $verified && $verified_client !== '' && filter_var( $verified_client, FILTER_VALIDATE_IP ) ) {
			$clients[ substr( hash( 'sha256', $verified_client ), 0, 16 ) ] = 1;
			$clients = array_slice( $clients, -50, 50, true );
		}

		$data = array(
			'peer'          => sanitize_text_field( $peer ),
			'peer_key'      => $key,
			'family'        => $family,
			'asn'           => sanitize_text_field( (string) ( $visitor->asnum ?? '' ) ),
			'asname'        => sanitize_text_field( (string) ( $visitor->asname ?? '' ) ),
			'ptr'           => sanitize_text_field( (string) ( $visitor->ptr ?? '' ) ),
			'header'        => sanitize_text_field( $header ),
			'hits'          => is_array( $row ) ? (int) $row['hits'] + 1 : 1,
			'bot_hits'      => is_array( $row ) ? (int) $row['bot_hits'] + 1 : 1,
			'verified_hits' => is_array( $row ) ? (int) $row['verified_hits'] + ( $verified ? 1 : 0 ) : ( $verified ? 1 : 0 ),
			'clients'       => (string) wp_json_encode( $clients ),
			'first_seen'    => is_array( $row ) ? (int) $row['first_seen'] : $now,
			'last_seen'     => $now,
			'state'         => sanitize_text_field( (string) ( $visitor->edge_state ?? '' ) ),
			'signature'     => sanitize_text_field( $signature ),
		);

		if ( is_array( $row ) ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$wpdb->update( $table, $data, array( 'peer_key' => $key ) );
			return;
		}
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->insert( $table, $data );
	}

	/**
	 * @return array<string,array{header:string,scope:string}>
	 */
	public static function autoTrustMap(): array {
		$settings = class_exists( 'BotBlocker' ) ? BotBlocker::getInstance()->settings : null;
		if ( ! is_object( $settings ) || empty( $settings->bbcs_edge_autotrust ) ) {
			return array();
		}
		$data = self::loadAutoEdges();
		$out  = array();
		foreach ( $data as $mask => $entry ) {
			if ( ! is_array( $entry ) || empty( $entry['header'] ) ) {
				continue;
			}
			if ( isset( $entry['expires'] ) && (int) $entry['expires'] < time() ) {
				continue;
			}
			$out[ (string) $mask ] = array(
				'header' => (string) $entry['header'],
				'scope'  => 'auto_bot',
			);
		}
		return $out;
	}

	/**
	 * Raw auto-trust entries for diagnostics and manual removal.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function autoTrustEntries(): array {
		$data = self::loadAutoEdges();
		$out  = array();
		foreach ( $data as $mask => $entry ) {
			if ( ! is_array( $entry ) ) {
				continue;
			}
			$out[ (string) $mask ] = $entry;
		}
		return $out;
	}

	public static function removeAutoEdge( string $mask ): bool {
		$mask = trim( $mask );
		if ( '' === $mask ) {
			return false;
		}
		$edges = self::loadAutoEdges();
		if ( ! isset( $edges[ $mask ] ) ) {
			return false;
		}
		unset( $edges[ $mask ] );
		return self::saveAutoEdges( $edges );
	}

	/**
	 * @return array<int,array<string,mixed>>
	 */
	public static function suggestions(): array {
		$rows = get_option( self::SUGGEST_OPTION, array() );
		return is_array( $rows ) ? array_values( array_filter( $rows, 'is_array' ) ) : array();
	}

	public static function daily(): void {
		self::prune();
		self::hygiene();
		self::repairAutoEdgesFile();
		self::maybeAutoTrust();
		self::publishSuggestions();
	}

	public static function maybeAutoTrust(): void {
		self::promote();
	}

	private static function prune(): void {
		$table = self::table();
		if ( $table === '' ) {
			return;
		}
		global $wpdb;
		$since = time() - ( self::WINDOW_DAYS * DAY_IN_SECONDS );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$wpdb->query( $wpdb->prepare( "DELETE FROM `{$table}` WHERE last_seen < %d", $since ) );
	}

	/**
	 * Narrow auto-PTR allow rules and report stale server rules.
	 */
	public static function hygiene(): void {
		global $wpdb;
		$settings = class_exists( 'BotBlocker' ) ? BotBlocker::getInstance()->settings : null;
		$masks    = self::ptrSubnetMasks( is_object( $settings ) ? (string) ( $settings->ptrcache_subnet ?? '' ) : '' );
		foreach ( array( 4 => $wpdb->bbcs_ipv4rules, 6 => $wpdb->bbcs_ipv6rules ) as $family => $table ) {
			if ( empty( $table ) ) {
				continue;
			}
			$target_width = 4 === $family ? $masks[0] : $masks[1];
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			$rows = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE `rule` = %s AND `comment` LIKE %s", BBCS_RULE_ALLOW, '%(ip: %' ), ARRAY_A );
			foreach ( (array) $rows as $row ) {
				$source = self::sourceIpFromComment( (string) $row['comment'], $family );
				if ( $source === '' ) {
					continue;
				}
				$prefix = strpos( (string) $row['search'], '/' ) !== false
					? (int) substr( (string) $row['search'], strpos( (string) $row['search'], '/' ) + 1 )
					: ( 4 === $family ? 32 : 128 );
				if ( $prefix >= $target_width ) {
					continue;
				}
				self::insertWideRule( $family, $table, $source, $target_width, $row );
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$wpdb->delete( $table, array( 'id' => (int) $row['id'] ) );
			}
		}
		BotBlockerFileRenderer::renderIps();
		BotBlockerFileRenderer::renderHotBans();
	}

	/**
	 * @return array{0:int,1:int}
	 */
	private static function ptrSubnetMasks( string $setting ): array {
		$setting = trim( $setting );
		if ( $setting === '' || ! preg_match( '/^(ipv6-)?[0-9]{1,3}(-[0-9]{1,3})?$/', $setting ) ) {
			return array( 32, 128 );
		}
		$masks = BotBlockerIp::parseRateSubnetMask( $setting );
		if ( $masks[0] < 0 || $masks[0] > 32 || $masks[1] < 0 || $masks[1] > 128 ) {
			return array( 32, 128 );
		}
		return $masks;
	}

	private static function sourceIpFromComment( string $comment, int $family ): string {
		if ( ! preg_match( '/\(ip:\s*([0-9a-fA-F:.]+)\)/', $comment, $m ) ) {
			return '';
		}
		$ip = $m[1];
		if ( BotBlockerIp::getVersion( $ip ) !== $family ) {
			return '';
		}
		return $ip;
	}

	private static function insertWideRule( int $family, string $table, string $ip, int $width, array $row ): void {
		global $wpdb;
		$search = 4 === $family
			? BotBlockerIp::computePtrSubnet( $ip, 4, $width, 128 )
			: BotBlockerIp::computePtrSubnet( $ip, 6, 32, $width );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$exists = (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM `{$table}` WHERE `search` = %s", $search ) );
		if ( $exists > 0 ) {
			return;
		}
		$range = BotBlockerIp::toRange( $search );
		$ip1   = 4 === $family ? BotBlockerIp::toNumeric( (string) ( $range[0] ?? '' ) ) : BotBlockerIp::toBinary( BotBlockerIp::expandIPv6( (string) ( $range[0] ?? '' ) ) );
		$ip2   = 4 === $family ? BotBlockerIp::toNumeric( (string) ( $range[1] ?? '' ) ) : BotBlockerIp::toBinary( BotBlockerIp::expandIPv6( (string) ( $range[1] ?? '' ) ) );
		$data = array(
			'priority' => $row['priority'] ?? 10,
			'search'   => $search,
			'ip1'      => $ip1,
			'ip2'      => $ip2,
			'rule'     => BBCS_RULE_ALLOW,
			'comment'  => (string) $row['comment'],
			'expires'  => (int) $row['expires'],
			'disable'  => (int) ( $row['disable'] ?? 0 ),
			'readonly' => (int) ( $row['readonly'] ?? 0 ),
		);
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->insert( $table, $data );
	}

	private static function promote(): void {
		$settings = BotBlocker::getInstance()->settings;
		if ( empty( $settings->bbcs_edge_autotrust ) ) {
			return;
		}
		$table = self::table();
		if ( $table === '' ) {
			return;
		}
		global $wpdb;
		$since = time() - ( self::WINDOW_DAYS * DAY_IN_SECONDS );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows  = $wpdb->get_results( $wpdb->prepare( "SELECT * FROM `{$table}` WHERE verified_hits >= %d AND last_seen >= %d AND state = %s", self::MIN_HITS, $since, 'unknown_edge' ), ARRAY_A );

		$edges = self::loadAutoEdges();
		$now   = time();
		foreach ( $edges as $mask => $entry ) {
			if ( ! is_array( $entry ) || ( isset( $entry['expires'] ) && (int) $entry['expires'] < $now ) ) {
				unset( $edges[ $mask ] );
			}
		}

		$added = array();
		foreach ( (array) $rows as $row ) {
			$clients = json_decode( (string) $row['clients'], true );
			if ( ! is_array( $clients ) || count( $clients ) < self::MIN_CLIENTS ) {
				continue;
			}
			if ( (int) $row['first_seen'] <= 0 || ( $now - (int) $row['first_seen'] ) < DAY_IN_SECONDS ) {
				continue;
			}
			$peer = (string) $row['peer'];
			$mask = 4 === (int) $row['family'] ? $peer . '/32' : BotBlockerIp::expandIPv6( $peer ) . '/128';
			$new  = array(
				'header'   => (string) $row['header'],
				'expires'  => $now + ( self::TTL_DAYS * DAY_IN_SECONDS ),
				'added'    => $now,
				'evidence' => (int) $row['verified_hits'],
			);
			if ( ! isset( $edges[ $mask ] ) ) {
				$added[ $mask ] = $new;
			}
			$edges[ $mask ] = $new;
		}

		uasort(
			$edges,
			static function ( $a, $b ) {
				$ea = is_array( $a ) ? (int) ( $a['expires'] ?? 0 ) : 0;
				$eb = is_array( $b ) ? (int) ( $b['expires'] ?? 0 ) : 0;
				return $eb - $ea;
			}
		);
		$edges = array_slice( $edges, 0, self::MAX_ENTRIES, true );
		self::saveAutoEdges( $edges );

		foreach ( $added as $mask => $entry ) {
			if ( ! class_exists( 'BotBlockerAuditLogger' ) || ! class_exists( 'BotBlockerAuditEvents' ) ) {
				continue;
			}
			BotBlockerAuditLogger::record(
				BotBlockerAuditEvents::EDGE_TRUST_ADDED,
				array(
					'object_type' => 'edge',
					'dedup'       => $mask,
					'data'        => array(
						'mask'     => (string) $mask,
						'header'   => (string) $entry['header'],
						'evidence' => (int) $entry['evidence'],
					),
				)
			);
		}
	}

	private static function publishSuggestions(): void {
		$table = self::table();
		if ( $table === '' ) {
			return;
		}
		global $wpdb;
		$since = time() - ( self::WINDOW_DAYS * DAY_IN_SECONDS );
		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT peer, family, asn, asname, ptr, header, verified_hits, clients FROM `{$table}` WHERE verified_hits > 0 AND last_seen >= %d ORDER BY verified_hits DESC LIMIT 5", $since ), ARRAY_A );
		$out  = array();
		foreach ( (array) $rows as $row ) {
			$clients = json_decode( (string) $row['clients'], true );
			if ( ! is_array( $clients ) || count( $clients ) < self::MIN_CLIENTS ) {
				continue;
			}
			$out[] = array(
				'peer'     => (string) $row['peer'],
				'asn'      => (string) $row['asn'],
				'asname'   => (string) $row['asname'],
				'ptr'      => (string) $row['ptr'],
				'header'   => (string) $row['header'],
				'verified' => (int) $row['verified_hits'],
			);
		}
		update_option( self::SUGGEST_OPTION, $out, false );
	}

	/**
	 * @return array<string,array<string,mixed>>
	 */
	private static function loadAutoEdges(): array {
		if ( ! class_exists( 'BotBlockerMultisite' ) || ! class_exists( 'BotBlockerDataFile' ) ) {
			return array();
		}
		$file = BotBlockerMultisite::getDataDir() . 'auto-edges.php';
		if ( ! file_exists( $file ) ) {
			return array();
		}
		$data = BotBlockerDataFile::safeLoadWithRecovery( $file );
		return isset( $data['bbcs_auto_edges'] ) && is_array( $data['bbcs_auto_edges'] ) ? $data['bbcs_auto_edges'] : array();
	}

	/**
	 * Repair callback for the generated-file map: drop expired entries.
	 */
	public static function repairAutoEdgesFile(): void {
		$edges = array();
		if ( class_exists( 'BotBlockerMultisite' ) && class_exists( 'BotBlockerDataFile' ) ) {
			$file = BotBlockerMultisite::getDataDir() . 'auto-edges.php';
			if ( file_exists( $file ) ) {
				$data = BotBlockerDataFile::safeLoad( $file );
				$edges = isset( $data['bbcs_auto_edges'] ) && is_array( $data['bbcs_auto_edges'] ) ? $data['bbcs_auto_edges'] : array();
			}
		}
		$now = time();
		foreach ( $edges as $mask => $entry ) {
			if ( ! is_array( $entry ) || ( isset( $entry['expires'] ) && (int) $entry['expires'] < $now ) ) {
				unset( $edges[ $mask ] );
			}
		}
		self::saveAutoEdges( $edges );
	}

	/**
	 * @param array<string,array<string,mixed>> $edges Edges to persist.
	 */
	private static function saveAutoEdges( array $edges ): bool {
		if ( ! class_exists( 'BotBlockerMultisite' ) || ! class_exists( 'BotBlockerDataFile' ) ) {
			return false;
		}
		$file    = BotBlockerMultisite::getDataDir() . 'auto-edges.php';
		$content = "<?php\nif ( ! defined( 'ABSPATH' ) ) {\n\texit;\n}\nreturn " . var_export( array( 'bbcs_auto_edges' => $edges ), true ) . ";\n";
		return false !== file_put_contents( $file, BotBlockerDataFile::sign( $content ), LOCK_EX ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
	}

	public static function renderNotice(): void {
		if ( ! is_admin() || ! current_user_can( BotBlockerMultisite::canManage() ) ) {
			return;
		}
		$rows = get_option( self::SUGGEST_OPTION, array() );
		if ( ! is_array( $rows ) || empty( $rows ) ) {
			return;
		}
		$url = BotBlockerMultisite::getAdminPageUrl( 'bbcs_rules' );
		echo '<div class="notice notice-warning"><p><strong>' . esc_html__( 'BotBlocker: unverified edges send verified-bot traffic', 'botblocker-security' ) . '</strong></p><ul>';
		foreach ( array_slice( $rows, 0, 3 ) as $row ) {
			echo '<li><code>' . esc_html( (string) $row['peer'] ) . '</code> ';
			echo esc_html( 'ASN ' . $row['asn'] . ' ' . $row['asname'] . ', PTR ' . $row['ptr'] );
			/* translators: %d: number of verified bot requests from this edge. */
			echo ' — ' . esc_html( sprintf( __( '%d verified bot requests', 'botblocker-security' ), (int) $row['verified'] ) ) . '</li>';
		}
		echo '</ul><p><a href="' . esc_url( $url ) . '">' . esc_html__( 'Review the Proxy list', 'botblocker-security' ) . '</a></p></div>';
	}
}

add_action( 'admin_notices', array( 'BotBlockerEdgeStats', 'renderNotice' ) );
