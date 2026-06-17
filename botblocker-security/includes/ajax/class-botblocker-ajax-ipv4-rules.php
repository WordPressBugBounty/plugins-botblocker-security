<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) { exit; }

class BotBlockerAjaxIpv4Rules {
	use BotBlockerAjaxIpRulesTrait;

	protected static function getTableName(): string {
		return 'bbcs_ipv4rules';
	}

	protected static function getIpVersionLabel(): string {
		return 'IPv4';
	}

	protected static function encodeIpForStorage( string $ip ): string {
		$numeric = BotBlockerIp::toNumeric( $ip );
		return (string) $numeric;
	}

	protected static function getOverlapPlaceholder(): string {
		return '%d';
	}

	protected static function getFileRenderMethod(): string {
		return 'renderIps';
	}

	protected static function decodeImportedIpField( $value ) {
		return intval( $value );
	}
}
