<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit; }

class BotBlockerAjaxIpv6Rules {
	use BotBlockerAjaxIpRulesTrait;

	protected static function getRuleListName(): string {
		return BotBlockerAuditEvents::RULE_LIST_IPV6;
	}

	protected static function getTableName(): string {
		return 'bbcs_ipv6rules';
	}

	protected static function getIpVersionLabel(): string {
		return 'IPv6';
	}

	protected static function encodeIpForStorage( string $ip ): string {
		return BotBlockerIp::toBinary( BotBlockerIp::expandIPv6( $ip ) );
	}

	protected static function getOverlapPlaceholder(): string {
		return '%s';
	}

	protected static function getFileRenderMethod(): string {
		return 'renderIps';
	}

	protected static function decodeImportedIpField( $value ) {
		return $value;
	}
}
