<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

trait BotBlockerStatsBlockedTrait {

	public static function blockedToday(): string {
		$bbcs = BotBlocker::getInstance();
		if ( ! empty( $bbcs->statistics['today_blocked'] ) && $bbcs->statistics['today_blocked'] !== BOTBLOCKER_EMPTY ) {
			return (string) $bbcs->statistics['today_blocked'];
		}
		return '0';
	}

	public static function blockedTotal(): string {
		$bbcs = BotBlocker::getInstance();
		if ( ! empty( $bbcs->statistics['total_blocked'] ) && $bbcs->statistics['total_blocked'] !== BOTBLOCKER_EMPTY ) {
			return (string) $bbcs->statistics['total_blocked'];
		}
		return '0';
	}
}
