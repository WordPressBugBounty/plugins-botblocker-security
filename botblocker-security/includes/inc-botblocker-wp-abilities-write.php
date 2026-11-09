<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'bbcs_wp_ability_execute_delete_ip_rules' ) ) {

	function bbcs_wp_ability_request_country(): string {
		foreach ( array( 'HTTP_CF_IPCOUNTRY', 'HTTP_X_COUNTRY_CODE', 'GEOIP_COUNTRY_CODE' ) as $key ) {
			if ( ! empty( $_SERVER[ $key ] ) && is_scalar( $_SERVER[ $key ] ) ) {
				$code = strtoupper( sanitize_text_field( wp_unslash( (string) $_SERVER[ $key ] ) ) );
				if ( preg_match( '/^[A-Z]{2}$/', $code ) ) {
					return $code;
				}
			}
		}
		return '';
	}

	function bbcs_wp_ability_execute_delete_ip_rules( $input = null ) {
		global $wpdb;

		$guard = bbcs_wp_ability_guard( 'botblocker/delete-ip-rules', $input, 'DELETE', true );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}
		$target = bbcs_wp_ability_input_target( $input );
		if ( is_wp_error( $target ) ) {
			return $target;
		}
		$table = bbcs_wp_ability_rule_table( (int) $target['version'] );
		if ( $table === '' ) {
			return new WP_Error( 'ability_unavailable', __( 'Firewall rule tables are not available.', 'botblocker-security' ) );
		}
		$rule = bbcs_ability_input_string( $input, 'rule' );
		if ( $rule !== '' && ! in_array( $rule, array( BBCS_RULE_ALLOW, BBCS_RULE_BLOCK, BBCS_RULE_GRAY, BBCS_RULE_DARK ), true ) ) {
			return new WP_Error( 'ability_invalid_input', __( 'Unsupported rule type.', 'botblocker-security' ) );
		}
		$dry_run = bbcs_wp_ability_is_dry_run( $input );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- table from $wpdb.
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT `id`, `search`, `rule`, `readonly` FROM `{$table}` WHERE `search` = %s", (string) $target['search'] ), ARRAY_A );
		$previous = array();
		$ids      = array();
		foreach ( (array) $rows as $row ) {
			if ( (int) $row['readonly'] === 1 ) {
				continue;
			}
			if ( $rule !== '' && (string) $row['rule'] !== $rule ) {
				continue;
			}
			$previous[] = (string) $row['rule'];
			$ids[]      = (int) $row['id'];
		}
		if ( empty( $ids ) && ! empty( $rows ) ) {
			return new WP_Error( 'ability_readonly_rule', __( 'Only readonly rules match this target.', 'botblocker-security' ) );
		}
		if ( $dry_run ) {
			return array(
				'deleted'            => count( $ids ),
				'previous_rules'     => $previous,
				'applied'            => false,
				'protection_enabled' => (int) bbcs_wp_ability_read_setting( 'disable', 0 ) !== 1,
			);
		}

		$deleted = 0;
		foreach ( $ids as $id ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$result = $wpdb->delete( $table, array( 'id' => $id ), array( '%d' ) );
			if ( false !== $result && $result > 0 ) {
				++$deleted;
			}
		}
		BotBlockerFileRenderer::removeHotBan( (string) $target['search'] );
		BotBlockerFileRenderer::renderIps();
		BotBlockerCache::clearFileCache();
		bbcs_wp_ability_audit( 'botblocker/delete-ip-rules', 'rules_deleted', array( 'ip' => (string) $target['search'], 'deleted' => $deleted ) );

		return array(
			'deleted'            => $deleted,
			'previous_rules'     => $previous,
			'applied'            => true,
			'protection_enabled' => (int) bbcs_wp_ability_read_setting( 'disable', 0 ) !== 1,
		);
	}

	function bbcs_wp_ability_execute_set_country_rule( $input = null ) {
		global $wpdb;

		$guard = bbcs_wp_ability_guard( 'botblocker/set-country-rule', $input, 'COUNTRY', true );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}
		$code = strtoupper( bbcs_ability_input_string( $input, 'code' ) );
		if ( ! preg_match( '/^[A-Z]{2}$/', $code ) ) {
			return new WP_Error( 'ability_invalid_input', __( 'A two-letter country code is required.', 'botblocker-security' ) );
		}
		$action = bbcs_ability_input_enum( $input, 'action', array( 'disable', 'rule' ), 'rule' );
		$rule   = bbcs_ability_input_enum( $input, 'rule', array( BBCS_RULE_ALLOW, BBCS_RULE_BLOCK ), BBCS_RULE_BLOCK );
		$force  = bbcs_wp_ability_input_bool( $input, 'force', false );

		if ( $action === 'rule' && $rule === BBCS_RULE_BLOCK && ! $force && bbcs_wp_ability_request_country() === $code ) {
			return new WP_Error( 'ability_self_block_requires_force', __( 'Blocking the request country requires force.', 'botblocker-security' ) );
		}

		$table = isset( $wpdb->bbcs_countries ) ? (string) $wpdb->bbcs_countries : '';
		if ( $table === '' ) {
			return new WP_Error( 'ability_unavailable', __( 'The country rule table is not available.', 'botblocker-security' ) );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- table from $wpdb.
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT `id`, `rule`, `disable` FROM `{$table}` WHERE `code` = %s LIMIT 1", $code ), ARRAY_A );
		$created  = ! is_array( $existing );
		$previous = array(
			'rule'    => is_array( $existing ) ? (string) $existing['rule'] : '',
			'disable' => is_array( $existing ) ? (int) $existing['disable'] : 1,
		);
		$new_disable = $action === 'disable' ? 1 : 0;
		$new_rule    = $action === 'disable' && is_array( $existing ) ? (string) $existing['rule'] : $rule;
		$changed     = $created || $previous['rule'] !== $new_rule || $previous['disable'] !== $new_disable;
		$dry_run     = bbcs_wp_ability_is_dry_run( $input );

		if ( ! $dry_run && $changed ) {
			$data = array(
				'priority' => 50,
				'code'     => $code,
				'rule'     => $new_rule,
				'comment'  => bbcs_ability_input_string( $input, 'comment' ),
				'disable'  => $new_disable,
			);
			if ( is_array( $existing ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$saved = $wpdb->update( $table, $data, array( 'id' => (int) $existing['id'] ) );
			} else {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$saved = $wpdb->insert( $table, $data );
			}
			if ( false === $saved ) {
				return new WP_Error( 'ability_execution_failed', __( 'The country rule could not be saved.', 'botblocker-security' ) );
			}
			BotBlockerFileRenderer::renderCountries();
			BotBlockerCache::clearFileCache();
			bbcs_wp_ability_audit( 'botblocker/set-country-rule', 'country_rule_saved', array( 'code' => $code, 'rule' => $new_rule, 'disable' => $new_disable ) );
		}

		return array(
			'code'    => $code,
			'action'  => $action,
			'rule'    => $new_rule,
			'created' => $created,
			'changed' => $changed,
			'applied' => ! $dry_run && $changed,
		);
	}

	function bbcs_wp_ability_execute_set_asn_rule( $input = null ) {
		global $wpdb;

		$guard = bbcs_wp_ability_guard( 'botblocker/set-asn-rule', $input, 'ASN', true );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}
		$asnum = bbcs_wp_ability_input_int( $input, 'asnum', 0 );
		if ( $asnum <= 0 ) {
			return new WP_Error( 'ability_invalid_input', __( 'A positive ASN is required.', 'botblocker-security' ) );
		}
		$action = bbcs_ability_input_enum( $input, 'action', array( 'disable', 'rule' ), 'rule' );
		$rule   = bbcs_ability_input_enum( $input, 'rule', array( BBCS_RULE_ALLOW, BBCS_RULE_BLOCK ), BBCS_RULE_BLOCK );
		$force  = bbcs_wp_ability_input_bool( $input, 'force', false );

		if ( $action === 'rule' && $rule === BBCS_RULE_BLOCK && ! $force && class_exists( 'BotBlockerAsnDb' ) ) {
			$lookup = BotBlockerAsnDb::lookup( BotBlockerIp::getCurrentIp() );
			if ( is_array( $lookup ) && isset( $lookup['asn'] ) && (int) $lookup['asn'] === $asnum ) {
				return new WP_Error( 'ability_self_block_requires_force', __( 'Blocking the request ASN requires force.', 'botblocker-security' ) );
			}
		}

		$table = isset( $wpdb->bbcs_asn ) ? (string) $wpdb->bbcs_asn : '';
		if ( $table === '' ) {
			return new WP_Error( 'ability_unavailable', __( 'The ASN rule table is not available.', 'botblocker-security' ) );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- table from $wpdb.
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT `id`, `rule`, `disable`, `asname` FROM `{$table}` WHERE `asnum` = %d LIMIT 1", $asnum ), ARRAY_A );
		$created  = ! is_array( $existing );
		$previous = array(
			'rule'    => is_array( $existing ) ? (string) $existing['rule'] : '',
			'disable' => is_array( $existing ) ? (int) $existing['disable'] : 1,
		);
		$new_disable = $action === 'disable' ? 1 : 0;
		$new_rule    = $action === 'disable' && is_array( $existing ) ? (string) $existing['rule'] : $rule;
		$changed     = $created || $previous['rule'] !== $new_rule || $previous['disable'] !== $new_disable;
		$dry_run     = bbcs_wp_ability_is_dry_run( $input );

		if ( ! $dry_run && $changed ) {
			$asname = bbcs_ability_input_string( $input, 'asname', is_array( $existing ) ? (string) $existing['asname'] : '' );
			$data   = array(
				'priority' => 50,
				'asnum'    => $asnum,
				'asname'   => $asname,
				'rule'     => $new_rule,
				'comment'  => bbcs_ability_input_string( $input, 'comment' ),
				'disable'  => $new_disable,
			);
			if ( is_array( $existing ) ) {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$saved = $wpdb->update( $table, $data, array( 'id' => (int) $existing['id'] ) );
			} else {
				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
				$saved = $wpdb->insert( $table, $data );
			}
			if ( false === $saved ) {
				return new WP_Error( 'ability_execution_failed', __( 'The ASN rule could not be saved.', 'botblocker-security' ) );
			}
			BotBlockerFileRenderer::renderAsn();
			BotBlockerCache::clearFileCache();
			bbcs_wp_ability_audit( 'botblocker/set-asn-rule', 'asn_rule_saved', array( 'asnum' => $asnum, 'rule' => $new_rule, 'disable' => $new_disable ) );
		}

		return array(
			'asnum'   => $asnum,
			'action'  => $action,
			'rule'    => $new_rule,
			'created' => $created,
			'changed' => $changed,
			'applied' => ! $dry_run && $changed,
		);
	}

	function bbcs_wp_ability_execute_update_threat_data( $input = null ) {
		$guard = bbcs_wp_ability_guard( 'botblocker/update-threat-data', $input, 'UPDATE', true );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}
		$source  = bbcs_ability_input_enum( $input, 'source', array( 'asn', 'llm', 'tls' ), 'asn' );
		$dry_run = bbcs_wp_ability_is_dry_run( $input );
		$previous = array();
		$status   = array();
		$updated  = false;

		if ( 'asn' === $source ) {
			if ( ! class_exists( 'BotBlockerAsnDb' ) ) {
				return new WP_Error( 'ability_unavailable', __( 'The ASN database component is not available.', 'botblocker-security' ) );
			}
			$previous = (array) BotBlockerAsnDb::getStatus();
			if ( ! $dry_run ) {
				$updated = (bool) BotBlockerAsnDb::download( 'ability' );
			}
			$status = (array) BotBlockerAsnDb::getStatus();
		} elseif ( 'llm' === $source ) {
			if ( ! class_exists( 'BotBlockerLlmSync' ) && defined( 'BOTBLOCKER_DIR' ) ) {
				require_once BOTBLOCKER_DIR . 'includes/cache/class-botblocker-llm-sync.php';
			}
			if ( ! class_exists( 'BotBlockerLlmSync' ) ) {
				return new WP_Error( 'ability_unavailable', __( 'The LLM sync component is not available.', 'botblocker-security' ) );
			}
			$previous = BotBlockerLlmSync::getStatus();
			if ( ! $dry_run ) {
				$updated = (bool) BotBlockerLlmSync::doSync( 'ability', true );
			}
			$status = BotBlockerLlmSync::getStatus();
		} else {
			if ( ! class_exists( 'BotBlockerTlsFingerprintsSync' ) && defined( 'BOTBLOCKER_DIR' ) ) {
				require_once BOTBLOCKER_DIR . 'includes/cache/class-botblocker-tls-fingerprints-sync.php';
			}
			if ( ! class_exists( 'BotBlockerTlsFingerprintsSync' ) ) {
				return new WP_Error( 'ability_unavailable', __( 'The TLS fingerprint sync component is not available.', 'botblocker-security' ) );
			}
			$previous = BotBlockerTlsFingerprintsSync::getStatus();
			if ( ! $dry_run ) {
				$updated = (bool) BotBlockerTlsFingerprintsSync::doSync( 'ability', true );
			}
			$status = BotBlockerTlsFingerprintsSync::getStatus();
		}

		if ( ! $dry_run ) {
			bbcs_wp_ability_audit( 'botblocker/update-threat-data', $updated ? 'threat_data_updated' : 'threat_data_failed', array( 'source' => $source ) );
		}

		return array(
			'source'   => $source,
			'updated'  => $updated,
			'applied'  => ! $dry_run && $updated,
			'dry_run'  => $dry_run,
			'previous' => $previous,
			'status'   => $status,
		);
	}

	function bbcs_wp_ability_execute_export_settings( $input = null ) {
		$guard = bbcs_wp_ability_guard( 'botblocker/export-settings', $input );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}
		$settings = BotBlocker::getInstance()->settings;
		if ( ! is_object( $settings ) ) {
			return new WP_Error( 'ability_unavailable', __( 'Settings are not available.', 'botblocker-security' ) );
		}
		$group = bbcs_ability_input_string( $input, 'group', 'all' );
		$map   = array(
			'protection'   => array( 'disable', 'secure_mode', 'bbcs_honeypot_enabled', 'block_proxy_users', 'block_tor_users', 'block_vpn_users', 'block_empty_ua', 'block_fake_ref', 'skip_logged_in_users' ),
			'captcha'      => array( 'bbcs_captcha_mode', 'bbcs_captcha_wait', 'bbcs_captcha_img_pack', 'bbcs_captcha_img_inline' ),
			'logging'      => array( 'botblocker_log_error', 'botblocker_log_admin', 'botblocker_log_allow', 'botblocker_log_block', 'audit_log_enable', 'audit_log_retention_days' ),
			'integrations' => array( 'bbcs_wp_abilities_enabled', 'bbcs_wp_connectors_enabled', 'redis_enable', 'memcached_enable', 'apcu_enable', 'transients_enable' ),
		);
		$values  = get_object_vars( $settings );
		$allowed = ( 'all' === $group || ! isset( $map[ $group ] ) ) ? array_keys( $values ) : $map[ $group ];
		$mask    = bbcs_ability_mask_enabled( $input );
		$secrets = array( 'recaptcha_key2', 'recaptcha_secret2', 'recaptcha_key3', 'recaptcha_secret3', 'recaptcha_secret', 'cloud_api_key', 'cloud_api_pass', 'cloud_api_secret', 'bbcs_antispam_api_key', 'secret_botblocker_get_param', 'redis_password' );
		$out     = array();
		$redacted = array();
		foreach ( $allowed as $key ) {
			if ( ! array_key_exists( $key, $values ) ) {
				continue;
			}
			if ( in_array( $key, $secrets, true ) || ( class_exists( 'BotBlockerAuditLogger' ) && BotBlockerAuditLogger::isSensitiveKey( (string) $key ) ) ) {
				$out[ $key ] = '[redacted]';
				$redacted[]  = (string) $key;
				continue;
			}
			$value = $values[ $key ];
			if ( $mask && is_string( $value ) && stripos( (string) $key, 'email' ) !== false ) {
				$value = bbcs_ability_mask_email( $value );
				$redacted[] = (string) $key;
			}
			$out[ $key ] = $value;
		}
		return array(
			'settings' => $out,
			'redacted' => $redacted,
			'masked'   => $mask,
		);
	}

	function bbcs_wp_ability_execute_apply_preset( $input = null ) {
		global $wpdb;

		$guard = bbcs_wp_ability_guard( 'botblocker/apply-preset', $input, 'PRESET', true );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}
		if ( ! class_exists( 'BotBlockerSettingsPresets' ) ) {
			return new WP_Error( 'ability_unavailable', __( 'Settings presets are not available.', 'botblocker-security' ) );
		}
		$preset = bbcs_ability_input_enum( $input, 'preset', array( 'light', 'strong', 'full' ), '' );
		if ( $preset === '' ) {
			return new WP_Error( 'ability_invalid_input', __( 'A preset is required.', 'botblocker-security' ) );
		}
		if ( empty( $wpdb->bbcs_settings ) ) {
			return new WP_Error( 'ability_unavailable', __( 'The settings table is not available.', 'botblocker-security' ) );
		}

		$settings = BotBlocker::getInstance()->settings;
		$snapshot = array();
		if ( is_object( $settings ) ) {
			foreach ( get_object_vars( $settings ) as $key => $value ) {
				if ( strpos( (string) $key, 'bbcs_ability_' ) === 0 || $key === 'bbcs_wp_abilities_enabled' ) {
					if ( ! is_bool( $value ) && ! is_int( $value ) && ! is_string( $value ) && $value !== null ) {
						return new WP_Error( 'ability_execution_failed', __( 'The ability toggle snapshot could not be taken.', 'botblocker-security' ) );
					}
					$snapshot[ (string) $key ] = (string) (int) $value;
				}
			}
		}
		if ( empty( $snapshot ) ) {
			return new WP_Error( 'ability_execution_failed', __( 'The ability toggle snapshot is empty; refusing to apply a preset.', 'botblocker-security' ) );
		}
		$dry_run = bbcs_wp_ability_is_dry_run( $input );
		if ( $dry_run ) {
			return array(
				'preset'             => $preset,
				'applied'            => false,
				'restored_toggles'   => 0,
				'protection_enabled' => (int) bbcs_wp_ability_read_setting( 'disable', 0 ) !== 1,
			);
		}

		if ( 'light' === $preset ) {
			BotBlockerSettingsPresets::loadSettingsLight();
		} elseif ( 'strong' === $preset ) {
			BotBlockerSettingsPresets::loadSettingsStrong();
		} else {
			BotBlockerSettingsPresets::loadSettingsFull();
		}

		$restored = 0;
		foreach ( $snapshot as $key => $value ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$result = $wpdb->replace( $wpdb->bbcs_settings, array( 'key' => $key, 'value' => $value ), array( '%s', '%s' ) );
			if ( false === $result ) {
				BotBlockerFileRenderer::generateSettingsFile();
				return new WP_Error( 'ability_execution_failed', __( 'The ability toggles could not be restored after applying the preset.', 'botblocker-security' ) );
			}
			if ( is_object( $settings ) ) {
				$settings->$key = (int) $value;
			}
			++$restored;
		}
		BotBlockerFileRenderer::generateSettingsFile();
		BotBlockerCache::clearFileCache();
		bbcs_wp_ability_audit( 'botblocker/apply-preset', 'preset_applied', array( 'preset' => $preset, 'restored' => $restored ) );

		return array(
			'preset'             => $preset,
			'applied'            => true,
			'restored_toggles'   => $restored,
			'protection_enabled' => (int) bbcs_wp_ability_read_setting( 'disable', 0 ) !== 1,
		);
	}
}
