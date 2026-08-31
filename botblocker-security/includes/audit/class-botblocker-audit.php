<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-botblocker-audit-events.php';
require_once __DIR__ . '/class-botblocker-audit-context.php';
require_once __DIR__ . '/class-botblocker-audit-logger.php';
require_once __DIR__ . '/class-botblocker-audit-repository.php';
require_once __DIR__ . '/sensors/class-botblocker-audit-sensor-authentication.php';
require_once __DIR__ . '/sensors/class-botblocker-audit-sensor-content.php';
require_once __DIR__ . '/sensors/class-botblocker-audit-sensor-users.php';
require_once __DIR__ . '/sensors/class-botblocker-audit-sensor-media.php';
require_once __DIR__ . '/sensors/class-botblocker-audit-sensor-comments.php';
require_once __DIR__ . '/sensors/class-botblocker-audit-sensor-taxonomy.php';
require_once __DIR__ . '/sensors/class-botblocker-audit-sensor-plugins-themes.php';
require_once __DIR__ . '/sensors/class-botblocker-audit-sensor-settings.php';
require_once __DIR__ . '/sensors/class-botblocker-audit-sensor-botblocker.php';

class BotBlockerAudit {

	public static function ruleChanged( string $list, string $action, array $data = array() ): void {
		do_action( 'bbcs_audit_rule_changed', $action, array_merge( array( 'list' => $list ), $data ) );
	}

	public static function init(): void {
		if ( ! self::isEnabled() ) {
			return;
		}

		$sensors = apply_filters(
			'bbcs_audit_sensors',
			array(
				'BotBlockerAuditSensorAuthentication',
				'BotBlockerAuditSensorContent',
				'BotBlockerAuditSensorUsers',
				'BotBlockerAuditSensorMedia',
				'BotBlockerAuditSensorComments',
				'BotBlockerAuditSensorTaxonomy',
				'BotBlockerAuditSensorPluginsThemes',
				'BotBlockerAuditSensorSettings',
				'BotBlockerAuditSensorBotblocker',
			)
		);

		foreach ( $sensors as $sensor_class ) {
			if ( is_string( $sensor_class ) && class_exists( $sensor_class ) && method_exists( $sensor_class, 'register' ) ) {
				call_user_func( array( $sensor_class, 'register' ) );
			}
		}

		self::recordSecretLinkUse();
	}

	private static function recordSecretLinkUse(): void {
		$action = BotBlocker::getInstance()->secret_link_action;
		if ( ! is_string( $action ) || $action === '' ) {
			return;
		}

		do_action( 'bbcs_audit_secret_link', $action );
	}

	public static function isEnabled(): bool {
		$bbcs = BotBlocker::getInstance();
		if ( isset( $bbcs->settings->audit_log_enable ) ) {
			return (string) $bbcs->settings->audit_log_enable === '1' || $bbcs->settings->audit_log_enable === 1;
		}
		return true;
	}

	public static function getRetentionDays(): int {
		$bbcs = BotBlocker::getInstance();
		$days = 7;
		if ( isset( $bbcs->settings->audit_log_retention_days ) ) {
			$days = (int) $bbcs->settings->audit_log_retention_days;
		}
		return max( 1, min( 30, $days ) );
	}

	public static function cleanOldEntries(): int {
		$cutoff  = time() - ( self::getRetentionDays() * DAY_IN_SECONDS );
		$deleted = BotBlockerAuditRepository::purgeOlderThan( $cutoff );

		do_action( 'bbcs_audit_purge', $cutoff );

		return $deleted;
	}
}
