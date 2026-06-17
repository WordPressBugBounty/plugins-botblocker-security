<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Sensitive keys whose values should never appear in diagnostic output.
 *
 * Extend this list via the 'bbcs_sensitive_hive_keys' filter to add
 * new fields without modifying this file.
 *
 * @return string[] Lowercase sensitive key names.
 */
function bbcs_sensitive_hive_keys(): array {
	$keys = array(
		'cloud_api_key',
		'cloud_api_pass',
		'cloud_api_secret',
		'cloud_api_email',
		'recaptcha_key2',
		'recaptcha_key3',
		'recaptcha_secret2',
		'recaptcha_secret3',
		'salt',
		'salt_pz',
		'redis_password',
		'memcached_password',
		'password',
		'secret',
		'api_key',
		'token',
	);

	/**
	 * Filter the list of sensitive hive keys that should be masked in diagnostic output.
	 *
	 * @param string[] $keys Lowercase key names to mask.
	 */
	return apply_filters( 'bbcs_sensitive_hive_keys', $keys );
}

/**
 * Recursively mask sensitive values in a diagnostic data structure.
 *
 * Values whose keys match the sensitive list are replaced with '****'.
 * Objects are converted to arrays before masking.
 *
 * @param array $data The diagnostic data (from get_bot_blocker_hive()).
 * @return array Data with sensitive values masked.
 */
function bbcs_mask_sensitive_data( array $data ): array {
	$sensitive_keys = bbcs_sensitive_hive_keys();

	foreach ( $data as $key => $value ) {
		$lower_key = strtolower( (string) $key );

		if ( is_object( $value ) ) {
			$value = get_object_vars( $value );
		}

		if ( is_array( $value ) ) {
			$data[ $key ] = bbcs_mask_sensitive_data( $value );
			continue;
		}

		foreach ( $sensitive_keys as $sensitive ) {
			if ( strpos( $lower_key, $sensitive ) !== false ) {
				$data[ $key ] = '****';
				continue 2;
			}
		}
	}

	return $data;
}
