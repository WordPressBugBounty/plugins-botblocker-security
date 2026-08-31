<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BotBlockerAuditSensorBotblocker {

	public static function register(): void {
		add_action( 'bbcs_addon_lifecycle', array( self::class, 'onAddonLifecycle' ), 10, 4 );
		add_action( 'bbcs_audit_rule_changed', array( self::class, 'onRuleChanged' ), 10, 2 );
		add_action( 'bbcs_audit_secret_link', array( self::class, 'onSecretLink' ), 10, 1 );
		add_action( 'bbcs_audit_protection_toggled', array( self::class, 'onProtectionToggled' ), 10, 3 );
		add_action( 'bbcs_audit_settings_diff', array( self::class, 'onSettingsDiff' ), 10, 2 );
	}

	private const PROTECTION_SETTING = 'disable';

	private static $audited_lifecycle_events = array( 'activate', 'deactivate' );

	/** @var array<string, string> Secret link action to event key. */
	private static $secret_link_events = array(
		BotBlocker::SECRET_LINK_BYPASS => BotBlockerAuditEvents::BOTBLOCKER_SECRET_BYPASS,
		BotBlocker::SECRET_LINK_OFF    => BotBlockerAuditEvents::BOTBLOCKER_SECRET_OFF,
		BotBlocker::SECRET_LINK_ON     => BotBlockerAuditEvents::BOTBLOCKER_SECRET_ON,
	);

	private static $ignored_settings = array(
		'db_prefix',
	);

	public static function onSettingsDiff( $previous, $current ): void {
		if ( ! is_array( $previous ) || ! is_array( $current ) || ! $previous ) {
			return;
		}

		$changes = array();
		foreach ( $current as $key => $value ) {
			$key = (string) $key;
			if ( in_array( $key, self::$ignored_settings, true ) ) {
				continue;
			}

			$before = array_key_exists( $key, $previous ) ? $previous[ $key ] : null;
			if ( $before === $value ) {
				continue;
			}
			if ( is_scalar( $before ) && is_scalar( $value ) && (string) $before === (string) $value ) {
				continue;
			}

			$changes[ $key ] = array(
				'from' => self::readableValue( $before ),
				'to'   => self::readableValue( $value ),
			);
		}

		foreach ( $previous as $key => $before ) {
			$key = (string) $key;
			if ( ! array_key_exists( $key, $current ) && ! in_array( $key, self::$ignored_settings, true ) ) {
				$changes[ $key ] = array( 'removed' => true );
			}
		}

		if ( ! $changes ) {
			return;
		}

		$total = count( $changes );

		BotBlockerAuditLogger::record(
			BotBlockerAuditEvents::BOTBLOCKER_SETTINGS_CHANGED,
			array(
				'data'  => array(
					'count'    => $total,
					'settings' => $changes,
				),
				'dedup' => wp_json_encode( $changes ),
			)
		);
	}

	/** @param mixed $value */
	private static function readableValue( $value ) {
		if ( is_bool( $value ) ) {
			return $value ? 'true' : 'false';
		}
		if ( $value === null || is_scalar( $value ) ) {
			$value = (string) $value;

			return strlen( $value ) > 120 ? substr( $value, 0, 117 ) . '...' : $value;
		}

		return '[' . gettype( $value ) . ']';
	}

	public static function onProtectionToggled( $setting_key, $old_value, $new_value ): void {
		if ( (string) $setting_key !== self::PROTECTION_SETTING ) {
			return;
		}

		$old = (int) $old_value;
		$new = (int) $new_value;
		if ( $old === $new ) {
			return;
		}

		$from = $old === 1 ? 'off' : 'on';
		$to   = $new === 1 ? 'off' : 'on';

		BotBlockerAuditLogger::record(
			BotBlockerAuditEvents::BOTBLOCKER_PROTECTION,
			array(
				'data'  => array(
					'from' => $from,
					'to'   => $to,
				),
				'dedup' => $from . '>' . $to,
			)
		);
	}

	public static function onSecretLink( $action ): void {
		$action = (string) $action;
		if ( ! isset( self::$secret_link_events[ $action ] ) ) {
			return;
		}

		BotBlockerAuditLogger::record(
			self::$secret_link_events[ $action ],
			array(
				'data' => array(
					'ip' => BotBlockerAuditContext::getIp(),
				),
			)
		);
	}

	public static function onAddonLifecycle( $event, $slug, $addon, $context ): void {
		unset( $addon );

		if ( ! in_array( (string) $event, self::$audited_lifecycle_events, true ) ) {
			return;
		}

		BotBlockerAuditLogger::record(
			BotBlockerAuditEvents::BOTBLOCKER_ADDON_LIFECYCLE,
			array(
				'data' => array(
					'event'   => (string) $event,
					'slug'    => (string) $slug,
					'context' => is_array( $context ) ? $context : array(),
				),
				'dedup' => (string) $event . ':' . (string) $slug,
			)
		);
	}

	/**
	 * @param array<string, mixed> $data
	 */
	public static function onRuleChanged( string $action, array $data ): void {
		BotBlockerAuditLogger::record(
			BotBlockerAuditEvents::BOTBLOCKER_RULE_CHANGED,
			array(
				'data' => array_merge(
					array( 'action' => $action ),
					$data
				),
				'dedup' => $action . ':' . ( isset( $data['list'] ) ? (string) $data['list'] : 'rule' )
					. ':' . self::ruleSubject( $data ),
			)
		);
	}

	// Dedup subject: every list keys on a row id except LLM, which toggles by provider slug.
	private static function ruleSubject( array $data ): string {
		if ( isset( $data['id'] ) && $data['id'] !== '' ) {
			return (string) $data['id'];
		}
		if ( isset( $data['provider'] ) && $data['provider'] !== '' ) {
			return (string) $data['provider'];
		}
		return '';
	}
}
