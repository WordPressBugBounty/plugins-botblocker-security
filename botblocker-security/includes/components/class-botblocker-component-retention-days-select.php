<?php
declare(strict_types=1);

namespace BotBlocker\Component;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** CustomSelect preset with the shared 3/5/7/10/14/30-day retention options. */
final class RetentionDaysSelect {

	/** @var int[] Preset day choices, shared by every retention picker. */
	public const DAYS = array( 3, 5, 7, 10, 14, 30 );

	public static function make(): CustomSelect {
		$instance = CustomSelect::make();

		$options = array();
		foreach ( self::DAYS as $days ) {
			$options[ (string) $days ] = sprintf(
				/* translators: %d: number of days */
				_n( '%d day', '%d days', $days, 'botblocker-security' ),
				$days
			);
		}

		return $instance->withOptions( $options );
	}
}
