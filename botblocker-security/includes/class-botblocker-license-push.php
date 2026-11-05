<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class BotBlockerLicensePush {
	const CONNECTED         = 'connected';
	const BLOCKED           = 'blocked';
	const UNAVAILABLE       = 'unavailable';
	const UNREACHABLE       = 'unreachable';
	const NO_LICENSE        = 'no_license';
	const REJECTED          = 'rejected';
	const RATE_LIMITED      = 'rate_limited';
	const CLOUD_UNREACHABLE = 'cloud_unreachable';
}
