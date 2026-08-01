<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Sliding window rate limiter at individual IP and subnet levels.
 *
 * Bucket: { b: { minute_timestamp: count }, t: total_hits_in_window }
 * Window: [now/60 - window_minutes + 1 ... now/60]
 * Individual RPM: rpm = total_hits_in_window / window_minutes
 *
 * Subnet pressure:
 *   subnet_threshold   = block_rpm * subnet_multiplier
 *   pressure           = min(1.0, (subnet_rpm / subnet_threshold)^2)
 *   floor_rpm          = block_rpm * floor_percent
 *   effective_block    = max(floor_rpm,       block_rpm   * (1 - pressure))
 *   effective_captcha  = max(floor_rpm * 0.5, captcha_rpm * (1 - pressure))
 */
trait BotBlockerCoreRateTrait {

	public function apply_core_rate_limit(): bool {
		if ( empty( $this->settings->bbcs_rate_check_enabled ) ) {
			return false;
		}

		$ip = (string) ( $this->ip ?? '' );
		if ( $ip === '' ) {
			return false;
		}

		if ( $this->verification_state === self::VERIFY_VALID ) {
			return false;
		}

		$window_minutes = (int) ( $this->settings->bbcs_rate_window_minutes ?? 5 );
		if ( $window_minutes < 1 ) {
			$window_minutes = 5;
		}

		$captcha_rpm = (int) ( $this->settings->bbcs_rate_captcha_rpm ?? 30 );
		$block_rpm   = (int) ( $this->settings->bbcs_rate_block_rpm ?? 50 );

		$individual_rpm = $this->recordHitAndGetRpm( $ip, $window_minutes );

		$this->core_individual_rpm = $individual_rpm;

		if ( ! empty( $this->settings->bbcs_rate_subnet_enabled ) ) {
			$mask_parts    = bbcs_parse_rate_subnet_mask( (string) ( $this->settings->bbcs_rate_subnet_mask ?? '24-64' ) );
			$mask_v4       = $mask_parts[0];
			$mask_v6       = $mask_parts[1];
			$ip_version    = (int) ( $this->ip_version ?? 4 );
			$subnet_cidr   = BotBlockerIp::computePtrSubnet( $ip, $ip_version, $mask_v4, $mask_v6 );

			$subnet_rpm       = $this->recordSubnetHitAndGetRpm( $subnet_cidr, $window_minutes );
			$multiplier       = (float) ( $this->settings->bbcs_rate_subnet_multiplier ?? 3.0 );
			$subnet_threshold = (float) $block_rpm * $multiplier;
			$floor_percent    = (float) ( $this->settings->bbcs_rate_floor_percent ?? 0.1 );
			$floor_rpm        = (float) $block_rpm * $floor_percent;

			if ( $subnet_threshold > 0 ) {
				$pressure              = min( 1.0, ( $subnet_rpm / $subnet_threshold ) ** 2 );
				$this->rate_subnet_pressure = $pressure;
				$effective_block_rpm   = max( $floor_rpm, (float) $block_rpm * ( 1.0 - $pressure ) );
				$effective_captcha_rpm = max( $floor_rpm * 0.5, (float) $captcha_rpm * ( 1.0 - $pressure ) );
			} else {
				$this->rate_subnet_pressure = 0.0;
				$effective_block_rpm   = (float) $block_rpm;
				$effective_captcha_rpm = (float) $captcha_rpm;
			}
		} else {
			$this->rate_subnet_pressure = 0.0;
			$effective_block_rpm   = (float) $block_rpm;
			$effective_captcha_rpm = (float) $captcha_rpm;
		}

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [CoreRate] evaluate IP=' . $ip . ' rpm=' . $individual_rpm . ' eff_captcha=' . $effective_captcha_rpm . ' eff_block=' . $effective_block_rpm . ' base_captcha=' . $captcha_rpm . ' base_block=' . $block_rpm . ' pressure=' . $this->rate_subnet_pressure );
		}

		if ( $individual_rpm >= $effective_block_rpm ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [CoreRate] BLOCK action IP=' . $ip . ' rpm=' . $individual_rpm . ' >= eff_block=' . $effective_block_rpm );
			}
			$this->insertRateLimitBlock();
			$this->redirect_to_block( 429, 'Rate limit block' );
			return true;
		}

		if ( $individual_rpm >= $effective_captcha_rpm ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [CoreRate] CAPTCHA action IP=' . $ip . ' rpm=' . $individual_rpm . ' >= eff_captcha=' . $effective_captcha_rpm );
			}
			$this->redirect_to_dark( 'Rate limit Captcha' );
			return true;
		}

		return false;
	}

	private function insertRateLimitBlock(): void {
		global $wpdb;
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
		$ip       = (string) $this->ip;
		$duration = (int) ( $this->settings->bbcs_rate_block_duration ?? 600 );
		if ( $duration < 1 ) {
			$duration = 600;
		}
		$expires  = time() + $duration;
		$table    = $this->ip_version == 4 ? $wpdb->bbcs_ipv4rules : $wpdb->bbcs_ipv6rules;

		$exists = $wpdb->get_var( $wpdb->prepare(
			"SELECT id FROM `{$table}` WHERE `search` = %s AND `rule` = 'block' AND `comment` = 'Rate Limit Block' LIMIT 1",
			$ip
		) );

		if ( $exists ) {
			$wpdb->update( $table, array( 'expires' => $expires ), array( 'id' => $exists ) );
			do_action( 'bbcs_rate_limit_blocked', $ip );
			return;
		}

		$encoded_ip = $this->ip_version == 4
			? BotBlockerIp::toNumeric( $ip )
			: BotBlockerIp::toBinary( $ip );

		$wpdb->insert( $table, array(
			'priority' => '1',
			'search'   => $ip,
			'ip1'      => $encoded_ip,
			'ip2'      => $encoded_ip,
			'rule'     => 'block',
			'comment'  => 'Rate Limit Block',
			'expires'  => $expires,
		) );

		do_action( 'bbcs_rate_limit_blocked', $ip );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
	}

	public function recordHitAndGetRpm( string $ip, int $window_minutes ): float {
		if ( $window_minutes < 1 ) {
			$window_minutes = 1;
		}
		return $this->recordAndGetRpm( 'bbcs_fr_', $ip, $window_minutes );
	}

	public function recordSubnetHitAndGetRpm( string $subnet_cidr, int $window_minutes ): float {
		if ( $window_minutes < 1 ) {
			$window_minutes = 1;
		}
		return $this->recordAndGetRpm( 'bbcs_fr_subnet_', $subnet_cidr, $window_minutes );
	}

	private function recordAndGetRpm( string $prefix, string $value, int $window_minutes ): float {
		$key    = $prefix . md5( $value );
		$bucket = $this->getAndCleanBucket( $key, $window_minutes );

		$current_minute = (int) ( time() / 60 );
		if ( ! isset( $bucket['b'][ $current_minute ] ) ) {
			$bucket['b'][ $current_minute ] = 0;
		}
		$bucket['b'][ $current_minute ]++;
		$bucket['t'] = (int) ( $bucket['t'] ?? 0 ) + 1;

		$this->saveBucket( $key, $bucket, $window_minutes );

		return (float) $bucket['t'] / $window_minutes;
	}

	private function getAndCleanBucket( string $key, int $window_minutes ): array {
		$bucket = get_transient( $key );
		if ( ! is_array( $bucket ) || ! isset( $bucket['b'] ) ) {
			return array(
				'b'  => array(),
				't'  => 0,
				'lp' => 0,
			);
		}

		$current_minute = (int) ( time() / 60 );

		if ( isset( $bucket['lp'] ) && (int) $bucket['lp'] === $current_minute ) {
			return $bucket;
		}

		$cutoff = $current_minute - $window_minutes + 1;
		foreach ( $bucket['b'] as $minute => $count ) {
			if ( (int) $minute < $cutoff ) {
				$bucket['t'] = max( 0, (int) ( $bucket['t'] ?? 0 ) - (int) $count );
				unset( $bucket['b'][ $minute ] );
			}
		}

		$bucket['lp'] = $current_minute;

		return $bucket;
	}

	private function saveBucket( string $key, array $bucket, int $window_minutes ): void {
		$ttl = $window_minutes * 60;
		if ( $ttl < 60 ) {
			$ttl = 60;
		}
		set_transient( $key, $bucket, $ttl );
	}
}
