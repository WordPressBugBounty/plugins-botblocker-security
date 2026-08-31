<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BotBlockerAuditSensorSettings {

	/** @var array<string, array<string, mixed>> */
	private static $queued = array();

	public static function register(): void {
		add_action( 'added_option', array( self::class, 'onOptionChanged' ), 10, 2 );
		add_action( 'updated_option', array( self::class, 'onOptionChanged' ), 10, 3 );
		add_action( 'deleted_option', array( self::class, 'onOptionDeleted' ), 10, 1 );
		add_action( 'shutdown', array( self::class, 'flushQueued' ), 15 );
	}

	public static function onOptionChanged( $option, $old_value = null, $value = null ): void {
		if ( func_num_args() === 2 ) {
			$value = $old_value;
			$old_value = null;
		}
		self::queueChange( (string) $option, $old_value, $value, false );
	}

	public static function onOptionDeleted( $option ): void {
		self::queueChange( (string) $option, null, null, true );
	}

	private static function queueChange( string $option, $old_value, $value, bool $deleted ): void {
		if ( ! BotBlockerAuditLogger::shouldLogOption( $option ) ) {
			return;
		}

		$entry = array(
			'deleted' => $deleted,
		);

		if ( BotBlockerAuditLogger::canStoreOptionValue( $option ) ) {
			if ( ! $deleted ) {
				$entry['new'] = $value;
			}
			if ( $old_value !== null && ! $deleted ) {
				$entry['old'] = $old_value;
			}
		}

		self::$queued[ $option ] = $entry;
	}

	public static function flushQueued(): void {
		if ( ! self::$queued ) {
			return;
		}

		$options = array_keys( self::$queued );
		$data    = self::$queued;
		self::$queued = array();

		// Option names are the keys of $data, so a separate names list would store each twice.
		BotBlockerAuditLogger::record(
			BotBlockerAuditEvents::SETTINGS_OPTION_CHANGED,
			array(
				'data' => array(
					'options' => $data,
				),
				'dedup' => md5( wp_json_encode( $options ) ),
			)
		);
	}
}
