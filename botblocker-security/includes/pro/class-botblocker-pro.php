<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BotBlockerPro {

	public static function getSettings(): array {
		global $wpdb;

		try {
			$BBCS = BotBlocker::getInstance();
			if ( $BBCS && $BBCS->settings ) {
				$settings = array(
					'cloud_api_type'   => $BBCS->settings->cloud_api_type ?? null,
					'cloud_api_key'    => $BBCS->settings->cloud_api_key ?? null,
					'cloud_api_secret' => $BBCS->settings->cloud_api_secret ?? null,
					'cloud_api_email'  => $BBCS->settings->cloud_api_email ?? null,
					'cloud_api_tier'   => $BBCS->settings->cloud_api_tier ?? null,
				);
				if ( array_filter( $settings ) ) {
					return $settings;
				}
			}
		} catch ( Exception $e ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( '[BBCS DEBUG] [Pro] getSettings singleton access failed: ' . $e->getMessage() );
			}
		}

		$status_key      = 'bbcs_cloud_api_status_transient';
		$cached_settings = BotBlockerCache::getCacheData( $status_key );
		if ( $cached_settings !== null ) {
			return $cached_settings;
		}

		$setting_keys = array( 'cloud_api_type', 'cloud_api_key', 'cloud_api_secret', 'cloud_api_email', 'cloud_api_tier' );
		$settings     = array();

		foreach ( $setting_keys as $key ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$value            = $wpdb->get_var( $wpdb->prepare( "SELECT value FROM `{$wpdb->bbcs_settings}` WHERE `key` = %s", $key ) );
			$settings[ $key ] = $value;
		}

		BotBlockerCache::setCacheData( $status_key, $settings, 10 * MINUTE_IN_SECONDS );

		return $settings;
	}

	public static function getSetting( string $setting_key ) {
		$settings = self::getSettings();
		return $settings[ $setting_key ] ?? null;
	}

	public static function getType(): string {
		$cloud_api_type = self::getSetting( 'cloud_api_type' );
		if ( ! empty( $cloud_api_type ) ) {
			return $cloud_api_type;
		}
		return 'Unknown';
	}

	public static function getKey(): string {
		$cloud_api_key = self::getSetting( 'cloud_api_key' );
		if ( ! empty( $cloud_api_key ) ) {
			return $cloud_api_key;
		}
		return 'Unknown';
	}

	public static function getSecret(): string {
		$cloud_api_secret = self::getSetting( 'cloud_api_secret' );
		if ( ! empty( $cloud_api_secret ) ) {
			return $cloud_api_secret;
		}
		return 'Unknown';
	}

	public static function getStatus(): ?string {
		$cloud_api_type = self::getType();
		$cloud_api_key  = self::getKey();

		if ( $cloud_api_type === BBCS_CLOUD_TYPE_EXTENDED ) {
			if ( self::isKeyHash( $cloud_api_key ) || self::isLegacyKeyHash( $cloud_api_key ) ) {
				return BBCS_CLOUD_TYPE_EXTENDED;
			}
		} elseif ( $cloud_api_type === BBCS_CLOUD_TYPE_BASIC ) {
			if ( self::isKeyHash( $cloud_api_key ) || self::isLegacyKeyHash( $cloud_api_key ) ) {
				return BBCS_CLOUD_TYPE_BASIC;
			}
		}

		return null;
	}

	public static function isValidTier( string $tier ): bool {
		return in_array( $tier, array( BBCS_CLOUD_TIER_PREMIUM, BBCS_CLOUD_TIER_PRO, BBCS_CLOUD_TIER_ULTIMATE ), true );
	}

	public static function getTier(): string {
		$tier = self::getSetting( 'cloud_api_tier' );
		if ( ! empty( $tier ) && self::isValidTier( $tier ) ) {
			return $tier;
		}
		return '';
	}

	public static function isActive(): bool {
		return self::getType() === BBCS_CLOUD_TYPE_EXTENDED && self::getStatus() === BBCS_CLOUD_TYPE_EXTENDED;
	}

	public static function isUltimate(): bool {
		return self::isActive() && self::getTier() === BBCS_CLOUD_TIER_ULTIMATE;
	}

	public static function getRemainingDays() {
		return get_transient( 'bbcs_remaining_days' );
	}

	public static function setRemainingDays( $days ): bool {
		return set_transient( 'bbcs_remaining_days', $days, BOTBLOCKER_CACHE_REMAINING_DAYS_TIME );
	}

	public static function getRemainingHits() {
		return get_transient( 'bbcs_remaining_hits' );
	}

	public static function setRemainingHits( $hits ): bool {
		return set_transient( 'bbcs_remaining_hits', $hits, BOTBLOCKER_CACHE_REMAINING_HITS_TIME );
	}

	public static function clearCache(): void {
		delete_transient( 'bbcs_remaining_hits' );
		delete_transient( 'bbcs_remaining_days' );
	}

	public static function checkExpiry(): void {
		$remaining_days = self::getRemainingDays();
		$remaining_hits = self::getRemainingHits();

		if ( $remaining_days === false || $remaining_hits === false ) {
			return;
		}

		$remaining_days = (int) $remaining_days;
		$remaining_hits = (int) $remaining_hits;

		$expired  = false;
		$warnings = array();
		foreach ( self::getExpiryWarningDays() as $threshold ) {
			if ( $remaining_days == $threshold ) {
				BotBlockerAlerts::setCloudApiExpired( $threshold == 0 ? null : $threshold );
				$expired = $threshold == 0;
				/* translators: %d: number of days until the cloud API expires. */
				$warnings[] = $expired ? __( 'Your cloud API has expired.', 'botblocker-security' ) : sprintf( __( 'Your cloud API will expire in %d days.', 'botblocker-security' ), $threshold );
				break;
			}
		}
		foreach ( self::getHitsWarningDays() as $threshold ) {
			if ( $remaining_hits <= $threshold ) {
				BotBlockerAlerts::setCloudApiHitsExhausted( $threshold == 0 ? null : $threshold );
				if ( ! $expired ) {
					$expired = $threshold == 0;
					/* translators: %d: number of hits remaining before the cloud API is exhausted. */
					$warnings[] = $expired ? __( 'Your cloud API has no hits remaining.', 'botblocker-security' ) : sprintf( __( 'Your cloud API has fewer than %d hits remaining.', 'botblocker-security' ), $threshold );
					break;
				}
			}
		}
		if ( ! empty( $warnings ) ) {
			require_once BOTBLOCKER_DIR . 'includes/mail/class-botblocker-mailer.php';
			BotBlockerMailer::sendExpirationEmail( implode( ' ', $warnings ), $expired );
		}
	}

	public static function getHitsWarningDays(): array {
		return array( 0, 100, 1000 );
	}

	public static function getExpiryWarningDays(): array {
		return array( 0, 1, 3, 7 );
	}

	public static function generateUuid(): string {
		return sprintf(
			'%04x-%04x-%04x',
			random_int( 0, 0xffff ),
			random_int( 0, 0xffff ),
			random_int( 0, 0xffff )
		);
	}

	public static function generateKey( string $series, string $email ): string {
		$seriesMap = array(
			'Metric'     => '1M',
			'BotBlocker' => '1B',
			'ShieldWP'   => '1S',
		);

		$series   = $seriesMap[ $series ] ?? '0X';
		$serial   = strtoupper( substr( md5( $email ), 0, 6 ) );
		$key      = self::generateUuid();
		$checksum = substr( md5( $series . $key . $serial ), 0, 2 );

		return "{$series}-{$key}-{$serial}-{$checksum}";
	}

	public static function isKeyHash( $key ): bool {
		return is_string( $key ) && (bool) preg_match( '/^[0-9a-f]{64}$/', $key );
	}

	public static function isLegacyKeyHash( $key ): bool {
		return is_string( $key ) && (bool) preg_match( '/^[0-9a-f]{32}$/', $key );
	}

	public static function reset(): void {
		global $wpdb;

		$cloud_basic_settings = array(
			'cloud_api_type'           => BBCS_CLOUD_TYPE_BASIC,
			'cloud_api_tier'           => '',
			'check'                    => 0,
			'unresponsive'             => 0,
			'force_cloud_validation'   => 0,
			'block_vpn_users'          => 0,
			'block_tor_users'          => 0,
			'block_override'           => 0,
			'block_web_engine_options' => 0,
			'block_device_options'     => 0,
		);

		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		foreach ( $cloud_basic_settings as $key => $value ) {
			$key = sanitize_key( $key );
			$wpdb->update( $wpdb->bbcs_settings, array( 'value' => $value ), array( 'key' => $key ) );
		}
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		BotBlockerFileRenderer::generateSettingsFile();
	}

	public static function buildAuthPayload( $settings = null ): array {
		if ( $settings === null ) {
			$settings = BotBlocker::getInstance()->settings ?? null;
		}

		$key     = isset( $settings->cloud_api_key ) ? (string) $settings->cloud_api_key : '';
		$secret  = isset( $settings->cloud_api_secret ) ? (string) $settings->cloud_api_secret : '';
		$payload = array( 'cloud_api_key' => $key );

		if ( $secret !== '' && ( self::isKeyHash( $key ) || self::isLegacyKeyHash( $key ) ) ) {
			$payload['domain_api_key'] = $secret;
		}

		return $payload;
	}
}
