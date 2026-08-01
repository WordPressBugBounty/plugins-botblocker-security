<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function bbcs_render_health_full_shortcode( $atts ): string {
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
add_shortcode( 'bbcs_health_full', 'bbcs_render_health_full_shortcode' );
