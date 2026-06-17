<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

function bbcs_codeList( $code ): array {
	static $codes;
	if ( $codes === null ) {
		$codes = array(
		0   => array(
			'msg'       => 'Show check page <b>stop</b> for manual check',
			'allow'     => false,
			'count'     => true,
			'searchbot' => false,
		),
		1   => array(
			'msg'       => 'Cookie passed <b>auto</b> check',
			'allow'     => true,
			'count'     => true,
			'searchbot' => false,
		),
		2   => array(
			'msg'       => 'Check page passed click success',
			'allow'     => true,
			'count'     => true,
			'searchbot' => false,
		),
		3   => array(
			'msg'       => 'Allow cookies after local check <b>auto</b> pass',
			'allow'     => true,
			'count'     => true,
			'searchbot' => false,
		),
		4   => array(
			'msg'       => 'Allow by path or rule',
			'allow'     => true,
			'count'     => true,
			'searchbot' => false,
		),
		5   => array(
			'msg'       => 'Good bot <b>good</b> ip or ptr',
			'allow'     => true,
			'count'     => true,
			'searchbot' => true,
		),
		6   => array(
			'msg'       => 'Block by rule or path',
			'allow'     => false,
			'count'     => true,
			'searchbot' => false,
		),
		7   => array(
			'msg'       => 'Fake bot <b>fake</b> ip or ptr',
			'allow'     => false,
			'count'     => true,
			'searchbot' => false,
		),
		8   => array(
			'msg'       => 'CAPTCHA verification failed ban time1 or time2',
			'allow'     => false,
			'count'     => true,
			'searchbot' => false,
		),
		9   => array(
			'msg'       => 'JavaScript check error',
			'allow'     => false,
			'count'     => true,
			'searchbot' => false,
		),

		10  => array(
			'msg'       => 'Fake browser detected',
			'allow'     => false,
			'count'     => true,
			'searchbot' => false,
		),
		11  => array(
			'msg'       => 'Simple bot detected',
			'allow'     => false,
			'count'     => true,
			'searchbot' => false,
		),
		12  => array(
			'msg'       => 'Self request hosting view',
			'allow'     => true,
			'count'     => false,
			'searchbot' => false,
		),
		13  => array(
			'msg'       => 'Geo or language block',
			'allow'     => false,
			'count'     => true,
			'searchbot' => false,
		),
		14  => array(
			'msg'       => 'IPv6 block',
			'allow'     => false,
			'count'     => true,
			'searchbot' => false,
		),
		15  => array(
			'msg'       => 'Connection failure vpn tor or proxy',
			'allow'     => false,
			'count'     => true,
			'searchbot' => false,
		),
		16  => array(
			'msg'       => 'Analytics or search engine',
			'allow'     => true,
			'count'     => true,
			'searchbot' => true,
		),
		17  => array(
			'msg'       => 'Hosting detected',
			'allow'     => false,
			'count'     => true,
			'searchbot' => false,
		),
		18  => array(
			'msg'       => 'Unknown activities',
			'allow'     => false,
			'count'     => true,
			'searchbot' => false,
		),
		19  => array(
			'msg'       => 'Request reset by client',
			'allow'     => false,
			'count'     => true,
			'searchbot' => false,
		),
		20  => array(
			'msg'       => 'BotBlocker internal error',
			'allow'     => false,
			'count'     => true,
			'searchbot' => false,
		),
		21  => array(
			'msg'       => 'WordPress environment error',
			'allow'     => false,
			'count'     => true,
			'searchbot' => false,
		),
		22  => array(
			'msg'       => 'Cloud check error 500 or 404',
			'allow'     => false,
			'count'     => true,
			'searchbot' => false,
		),
		23  => array(
			'msg'       => 'BotBlocker inactive all pass',
			'allow'     => true,
			'count'     => false,
			'searchbot' => false,
		),
		24  => array(
			'msg'       => 'Fingerprint allow',
			'allow'     => true,
			'count'     => true,
			'searchbot' => false,
		),
		25  => array(
			'msg'       => 'Fingerprint block',
			'allow'     => false,
			'count'     => true,
			'searchbot' => false,
		),
		26  => array(
			'msg'       => 'Many requests block brute force',
			'allow'     => false,
			'count'     => true,
			'searchbot' => false,
		),
		27  => array(
			'msg'       => 'Direct file access block',
			'allow'     => false,
			'count'     => true,
			'searchbot' => false,
		),
		28  => array(
			'msg'       => 'REST or RPC block',
			'allow'     => false,
			'count'     => true,
			'searchbot' => false,
		),
		29  => array(
			'msg'       => 'REST or RPC allow whitelist',
			'allow'     => true,
			'count'     => true,
			'searchbot' => false,
		),

		30  => array(
			'msg'       => 'Early block reason 0-9',
			'allow'     => false,
			'count'     => true,
			'searchbot' => false,
		),
		40  => array(
			'msg'       => 'MU block reason 0-9',
			'allow'     => false,
			'count'     => true,
			'searchbot' => false,
		),

		50  => array(
			'msg'       => 'Empty user agent',
			'allow'     => false,
			'count'     => true,
			'searchbot' => false,
		),
		51  => array(
			'msg'       => 'IPv6 user block',
			'allow'     => false,
			'count'     => true,
			'searchbot' => false,
		),
		52  => array(
			'msg'       => 'Empty language header',
			'allow'     => false,
			'count'     => true,
			'searchbot' => false,
		),
		53  => array(
			'msg'       => 'HTTP 1.0 detected',
			'allow'     => false,
			'count'     => true,
			'searchbot' => false,
		),
		54  => array(
			'msg'       => 'Bot features in user agent',
			'allow'     => false,
			'count'     => true,
			'searchbot' => false,
		),
		55  => array(
			'msg'       => 'CloudFlare user detected',
			'allow'     => false,
			'count'     => true,
			'searchbot' => false,
		),
		56  => array(
			'msg'       => 'Classic proxy detected',
			'allow'     => false,
			'count'     => true,
			'searchbot' => false,
		),
		57  => array(
			'msg'       => 'Incorrect language header',
			'allow'     => false,
			'count'     => true,
			'searchbot' => false,
		),
		58  => array(
			'msg'       => 'Fake referrer detected',
			'allow'     => false,
			'count'     => true,
			'searchbot' => false,
		),
		59  => array(
			'msg'       => 'WordPress administrator',
			'allow'     => true,
			'count'     => false,
			'searchbot' => false,
		),
		60  => array(
			'msg'       => 'IP equals PTR record',
			'allow'     => false,
			'count'     => true,
			'searchbot' => false,
		),

		70  => array(
			'msg'       => 'WordPress cron',
			'allow'     => true,
			'count'     => false,
			'searchbot' => false,
		),
		71  => array(
			'msg'       => 'IP rule allow',
			'allow'     => true,
			'count'     => true,
			'searchbot' => false,
		),
		72  => array(
			'msg'       => 'IP rule block',
			'allow'     => false,
			'count'     => true,
			'searchbot' => false,
		),
		73  => array(
			'msg'       => 'WordPress heartbeat',
			'allow'     => true,
			'count'     => false,
			'searchbot' => false,
		),
		74  => array(
			'msg'       => 'WordPress REST API',
			'allow'     => true,
			'count'     => false,
			'searchbot' => false,
		),
		80  => array(
			'msg'       => 'Captcha timeout',
			'allow'     => false,
			'count'     => true,
			'searchbot' => false,
		),
		81  => array(
			'msg'       => 'Payment gateway callback bypass',
			'allow'     => true,
			'count'     => true,
			'searchbot' => false,
		),
		90  => array(
			'msg'       => 'CLI request detected',
			'allow'     => true,
			'count'     => false,
			'searchbot' => false,
		),
		98  => array(
			'msg'       => 'BotBlocker start by secret',
			'allow'     => true,
			'count'     => true,
			'searchbot' => false,
		),
		99  => array(
			'msg'       => 'BotBlocker server access',
			'allow'     => true,
			'count'     => false,
			'searchbot' => false,
		),
		100 => array(
			'msg'       => 'Unknown error',
			'allow'     => false,
			'count'     => true,
			'searchbot' => false,
		),
	);
	}

	return $codes[ $code ] ?? array(
		'msg'   => 'Unknown code',
		'allow' => false,
		'count' => true,
	);
}
