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

		$guard = bbcs_wp_ability_guard( 'botblocker/delete-ip-rules', $input, bbcs_ability_confirm_token( 'botblocker/delete-ip-rules' ), true );
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
				'deleted'            => 0,
				'previous_rules'     => $previous,
				'changed'            => ! empty( $ids ),
				'applied'            => false,
				'dry_run'            => true,
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
			'changed'            => ! empty( $ids ),
			'applied'            => $deleted > 0,
			'dry_run'            => false,
			'protection_enabled' => (int) bbcs_wp_ability_read_setting( 'disable', 0 ) !== 1,
		);
	}

	function bbcs_wp_ability_execute_set_country_rule( $input = null ) {
		global $wpdb;

		$guard = bbcs_wp_ability_guard( 'botblocker/set-country-rule', $input, bbcs_ability_confirm_token( 'botblocker/set-country-rule' ), true );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}
		$code = strtoupper( bbcs_ability_input_string( $input, 'code' ) );
		if ( ! preg_match( '/^[A-Z]{2}$/', $code ) ) {
			return new WP_Error( 'ability_invalid_input', __( 'A two-letter country code is required.', 'botblocker-security' ) );
		}
		$action = bbcs_ability_input_enum( $input, 'action', array( 'disable', 'rule' ), 'rule' );
		$rule   = bbcs_ability_input_string( $input, 'rule', BBCS_RULE_BLOCK );
		if ( ! in_array( $rule, array( BBCS_RULE_ALLOW, BBCS_RULE_BLOCK ), true ) ) {
			return new WP_Error( 'ability_invalid_input', __( 'Country rules accept allow or block only.', 'botblocker-security' ) );
		}
		$force  = bbcs_wp_ability_input_bool( $input, 'force', false );

		if ( $action === 'rule' && $rule === BBCS_RULE_BLOCK && ! $force && bbcs_wp_ability_request_country() === $code ) {
			return new WP_Error( 'ability_self_block_requires_force', __( 'Blocking the request country requires force.', 'botblocker-security' ) );
		}

		$table = isset( $wpdb->bbcs_countries ) ? (string) $wpdb->bbcs_countries : '';
		if ( $table === '' ) {
			return new WP_Error( 'ability_unavailable', __( 'The country rule table is not available.', 'botblocker-security' ) );
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, PluginCheck.Security.DirectDB.UnescapedDBParameter -- table from $wpdb.
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT `id`, `rule`, `disable`, `comment` FROM `{$table}` WHERE `code` = %s LIMIT 1", $code ), ARRAY_A );
		$created  = ! is_array( $existing );
		$previous = array(
			'rule'    => is_array( $existing ) ? (string) $existing['rule'] : '',
			'disable' => is_array( $existing ) ? (int) $existing['disable'] : 1,
		);
		$previous_rule     = (string) $previous['rule'];
		$previous_disabled = 1 === (int) $previous['disable'];
		$previous_comment  = is_array( $existing ) ? (string) $existing['comment'] : '';
		$comment           = bbcs_ability_input_string( $input, 'comment', $previous_comment );
		$new_disable       = $action === 'disable' ? 1 : 0;
		$new_rule          = $action === 'disable' && is_array( $existing ) ? (string) $existing['rule'] : $rule;
		$changed           = $created || $previous['rule'] !== $new_rule || $previous['disable'] !== $new_disable || $previous_comment !== $comment;
		$dry_run           = bbcs_wp_ability_is_dry_run( $input );

		if ( ! $dry_run && $changed ) {
			$data = array(
				'priority' => 50,
				'code'     => $code,
				'rule'     => $new_rule,
				'comment'  => $comment,
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
			'code'              => $code,
			'action'            => $action,
			'rule'              => $new_rule,
			'previous_rule'     => $previous_rule,
			'previous_disabled' => $previous_disabled,
			'created'           => $dry_run ? false : $created,
			'changed'           => $changed,
			'applied'           => ! $dry_run && $changed,
			'dry_run'           => $dry_run,
		);
	}

	function bbcs_wp_ability_execute_set_asn_rule( $input = null ) {
		global $wpdb;

		$guard = bbcs_wp_ability_guard( 'botblocker/set-asn-rule', $input, bbcs_ability_confirm_token( 'botblocker/set-asn-rule' ), true );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}
		$asnum = bbcs_wp_ability_input_int( $input, 'asnum', 0 );
		if ( $asnum <= 0 ) {
			return new WP_Error( 'ability_invalid_input', __( 'A positive ASN is required.', 'botblocker-security' ) );
		}
		$action = bbcs_ability_input_enum( $input, 'action', array( 'disable', 'rule' ), 'rule' );
		$rule   = bbcs_ability_input_string( $input, 'rule', BBCS_RULE_BLOCK );
		if ( ! in_array( $rule, array( BBCS_RULE_ALLOW, BBCS_RULE_BLOCK ), true ) ) {
			return new WP_Error( 'ability_invalid_input', __( 'ASN rules accept allow or block only.', 'botblocker-security' ) );
		}
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
		$existing = $wpdb->get_row( $wpdb->prepare( "SELECT `id`, `rule`, `disable`, `asname`, `comment` FROM `{$table}` WHERE `asnum` = %d LIMIT 1", $asnum ), ARRAY_A );
		$created  = ! is_array( $existing );
		$previous = array(
			'rule'    => is_array( $existing ) ? (string) $existing['rule'] : '',
			'disable' => is_array( $existing ) ? (int) $existing['disable'] : 1,
		);
		$previous_rule     = (string) $previous['rule'];
		$previous_disabled = 1 === (int) $previous['disable'];
		$previous_comment  = is_array( $existing ) ? (string) $existing['comment'] : '';
		$previous_asname   = is_array( $existing ) ? (string) $existing['asname'] : '';
		$comment           = bbcs_ability_input_string( $input, 'comment', $previous_comment );
		$asname            = bbcs_ability_input_string( $input, 'asname', $previous_asname );
		$new_disable       = $action === 'disable' ? 1 : 0;
		$new_rule          = $action === 'disable' && is_array( $existing ) ? (string) $existing['rule'] : $rule;
		$changed           = $created || $previous['rule'] !== $new_rule || $previous['disable'] !== $new_disable || $previous_comment !== $comment || $previous_asname !== $asname;
		$dry_run           = bbcs_wp_ability_is_dry_run( $input );

		if ( ! $dry_run && $changed ) {
			$data = array(
				'priority' => 50,
				'asnum'    => $asnum,
				'asname'   => $asname,
				'rule'     => $new_rule,
				'comment'  => $comment,
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
			'asnum'             => $asnum,
			'action'            => $action,
			'rule'              => $new_rule,
			'previous_rule'     => $previous_rule,
			'previous_disabled' => $previous_disabled,
			'created'           => $dry_run ? false : $created,
			'changed'           => $changed,
			'applied'           => ! $dry_run && $changed,
			'dry_run'           => $dry_run,
		);
	}

	function bbcs_wp_ability_execute_update_threat_data( $input = null ) {
		$guard = bbcs_wp_ability_guard( 'botblocker/update-threat-data', $input, bbcs_ability_confirm_token( 'botblocker/update-threat-data' ), true );
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
			'applied'  => ! $dry_run && $updated,
			'dry_run'  => $dry_run,
			'previous' => is_array( $previous ) ? $previous : array(),
			'status'   => is_array( $status ) ? $status : array(),
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
		if ( ! in_array( $group, bbcs_ability_export_groups(), true ) ) {
			return new WP_Error( 'ability_invalid_input', __( 'The group must be protection, captcha, logging, integrations or all.', 'botblocker-security' ) );
		}
		$map   = array(
			'protection'   => array( 'disable', 'secure_mode', 'bbcs_honeypot_enabled', 'block_proxy_users', 'block_tor_users', 'block_vpn_users', 'block_empty_ua', 'block_fake_ref', 'skip_logged_in_users' ),
			'captcha'      => array( 'bbcs_captcha_mode', 'bbcs_captcha_wait', 'bbcs_captcha_img_pack', 'bbcs_captcha_img_inline' ),
			'logging'      => array( 'botblocker_log_error', 'botblocker_log_admin', 'botblocker_log_allow', 'botblocker_log_block', 'audit_log_enable', 'audit_log_retention_days' ),
			'integrations' => array( 'bbcs_wp_abilities_enabled', 'bbcs_wp_connectors_enabled', 'redis_enable', 'memcached_enable', 'apcu_enable', 'transients_enable' ),
		);
		$values  = get_object_vars( $settings );
		$allowed = 'all' === $group ? array_keys( $values ) : $map[ $group ];
		$out     = array();
		$redacted = array();
		foreach ( $allowed as $key ) {
			if ( ! array_key_exists( $key, $values ) ) {
				continue;
			}
			if ( bbcs_ability_setting_is_redacted( (string) $key ) ) {
				$out[ $key ] = '[redacted]';
				$redacted[]  = (string) $key;
				continue;
			}
			$out[ $key ] = $values[ $key ];
		}
		return array(
			'group'         => $group,
			'settings'      => $out,
			'redacted_keys' => $redacted,
			'masked'        => true,
		);
	}

	function bbcs_wp_ability_execute_apply_preset( $input = null ) {
		global $wpdb;

		$guard = bbcs_wp_ability_guard( 'botblocker/apply-preset', $input, bbcs_ability_confirm_token( 'botblocker/apply-preset' ), true );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}
		if ( ! class_exists( 'BotBlockerSettingsPresets' ) ) {
			$preset_class = BOTBLOCKER_DIR . 'includes/pro/class-botblocker-settings-presets.php';
			if ( is_readable( $preset_class ) ) {
				require_once $preset_class;
			}
		}
		if ( ! class_exists( 'BotBlockerSettingsPresets' ) ) {
			return new WP_Error( 'ability_unavailable', __( 'Settings presets are not available.', 'botblocker-security' ) );
		}
		$preset = bbcs_ability_input_enum( $input, 'preset', array( 'light', 'medium', 'strong', 'strong_api', 'full', 'emergency' ), '' );
		if ( $preset === '' ) {
			return new WP_Error( 'ability_invalid_input', __( 'A preset is required.', 'botblocker-security' ) );
		}
		if ( in_array( $preset, array( 'full', 'emergency' ), true ) && ! ( class_exists( 'BotBlockerPro' ) && BotBlockerPro::isActive() ) ) {
			return new WP_Error( 'ability_forbidden', __( 'This preset requires an active PRO license.', 'botblocker-security' ) );
		}
		if ( empty( $wpdb->bbcs_settings ) ) {
			return new WP_Error( 'ability_unavailable', __( 'The settings table is not available.', 'botblocker-security' ) );
		}

		$settings = BotBlocker::getInstance()->settings;
		$previous_preset = BotBlockerSettingsPresets::detectPreset( $settings );
		$changed         = $previous_preset !== $preset;
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
				'previous_preset'    => $previous_preset,
				'changed'            => $changed,
				'applied'            => false,
				'dry_run'            => true,
				'restored_toggles'   => 0,
				'protection_enabled' => (int) bbcs_wp_ability_read_setting( 'disable', 0 ) !== 1,
			);
		}

		if ( 'light' === $preset ) {
			BotBlockerSettingsPresets::loadSettingsLight();
		} elseif ( 'medium' === $preset ) {
			BotBlockerSettingsPresets::loadSettingsMedium();
		} elseif ( 'strong' === $preset ) {
			BotBlockerSettingsPresets::loadSettingsStrong();
		} elseif ( 'strong_api' === $preset ) {
			BotBlockerSettingsPresets::loadSettingsStrongApi();
		} elseif ( 'emergency' === $preset ) {
			BotBlockerSettingsPresets::loadSettingsEmergency();
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
		if ( class_exists( 'BotBlockerMultisite' ) ) {
			BotBlockerMultisite::updateOption( 'bbcs_wizard_preset', $preset );
		}
		bbcs_wp_ability_audit( 'botblocker/apply-preset', 'preset_applied', array( 'preset' => $preset, 'restored' => $restored ) );

		return array(
			'preset'             => $preset,
			'previous_preset'    => $previous_preset,
			'changed'            => $changed,
			'applied'            => true,
			'dry_run'            => false,
			'restored_toggles'   => $restored,
			'protection_enabled' => (int) bbcs_wp_ability_read_setting( 'disable', 0 ) !== 1,
		);
	}
}

if ( ! function_exists( 'bbcs_wp_ability_setting_switch_keys' ) ) {

	function bbcs_wp_ability_setting_switch_keys(): array {
		return array(
			'allow_app_password_rest',
			'allow_self_call_header',
			'allow_self_ip_req',
			'apcu_enable',
			'audit_log_enable',
			'autosave_admin_ip',
			'bbcs_2fa_enable',
			'bbcs_2fa_xmlrpc_block',
			'bbcs_allow_empty_accept_lang',
			'bbcs_captcha_img_inline',
			'bbcs_cors_strict_headers',
			'bbcs_ddos_resilience',
			'bbcs_edge_autotrust',
			'bbcs_honeypot_enabled',
			'bbcs_rate_check_enabled',
			'bbcs_hide_user_enumeration',
			'bbcs_strip_php_version',
			'bbcs_rate_subnet_enabled',
			'block_adblocker_users',
			'block_cf_users',
			'block_device_options',
			'block_empty_lang',
			'block_empty_ua',
			'block_fake_ref',
			'block_http10_users',
			'block_incognito_users',
			'block_incorrect_lang_users',
			'block_ip_ptr_match',
			'block_ipv6_users',
			'block_override',
			'block_proxy_users',
			'block_rkn',
			'block_simple_antidetect',
			'block_simplebot_ua',
			'block_tor_users',
			'block_vpn_users',
			'block_web_engine_options',
			'botblocker_force_check',
			'botblocker_log_admin',
			'botblocker_log_allow',
			'botblocker_log_bbcs',
			'botblocker_log_block',
			'botblocker_log_cli',
			'botblocker_log_disabled',
			'botblocker_log_error',
			'botblocker_log_fake',
			'botblocker_log_goodip',
			'botblocker_log_local',
			'botblocker_log_tests',
			'botblocker_log_wp',
			'cache_ui_data',
			'check',
			'check_get_ref',
			'cloud_fallback_block',
			'early_cache_gate',
			'early_geo_enable',
			'early_init_enable',
			'fingerprint_sticky_block',
			'force_cloud_validation',
			'get_browser_type',
			'get_device_type',
			'get_os_type',
			'hosting_block',
			'iframe_stop',
			'login_brutforce_enabled',
			'memcached_enable',
			'mu_enable',
			'mu_geo_enable',
			'noarchive',
			'options_preflight',
			'payment_bypass_enable',
			'payment_bypass_log',
			'payment_keep_ip_rules',
			'payment_strict_method',
			'ptr_cache_in_db',
			'recaptcha_check',
			'recaptcha_v3_ipv6_block',
			'redis_enable',
			'session_token_enabled',
			'skip_logged_in_users',
			'tls_fingerprint_check',
			'transients_enable',
			'utm_noindex',
			'utm_referrer',
			'vary_cookie',
		);
	}

	function bbcs_wp_ability_setting_switch_allowed( string $key ): bool {
		if ( '' === $key || ! in_array( $key, bbcs_wp_ability_setting_switch_keys(), true ) ) {
			return false;
		}
		// The presets map is an admin-side class and is not loaded on REST or
		// MCP requests; the published switch list is authoritative on its own.
		if ( ! class_exists( 'BotBlockerSettingsPresets' ) ) {
			return true;
		}
		return in_array( $key, BotBlockerSettingsPresets::getAllowedFields(), true );
	}

	function bbcs_wp_ability_cache_backend_probe( string $key ) {
		if ( ! class_exists( 'BotBlockerCache' ) ) {
			return new WP_Error( 'ability_unavailable', __( 'The cache backend check is not available.', 'botblocker-security' ) );
		}
		if ( 'redis_enable' === $key ) {
			$probe = BotBlockerCache::testRedisConnection();
		} elseif ( 'memcached_enable' === $key ) {
			$probe = BotBlockerCache::testMemcachedConnection();
		} else {
			$probe = BotBlockerCache::testApcuConnection();
		}
		if ( ! empty( $probe['ok'] ) ) {
			return true;
		}
		return new WP_Error( 'ability_backend_unavailable', __( 'The cache backend did not answer a connection probe, so the switch was left off.', 'botblocker-security' ) );
	}

	function bbcs_wp_ability_apply_early_phase( string $key, int $value ): void {
		if ( ! class_exists( 'BotBlockerEarlyPhaseDedup' ) ) {
			return;
		}
		$state = BotBlockerEarlyPhaseDedup::dedup( $key, $value );
		$bbcs  = BotBlocker::getInstance();
		if ( is_object( $bbcs->settings ) ) {
			$bbcs->settings->early_init_enable = (int) $state['early_init_enable'];
			$bbcs->settings->mu_enable         = (int) $state['mu_enable'];
		}
		if ( class_exists( 'BotBlockerFileRenderer' ) ) {
			BotBlockerFileRenderer::generateSettingsFile();
		}
		if ( ! class_exists( 'BotBlockerInstall' ) ) {
			return;
		}
		if ( 1 === (int) $state['early_init_enable'] ) {
			BotBlockerInstall::setEarlyInitEnabled( true );
			return;
		}
		if ( 1 === (int) $state['mu_enable'] ) {
			BotBlockerInstall::setEarlyInitEnabled( false, array( 'reason' => 'mu_switch' ) );
			BotBlockerInstall::installMuPlugin();
			return;
		}
		BotBlockerInstall::setEarlyInitEnabled( false );
		BotBlockerInstall::uninstallMuPlugin();
	}

	function bbcs_wp_ability_execute_set_setting( $input = null ) {
		$guard = bbcs_wp_ability_guard( 'botblocker/set-setting', $input, bbcs_ability_confirm_token( 'botblocker/set-setting' ), true );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}

		$key = bbcs_ability_input_string( $input, 'key', '' );
		if ( ! bbcs_wp_ability_setting_switch_allowed( $key ) ) {
			return new WP_Error( 'ability_invalid_input', __( 'That setting is not a switch this ability can change.', 'botblocker-security' ) );
		}
		if ( ! is_array( $input ) || ! array_key_exists( 'enabled', $input ) ) {
			return new WP_Error( 'ability_invalid_input', __( 'The enabled flag is required.', 'botblocker-security' ) );
		}

		$enabled  = bbcs_wp_ability_input_bool( $input, 'enabled', false );
		$previous = 1 === (int) bbcs_wp_ability_read_setting( $key, 0 );
		$changed  = $previous !== $enabled;
		$dry_run  = bbcs_wp_ability_is_dry_run( $input );
		$value    = $enabled ? 1 : 0;
		$reason   = ( is_array( $input ) && isset( $input['reason'] ) && is_scalar( $input['reason'] ) ) ? sanitize_text_field( (string) $input['reason'] ) : '';

		if ( $enabled && class_exists( 'BotBlockerPro' ) && ! BotBlockerPro::allowsFeature( $key ) ) {
			return new WP_Error( 'ability_forbidden', __( 'The license does not allow enabling this setting.', 'botblocker-security' ) );
		}

		if ( $changed && ! $dry_run ) {
			if ( $enabled && in_array( $key, array( 'redis_enable', 'memcached_enable', 'apcu_enable' ), true ) ) {
				$probe = bbcs_wp_ability_cache_backend_probe( $key );
				if ( is_wp_error( $probe ) ) {
					return $probe;
				}
			}
			if ( ! bbcs_wp_ability_write_setting( $key, $value ) ) {
				return new WP_Error( 'ability_execution_failed', __( 'The setting could not be saved.', 'botblocker-security' ) );
			}
			if ( in_array( $key, array( 'early_init_enable', 'mu_enable' ), true ) ) {
				bbcs_wp_ability_apply_early_phase( $key, $value );
			}
			if ( 'block_rkn' === $key && $enabled && class_exists( 'BotBlockerRugov' ) ) {
				BotBlockerRugov::scheduleUpdate( 'enabled', 60 );
			}
			bbcs_wp_ability_audit(
				'botblocker/set-setting',
				'setting_changed',
				array(
					'setting' => $key,
					'from'    => $previous ? 'on' : 'off',
					'to'      => $enabled ? 'on' : 'off',
					'reason'  => $reason,
				)
			);
		}

		return array(
			'key'                => $key,
			'previous'           => $previous,
			'enabled'            => $enabled,
			'changed'            => $changed,
			'applied'            => $changed && ! $dry_run,
			'dry_run'            => $dry_run,
			'reason'             => $reason,
			'protection_enabled' => 1 !== (int) bbcs_wp_ability_read_setting( 'disable', 0 ),
		);
	}

	function bbcs_wp_ability_execute_remove_edge_trust( $input = null ) {
		$guard = bbcs_wp_ability_guard( 'botblocker/remove-edge-trust', $input, bbcs_ability_confirm_token( 'botblocker/remove-edge-trust' ), true );
		if ( is_wp_error( $guard ) ) {
			return $guard;
		}
		if ( ! class_exists( 'BotBlockerEdgeStats' ) ) {
			return new WP_Error( 'ability_unavailable', __( 'The edge trust component is not available.', 'botblocker-security' ) );
		}
		$mask = trim( bbcs_ability_input_string( $input, 'mask' ) );
		if ( '' === $mask || false === strpos( $mask, '/' ) ) {
			return new WP_Error( 'ability_invalid_input', __( 'An edge mask in CIDR form is required.', 'botblocker-security' ) );
		}
		$edges     = BotBlockerEdgeStats::autoTrustEntries();
		$remaining = count( (array) $edges );
		if ( ! isset( $edges[ $mask ] ) ) {
			return new WP_Error( 'ability_not_found', __( 'No auto-trusted edge matches this mask.', 'botblocker-security' ) );
		}
		$dry_run = bbcs_wp_ability_is_dry_run( $input );
		if ( $dry_run ) {
			return array(
				'deleted'   => 0,
				'mask'      => $mask,
				'remaining' => $remaining,
				'changed'   => true,
				'applied'   => false,
				'dry_run'   => true,
			);
		}
		if ( ! BotBlockerEdgeStats::removeAutoEdge( $mask ) ) {
			return new WP_Error( 'ability_execution_failed', __( 'The auto-trust file could not be updated.', 'botblocker-security' ) );
		}
		if ( class_exists( 'BotBlockerAuditLogger' ) && class_exists( 'BotBlockerAuditEvents' ) ) {
			BotBlockerAuditLogger::record(
				BotBlockerAuditEvents::EDGE_TRUST_REMOVED,
				array(
					'object_type' => 'edge',
					'data'        => array( 'mask' => $mask ),
				)
			);
		}
		BotBlockerCache::clearFileCache();

		return array(
			'deleted'   => 1,
			'mask'      => $mask,
			'remaining' => count( (array) BotBlockerEdgeStats::autoTrustEntries() ),
			'changed'   => true,
			'applied'   => true,
			'dry_run'   => false,
		);
	}
}
