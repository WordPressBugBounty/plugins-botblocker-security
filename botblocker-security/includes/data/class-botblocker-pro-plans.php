<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class BotBlockerProPlans {

	public const CACHE_KEY = 'bbcs_pro_plans';
	private const CACHE_TTL = 43200;
	private const RETRY_TTL = 3600;

	public static function get(): array {
		$cached = get_transient( self::CACHE_KEY );
		if ( is_array( $cached ) ) {
			return $cached;
		}

		$plans = self::fetch();
		$ttl   = self::CACHE_TTL;
		if ( empty( $plans ) ) {
			$ttl = self::RETRY_TTL;
		}
		set_transient( self::CACHE_KEY, $plans, $ttl );

		return $plans;
	}

	public static function featureLabel( array $feature ): string {
		$key = $feature['key'];
		if ( 'cloud_checks' === $key && isset( $feature['n'] ) ) {
			/* translators: %s: number of cloud checks per month, already formatted (e.g. 1,000). */
			return sprintf( __( '%s cloud checks / month', 'botblocker-security' ), number_format_i18n( $feature['n'] ) );
		}

		$labels = array(
			'early_init'         => __( 'Early Init Mode', 'botblocker-security' ),
			'cloud_ip'           => __( 'Cloud IP intelligence', 'botblocker-security' ),
			'addons'             => __( 'Unlock add-ons', 'botblocker-security' ),
			'everything_premium' => __( 'Everything in Premium', 'botblocker-security' ),
			'everything_pro'     => __( 'Everything in Pro', 'botblocker-security' ),
			'zero_day'           => __( 'Zero-day botnet updates', 'botblocker-security' ),
			'signatures'         => __( '5M+ bot signatures', 'botblocker-security' ),
			'behavioral'         => __( 'Behavioral analysis engine', 'botblocker-security' ),
			'priority_support'   => __( 'Priority support', 'botblocker-security' ),
			'emergency_support'  => __( 'Emergency support (24-hour resolution)', 'botblocker-security' ),
		);
		if ( isset( $labels[ $key ] ) ) {
			return $labels[ $key ];
		}
		if ( isset( $feature['label'] ) ) {
			return $feature['label'];
		}

		return '';
	}

	public static function formatPrice( float $price ): string {
		if ( floor( $price ) === $price ) {
			return number_format_i18n( $price );
		}

		return number_format_i18n( $price, 2 );
	}

	private static function fetch(): array {
		$response = wp_remote_get( BOTBLOCKER_PLANS_URL, array( 'timeout' => 5 ) );
		if ( is_wp_error( $response ) || 200 !== (int) wp_remote_retrieve_response_code( $response ) ) {
			return array();
		}

		$body = json_decode( (string) wp_remote_retrieve_body( $response ), true );
		if ( ! is_array( $body ) || empty( $body['success'] ) || ! isset( $body['data']['plans'] ) || ! is_array( $body['data']['plans'] ) ) {
			return array();
		}

		$plans = array();
		foreach ( $body['data']['plans'] as $plan ) {
			if ( ! is_array( $plan ) ) {
				continue;
			}
			$normalized = self::normalize( $plan );
			if ( null !== $normalized ) {
				$plans[] = $normalized;
			}
		}

		return $plans;
	}

	private static function normalize( array $plan ): ?array {
		if ( empty( $plan['plan_id'] ) || empty( $plan['name'] ) || empty( $plan['tier'] ) ) {
			return null;
		}
		if ( ! isset( $plan['currency'] ) || 'usd' !== $plan['currency'] ) {
			return null;
		}
		if ( ! isset( $plan['monthly'], $plan['annual'] ) || ! is_numeric( $plan['monthly'] ) || ! is_numeric( $plan['annual'] ) ) {
			return null;
		}

		$features = array();
		if ( isset( $plan['features'] ) && is_array( $plan['features'] ) ) {
			foreach ( $plan['features'] as $feature ) {
				if ( ! is_array( $feature ) || empty( $feature['key'] ) || ! is_string( $feature['key'] ) ) {
					continue;
				}
				$item = array( 'key' => sanitize_key( $feature['key'] ) );
				if ( isset( $feature['n'] ) && is_numeric( $feature['n'] ) ) {
					$item['n'] = (int) $feature['n'];
				}
				if ( isset( $feature['label'] ) && is_string( $feature['label'] ) && '' !== $feature['label'] ) {
					$item['label'] = sanitize_text_field( $feature['label'] );
				}
				$features[] = $item;
			}
		}

		return array(
			'plan_id'  => (int) $plan['plan_id'],
			'name'     => sanitize_text_field( (string) $plan['name'] ),
			'tier'     => sanitize_key( (string) $plan['tier'] ),
			'monthly'  => (float) $plan['monthly'],
			'annual'   => (float) $plan['annual'],
			'features' => $features,
		);
	}
}
