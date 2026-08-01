<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Botblocker_HealthScoreHelper {
	public static function getLabel( int $score ): string {
		if ( $score >= 85 ) {
			return __( 'Secure', 'botblocker-security' );
		} elseif ( $score >= 70 ) {
			return __( 'Strong', 'botblocker-security' );
		} elseif ( $score >= 50 ) {
			return __( 'Moderate', 'botblocker-security' );
		} elseif ( $score >= 25 ) {
			return __( 'Weak', 'botblocker-security' );
		}
		return __( 'Critical', 'botblocker-security' );
	}

	public static function getColorClass( int $score ): string {
		if ( $score >= 85 ) {
			return 'bbcs-gauge--secure';
		} elseif ( $score >= 70 ) {
			return 'bbcs-gauge--strong';
		} elseif ( $score >= 50 ) {
			return 'bbcs-gauge--moderate';
		} elseif ( $score >= 25 ) {
			return 'bbcs-gauge--weak';
		}
		return 'bbcs-gauge--critical';
	}
}
