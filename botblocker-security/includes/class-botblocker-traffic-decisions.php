<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BotBlockerTrafficDecisions {

	public static function reset(): void {
		unset( $GLOBALS['bbcs_traffic_decision_providers'] );
	}

	public static function register( string $slug, $callback, int $priority = 10 ): bool {
		$slug = sanitize_key( $slug );
		if ( $slug === '' || ! is_callable( $callback ) ) {
			return false;
		}

		if ( ! isset( $GLOBALS['bbcs_traffic_decision_providers'] ) || ! is_array( $GLOBALS['bbcs_traffic_decision_providers'] ) ) {
			$GLOBALS['bbcs_traffic_decision_providers'] = array();
		}

		$GLOBALS['bbcs_traffic_decision_providers'][ $slug ] = array(
			'slug'     => $slug,
			'callback' => $callback,
			'priority' => max( -9999, min( 9999, $priority ) ),
		);
		return true;
	}

	public static function has( string $slug ): bool {
		$slug = sanitize_key( $slug );
		if ( $slug === '' ) {
			return false;
		}
		$providers = isset( $GLOBALS['bbcs_traffic_decision_providers'] ) && is_array( $GLOBALS['bbcs_traffic_decision_providers'] ) ? $GLOBALS['bbcs_traffic_decision_providers'] : array();
		return isset( $providers[ $slug ] );
	}

	public static function getAll(): array {
		$providers = isset( $GLOBALS['bbcs_traffic_decision_providers'] ) && is_array( $GLOBALS['bbcs_traffic_decision_providers'] ) ? $GLOBALS['bbcs_traffic_decision_providers'] : array();
		uasort(
			$providers,
			static function ( $a, $b ) {
				$priority_a = isset( $a['priority'] ) ? (int) $a['priority'] : 10;
				$priority_b = isset( $b['priority'] ) ? (int) $b['priority'] : 10;
				if ( $priority_a === $priority_b ) {
					return strcmp( (string) ( $a['slug'] ?? '' ), (string) ( $b['slug'] ?? '' ) );
				}
				return $priority_a <=> $priority_b;
			}
		);
		return $providers;
	}
}
