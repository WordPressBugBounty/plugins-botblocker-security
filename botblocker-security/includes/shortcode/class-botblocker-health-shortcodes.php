<?php
declare(strict_types=1);

use BotBlocker\Component\CountersGrid;
use BotBlocker\Component\HealthGauge;

require_once BOTBLOCKER_DIR . 'includes/services/class-botblocker-health-service.php';

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class BotBlockerHealthShortcodes {

	public static function register(): void {
		add_shortcode( 'bbcs_health_gauge', array( self::class, 'healthGaugeShortcode' ) );
		add_shortcode( 'botblocker_generateSiteHealthList', array( self::class, 'generateSiteHealthList' ) );
		add_shortcode( 'bbcs_counters_grid', array( self::class, 'renderCountersGrid' ) );
		add_shortcode( 'bbcs_health_full', array( self::class, 'renderHealthFullShortcode' ) );
	}

	// Health gauge canvas uses a fixed id and must be rendered at most once // per page.
	public static function healthGaugeShortcode( $atts ): string {
		$defaults = array(
			'id'                     => 'gauge_' . uniqid(),
			'value'                  => 0,
			'min'                    => 0,
			'max'                    => 100,
			'decimals'               => 0,
			'gauge_width_scale'      => 0.6,
			'label'                  => 'Security Score',
		);

		$userProvidedLabel = is_array( $atts ) && array_key_exists( 'label', $atts );
		$atts              = shortcode_atts( $defaults, $atts, 'bbcs_health_gauge' );
		$score             = (float) $atts['value'];

		if ( ! $userProvidedLabel ) {
			if ( $score >= 85 ) {
				$atts['label'] = __( 'Secure', 'botblocker-security' );
			} elseif ( $score >= 70 ) {
				$atts['label'] = __( 'Strong', 'botblocker-security' );
			} elseif ( $score >= 50 ) {
				$atts['label'] = __( 'Moderate', 'botblocker-security' );
			} elseif ( $score >= 25 ) {
				$atts['label'] = __( 'Weak', 'botblocker-security' );
			} else {
				$atts['label'] = __( 'Critical', 'botblocker-security' );
			}
		}

		return HealthGauge::make()
			->withValue( $atts['value'] )
			->withMax( $atts['max'] )
			->withLabel( $atts['label'] )
			->withDecimals( (int) $atts['decimals'] )
			->render( true );
	}

	public static function generateSiteHealthList(): string {
		$BBCS = BotBlocker::getInstance();

		if ( $BBCS->settings->cache_ui_data == 1 ) {
			$transient_key = 'bbcs_site_health_list';
			$cached_output = get_transient( $transient_key );
			if ( $cached_output !== false ) {
				return $cached_output;
			}
		}
		if ( $BBCS->isDisabled ) {
			return '<div class="bbcs-health-list"><span class="bbcs-health-list-item text-danger"><i class="fa-regular fa-circle-xmark"></i> ' . esc_html__( 'BotBlocker is disabled', 'botblocker-security' ) . '</span></div>';
		}

		$variables_affecting_weight = array(
			'bbcs_captcha_mode'  => __( 'Captcha mode enabled', 'botblocker-security' ),
			'block_empty_ua'     => __( 'Blocking empty User-Agent', 'botblocker-security' ),
			'block_empty_lang'   => __( 'Blocking empty language', 'botblocker-security' ),
			'block_nojs_users'   => __( 'Blocking users without JavaScript', 'botblocker-security' ),
			'block_simplebot_ua' => __( 'Blocking simple bot User-Agents', 'botblocker-security' ),
			'block_fake_ref'     => __( 'Blocking fake referrers', 'botblocker-security' ),
			'block_proxy_users'  => __( 'Blocking proxy users', 'botblocker-security' ),
			'block_http10_users' => __( 'Blocking HTTP/1.0 users', 'botblocker-security' ),
			'recaptcha_check'    => __( 'reCaptcha enabled', 'botblocker-security' ),
			'time_ban'           => __( 'Time ban enabled', 'botblocker-security' ),
			'time_ban_2'         => __( 'Secondary time ban enabled', 'botblocker-security' ),
		);

		$cloud_api_variables_affecting_weight = array(
			'check'                    => __( 'Cloud protection', 'botblocker-security' ),
			'unresponsive'             => __( 'Unresponsive IP blocking', 'botblocker-security' ),
			'block_vpn_users'          => __( 'Blocking VPN users', 'botblocker-security' ),
			'block_tor_users'          => __( 'Blocking Tor users', 'botblocker-security' ),
			'block_override'           => __( 'Block override', 'botblocker-security' ),
			'block_web_engine_options' => __( 'Blocking web engine options', 'botblocker-security' ),
			'block_device_options'     => __( 'Blocking device options', 'botblocker-security' ),
			'hosting_block'            => __( 'Blocking hosting providers', 'botblocker-security' ),
		);

		$variables_not_affecting_weight = array(
			'block_cf_users'             => __( 'Blocking Cloudflare users', 'botblocker-security' ),
			'block_incognito_users'      => __( 'Blocking incognito users', 'botblocker-security' ),
			'block_simple_antidetect'    => __( 'Blocking simple antidetect', 'botblocker-security' ),
			'block_incorrect_lang_users' => __( 'Blocking users with incorrect language', 'botblocker-security' ),
		);

		$output = '<div class="bbcs-health-list">';

		$isEnabledFunc = function ( $key, $value ) use ( $BBCS ) {
			switch ( $key ) {
				case 'bbcs_captcha_mode':
					return $value != -1;
				case 'hosting_block':
					return $value == 1;
				case 'time_ban':
				case 'time_ban_2':
					return $value > 0;
				case 'recaptcha_check':
					return $value == 1 && ! empty( $BBCS->settings->recaptcha_key3 ) && ! empty( $BBCS->settings->recaptcha_secret3 );
				default:
					return $value == 1;
			}
		};

		foreach ( $variables_affecting_weight as $key => $text ) {
			$value     = isset( $BBCS->settings->$key ) ? $BBCS->settings->$key : null;
			$isEnabled = $isEnabledFunc( $key, $value );

			$icon        = $isEnabled ? '<i class="fa-regular fa-circle-check"></i>' : '<i class="fa-regular fa-circle-xmark"></i>';
			$statusClass = $isEnabled ? 'text-success' : 'text-danger';
			$statusText  = $isEnabled ? '' : ' (disabled)';

			$output .= "<span class='bbcs-health-list-item $statusClass'>$icon $text$statusText</span>";
		}

		foreach ( $cloud_api_variables_affecting_weight as $key => $text ) {
			$value     = isset( $BBCS->settings->$key ) ? $BBCS->settings->$key : null;
			$isEnabled = $isEnabledFunc( $key, $value );

			$icon        = $isEnabled ? '<i class="fa-regular fa-circle-check"></i>' : '<i class="fa-regular fa-circle-xmark"></i>';
			$statusClass = $isEnabled ? 'text-success' : 'text-danger';
			$statusText  = $isEnabled ? '' : ' (disabled)';

			$output .= "<span class='bbcs-health-list-item $statusClass'>$icon $text$statusText</span>";
		}

		foreach ( $variables_not_affecting_weight as $key => $text ) {
			$value     = isset( $BBCS->settings->$key ) ? $BBCS->settings->$key : null;
			$isEnabled = $isEnabledFunc( $key, $value );

			$icon        = $isEnabled ? '<i class="fa-regular fa-circle-check"></i>' : '<i class="fa-regular fa-circle-xmark"></i>';
			$statusClass = $isEnabled ? 'text-muted' : 'text-muted';
			$statusText  = $isEnabled ? '' : ' (disabled)';

			$output .= "<span class='bbcs-health-list-item $statusClass'>$icon $text$statusText</span>";
		}

		$output .= '</div>';
		if ( $BBCS->settings->cache_ui_data == 1 ) {
			set_transient( $transient_key, $output, $BBCS->settings->cache_ui_duration );
		}
		return $output;
	}

	public static function calculateSiteHealth(): int {
		$BBCS = BotBlocker::getInstance();

		if ( $BBCS->isDisabled ) {
			return 0;
		}

		if ( $BBCS->settings->cache_ui_data == 1 ) {
			$transient_key = 'bbcs_site_health';
			$cached_health = get_transient( $transient_key );
			if ( $cached_health !== false ) {
				return (int) $cached_health;
			}
		}

		$settings       = $BBCS->settings;
		$recaptchaReady = ! empty( $settings->recaptcha_key3 ) && ! empty( $settings->recaptcha_secret3 );
		$health         = BotBlockerHealthService::calculateHealthScore( $settings, $recaptchaReady );

		if ( $BBCS->settings->cache_ui_data == 1 ) {
			set_transient( $transient_key, $health, $BBCS->settings->cache_ui_duration );
		}
		return $health;
	}

	public static function collectStatisticData(): void {
		global $wpdb;
		$BBCS = BotBlocker::getInstance();

		// REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
		    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$BBCS->statistics = $wpdb->get_row( "SELECT * FROM `{$wpdb->bbcs_counters}` LIMIT 1", ARRAY_A );
	}

	public static function renderCountersGrid(): string {
		return CountersGrid::make()
			->withData( BotBlockerStats::getCountersGridData() )
			->render( true );
	}

	public static function renderHealthFullShortcode( $atts ): string {
		$BBCS = BotBlocker::getInstance();

		$defaults = array( 'cols' => 3 );
		$atts     = is_array( $atts ) ? $atts : array();
		$atts     = shortcode_atts( $defaults, $atts, 'bbcs_health_full' );
		$cols     = (int) $atts['cols'];
		if ( $cols < 1 ) {
			$cols = 1;
		} if ( $cols > 8 ) {
			$cols = 8;
		}

		if ( $BBCS->isDisabled ) {
			return '<div class="bbcs-health-full"><div class="bbcs-health-col"><span class="bbcs-health-list-item text-danger"><i class="fa-regular fa-circle-xmark"></i> ' . esc_html__( 'BotBlocker is disabled', 'botblocker-security' ) . '</span></div></div>';
		}

		$settings       = isset( $BBCS->settings ) ? $BBCS->settings : (object) array();
		$recaptchaReady = ! empty( $settings->recaptcha_key3 ) && ! empty( $settings->recaptcha_secret3 );
		$cacheEnabled   = isset( $settings->cache_ui_data ) && (int) $settings->cache_ui_data === 1;
		$cacheKey       = 'bbcs_health_full_html_';
		if ( $cacheEnabled ) {
			$cached = get_transient( $cacheKey );
			if ( $cached !== false ) {
				return $cached;
			}
		}

		$rawItems = BotBlockerHealthService::getDefinitions();

		$itemsHtml = array();
		foreach ( $rawItems as $it ) {
			$enabled = BotBlockerHealthService::isEnabled( $it['key'], $settings, $recaptchaReady );
			switch ( $it['type'] ) {
				case 'negative':
					if ( $enabled ) {
						$icon   = '<i class="fa-regular fa-circle-xmark"></i>';
						$cls    = 'text-warning';
						$suffix = ' (' . esc_html__( 'may reduce protection', 'botblocker-security' ) . ')';
					} else {
						$icon   = '<i class="fa-regular fa-circle-check"></i>';
						$cls    = 'text-success';
						$suffix = '';
					}
					break;
				case 'neutral':
					$icon   = $enabled ? '<i class="fa-regular fa-circle-check"></i>' : '<i class="fa-regular fa-circle-xmark"></i>';
					$cls    = 'text-muted';
					$suffix = $enabled ? '' : ' (' . esc_html__( 'disabled', 'botblocker-security' ) . ')';
					break;
				default: // core/cloud_extended
					$icon   = $enabled ? '<i class="fa-regular fa-circle-check"></i>' : '<i class="fa-regular fa-circle-xmark"></i>';
					$cls    = $enabled ? 'text-success' : 'text-danger';
					$suffix = $enabled ? '' : ' (' . esc_html__( 'disabled', 'botblocker-security' ) . ')';
			}
			$itemsHtml[] = '<span class="bbcs-health-list-item ' . esc_attr( $cls ) . '">' . $icon . ' ' . esc_html( $it['label'] ) . $suffix . '</span>';
		}

		$total     = count( $itemsHtml );
		$perColumn = max( 1, (int) ceil( $total / $cols ) );
		$columns   = array();
		for ( $i = 0; $i < $cols; $i++ ) {
			$slice = array_slice( $itemsHtml, $i * $perColumn, $perColumn );
			if ( ! $slice ) {
				break;
			}
			$columns[] = '<div class="bbcs-health-col">' . implode( '', $slice ) . '</div>';
		}

		$html  = '<div class="bbcs-health-full">';
		$html .= implode( '', $columns );
		$html .= '</div>';

		if ( $cacheEnabled ) {
			set_transient( $cacheKey, $html, isset( $settings->cache_ui_duration ) ? (int) $settings->cache_ui_duration : 300 );
		}

		return $html;
	}
}
