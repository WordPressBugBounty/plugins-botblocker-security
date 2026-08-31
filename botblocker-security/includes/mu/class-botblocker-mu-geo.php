<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! defined( 'BBCS_ASN_DB_MIN_SIZE' ) ) {
	define( 'BBCS_ASN_DB_MIN_SIZE', 1024 );
}
if ( ! defined( 'BBCS_ASN_DB_MAX_SIZE' ) ) {
	define( 'BBCS_ASN_DB_MAX_SIZE', 200 * 1024 * 1024 );
}

if ( ! class_exists( 'BotBlockerMuGeo' ) ) {

	/**
	 * Layer-2 geo country decision engine: header country, MMDB lookup,
	 * blocked-list load, pure block decision. Static, zero WP dependency.
	 */
	class BotBlockerMuGeo {

		private static $cache = array();

		/**
		 * Normalizes a value to a strict [A-Z]{2} country code, '' otherwise (G3).
		 */
		public static function validCode( $value ): string {
			if ( ! is_string( $value ) ) {
				return '';
			}
			$code = strtoupper( trim( $value ) );
			return preg_match( '/^[A-Z]{2}$/', $code ) ? $code : '';
		}

		/**
		 * Prefix sanity gate: an ASN-level mega range must not drive blocking.
		 */
		public static function prefixOk( bool $is_v6, $prefix ): bool {
			$prefix = (int) $prefix;
			if ( $is_v6 ) {
				return $prefix >= 40;
			}
			return $prefix >= 16;
		}

		/**
		 * MMDB file path derived from the layer's own data dir.
		 * The database is a single network copy stored in the NETWORK data dir
		 * (main site in multisite): per-site `/sites/{blog_id}` prefixes are
		 * stripped before appending the asn_database folder.
		 */
		public static function mmdbPath( $data_dir ): string {
			if ( ! is_string( $data_dir ) || $data_dir === '' ) {
				return '';
			}
			$base   = rtrim( $data_dir, '/\\' );
			$parent = dirname( $base );
			$sites  = dirname( dirname( $parent ) );
			if ( basename( $sites ) === 'sites' ) {
				$base = rtrim( dirname( $sites ), '/\\' ) . '/botblocker/data/';
			}
			return rtrim( $base, '/\\' ) . '/asn_database/asn_database.mmdb';
		}

		/**
		 * Country from CDN/proxy headers (branch A), '' when absent/invalid.
		 */
		public static function headerCountry(): string {
			$keys = array( 'HTTP_CF_IPCOUNTRY', 'HTTP_X_IP_COUNTRY', 'HTTP_GEO_COUNTRY_CODE', 'HTTP_X_COUNTRY_CODE' );
			foreach ( $keys as $key ) {
				if ( isset( $_SERVER[ $key ] ) && is_string( $_SERVER[ $key ] ) ) {
					// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized, WordPress.Security.ValidatedSanitizedInput.MissingUnslash
					$code = self::validCode( $_SERVER[ $key ] );
					if ( $code !== '' ) {
						return $code;
					}
				}
			}
			return '';
		}

		/**
		 * Real MMDB lookup: [country, prefix, db_type] or null on any failure (fail-open).
		 */
		public static function mmdbLookup( $autoloader, $mmdb_path, string $ip ): ?array {
			$key = md5( (string) $mmdb_path . '|' . $ip );
			if ( array_key_exists( $key, self::$cache ) ) {
				return self::$cache[ $key ];
			}

			$result = null;
			if ( is_string( $autoloader ) && $autoloader !== '' && is_string( $mmdb_path ) && $mmdb_path !== '' && is_file( $mmdb_path ) ) {
				$size = @filesize( $mmdb_path );
				if ( $size !== false && $size >= BBCS_ASN_DB_MIN_SIZE && $size <= BBCS_ASN_DB_MAX_SIZE ) {
					if ( BotBlockerMuPathResolver::includeAutoloader( $autoloader, 'BotBlocker\\Vendor\\MaxMind\\Db\\Reader' ) ) {
						try {
							$reader  = new \BotBlocker\Vendor\MaxMind\Db\Reader( $mmdb_path );
							$meta    = $reader->metadata();
							$db_type = ( is_object( $meta ) && isset( $meta->databaseType ) ) ? (string) $meta->databaseType : '';
							$record  = null;
							$prefix  = 0;
							if ( method_exists( $reader, 'getWithPrefixLen' ) ) {
								$res = $reader->getWithPrefixLen( $ip );
								if ( is_array( $res ) && array_key_exists( 0, $res ) ) {
									$record = $res[0];
									$prefix = isset( $res[1] ) ? (int) $res[1] : 0;
								}
							} else {
								$record = $reader->get( $ip );
							}
							$reader->close();
							$country = ( is_array( $record ) && isset( $record['country'] ) )
								? self::validCode( $record['country'] )
								: '';
							if ( $country !== '' ) {
								$result = array(
									'country' => $country,
									'prefix'  => $prefix,
									'db_type' => $db_type,
								);
							}
						} catch ( \Throwable $e ) {
							$result = null;
						}
					}
				}
			}

			self::$cache[ $key ] = $result;
			return $result;
		}

		/**
		 * Loads the signed geo_countries.php blocked-list into code => true map.
		 */
		public static function loadBlocked( $geo_file ): array {
			$blocked = array();
			if ( ! is_string( $geo_file ) || $geo_file === '' || ! file_exists( $geo_file ) ) {
				return $blocked;
			}
			if ( ! class_exists( 'BotBlockerDataFile' ) ) {
				return $blocked;
			}
			$data = BotBlockerDataFile::safeLoad( $geo_file );
			if ( ! is_array( $data ) || empty( $data['bbcs_geo'] ) || ! is_array( $data['bbcs_geo'] ) ) {
				return $blocked;
			}
			foreach ( $data['bbcs_geo'] as $entry ) {
				if ( ! is_array( $entry ) ) {
					continue;
				}
				$code = self::validCode( isset( $entry['code'] ) ? $entry['code'] : '' );
				if ( $code !== '' ) {
					$blocked[ $code ] = true;
				}
			}
			return $blocked;
		}

		/**
		 * Exact allow-rule match against the layer's ip.php rules map (S17).
		 */
		public static function isAllowed( array $rules, string $ip ): bool {
			if ( ! isset( $rules[ $ip ] ) ) {
				return false;
			}
			return $rules[ $ip ] === BBCS_RULE_ALLOW;
		}

		/**
		 * PURE block decision, spec section 4.4. Fail-open by construction.
		 *
		 * @param string      $header_country
		 * @param array|null  $mmdb            [country, prefix, db_type] or null
		 * @param array       $blocked         code => true map
		 * @param bool        $is_allowed_ip
		 * @param bool        $is_v6
		 * @return bool
		 */
		public static function blockDecision( string $header_country, $mmdb, array $blocked, bool $is_allowed_ip, bool $is_v6 ): bool {
			if ( $is_allowed_ip ) {
				return false;
			}
			if ( empty( $blocked ) ) {
				return false;
			}
			if ( $header_country !== '' && isset( $blocked[ $header_country ] ) ) {
				return true;
			}
			if ( ! is_array( $mmdb ) ) {
				return false;
			}
			if ( ! self::prefixOk( $is_v6, $mmdb['prefix'] ?? 0 ) ) {
				return false;
			}
			return isset( $blocked[ $mmdb['country'] ?? '' ] );
		}
	}
}
