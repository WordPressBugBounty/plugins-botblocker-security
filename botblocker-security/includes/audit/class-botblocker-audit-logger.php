<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BotBlockerAuditLogger {

	public const SEVERITY_INFO     = 100;
	public const SEVERITY_MEDIUM   = 300;
	public const SEVERITY_CRITICAL = 500;

	/** Stands in for a placeholder that resolved to nothing. */
	public const EMPTY_PLACEHOLDER = '—';

	private static $definitions_cache = null;

	private static $severity_aliases = array(
		'info'          => self::SEVERITY_INFO,
		'notice'        => self::SEVERITY_INFO,
		'low'           => self::SEVERITY_INFO,
		'warning'       => self::SEVERITY_MEDIUM,
		'medium'        => self::SEVERITY_MEDIUM,
		'critical'      => self::SEVERITY_CRITICAL,
		'high'          => self::SEVERITY_CRITICAL,
		'error'         => self::SEVERITY_CRITICAL,
	);

	/** @var array<string, true> */
	private static $dedup_keys = array();

	private static $actorless_events = array(
		BotBlockerAuditEvents::AUTH_LOGIN_FAILED,
		BotBlockerAuditEvents::USER_CREATED,
		BotBlockerAuditEvents::BOTBLOCKER_SECRET_BYPASS,
		BotBlockerAuditEvents::BOTBLOCKER_SECRET_OFF,
		BotBlockerAuditEvents::BOTBLOCKER_SECRET_ON,
	);

	/**
	 * Field names the sensors own. They carry structure, never a secret, so the needle
	 * tests below must not eat them: "keys" holds "key", "author" holds "auth".
	 *
	 * @var string[]
	 */
	private static $never_redacted = array(
		'keys', 'login', 'roles', 'role', 'from', 'to', 'options', 'taxonomy',
		'plugin', 'theme', 'title', 'post_type', 'status', 'changes', 'count',
		'settings', 'via', 'action', 'slug', 'event', 'ip', 'message', 'mime',
		'type', 'provider', 'list', 'id', 'imported', 'skipped', 'disabled',
		'email', 'user_exists', 'context', 'removed', 'deleted', 'old', 'new',
	);

	/** No ordinary word carries these by accident, so a substring hit is enough. */
	private static $sensitive_substrings = array(
		'password', 'passwd', 'secret', 'token', 'license', 'nonce', 'credential', 'cookie', 'apikey',
	);

	/** Short and collision-prone, so these match whole segments only ("author" is not "auth"). */
	private static $sensitive_segments = array(
		'key', 'keys', 'auth', 'salt', 'pass', 'hash',
	);

	/**
	 * Security-relevant WordPress options to audit, never display preferences.
	 *
	 * `false` logs the change without the old and new values.
	 *
	 * @var array<string, bool>
	 */
	private static $audited_options_allowlist = array(
		'blogname'                      => true,
		'blogdescription'               => true,
		'admin_email'                   => true,
		'siteurl'                       => true,
		'home'                          => true,
		'users_can_register'            => true,
		'default_role'                  => true,
		'blog_public'                   => true,
		'permalink_structure'           => true,
		'default_comment_status'        => true,
		'comment_registration'          => true,
		'comment_moderation'            => true,
		'require_name_email'            => true,
		'moderation_keys'               => false,
		'disallowed_keys'               => false,
		'upload_path'                   => true,
		'uploads_use_yearmonth_folders' => true,
		'template'                      => true,
		'stylesheet'                    => true,
		'active_plugins'                => false,
	);

	/**
	 * @param array<string, mixed> $payload
	 */
	public static function record( string $event_key, array $payload = array() ): bool {
		$event_key = preg_replace( '/[^a-z0-9._-]/', '', strtolower( trim( $event_key ) ) );

		if ( $event_key === '' ) {
			return false;
		}

		if ( BotBlockerAuditContext::isWriting() ) {
			return false;
		}

		if ( ! BotBlockerAudit::isEnabled() ) {
			return false;
		}

		// Auth sensors fire before the session exists, so they pass the actor explicitly.
		$actor_user_id = isset( $payload['actor_user_id'] )
			? (int) $payload['actor_user_id']
			: BotBlockerAuditContext::getActorUserId();

		if ( ! self::hasLoggableActor( $event_key, $actor_user_id ) ) {
			return false;
		}

		if ( isset( $payload['actor_role'] ) ) {
			$actor_role = (string) $payload['actor_role'];
		} elseif ( isset( $payload['actor_user_id'] ) ) {
			$actor_role = BotBlockerAuditContext::getRoleForUser( $actor_user_id );
		} else {
			$actor_role = BotBlockerAuditContext::getActorRole();
		}

		if ( ! BotBlockerAuditContext::isRoleEnabled( $actor_role ) ) {
			return false;
		}

		$dedup = self::buildDedupKey( $event_key, $payload, $actor_user_id );
		if ( isset( self::$dedup_keys[ $dedup ] ) ) {
			return false;
		}
		self::$dedup_keys[ $dedup ] = true;

		$definitions = self::getDefinitions();
		$definition  = isset( $definitions[ $event_key ] ) && is_array( $definitions[ $event_key ] )
			? $definitions[ $event_key ]
			: array();

		$severity = isset( $payload['severity'] )
			? $payload['severity']
			: ( isset( $definition['severity'] ) ? $definition['severity'] : self::SEVERITY_INFO );
		$severity = self::normalizeSeverity( $severity );

		$object_type = isset( $payload['object_type'] ) ? (string) $payload['object_type'] : '';
		$object_id   = isset( $payload['object_id'] ) ? (int) $payload['object_id'] : 0;

		if ( $object_type === '' && isset( $definition['object_type'] ) ) {
			$object_type = (string) $definition['object_type'];
		}

		$data = isset( $payload['data'] ) && is_array( $payload['data'] ) ? $payload['data'] : array();
		if ( isset( $payload['message'] ) ) {
			$data['message'] = (string) $payload['message'];
		}

		$data = self::redactData( $data );

		// Stored, not resolved at read time: deleting a user must not erase who did what.
		if ( isset( $payload['actor_username'] ) ) {
			$actor_username = (string) $payload['actor_username'];
		} else {
			$actor_username = BotBlockerAuditContext::getUsernameForUser( $actor_user_id );
		}

		$row = array(
			'event_key'      => $event_key,
			'severity'       => $severity,
			'actor_user_id'  => $actor_user_id,
			'actor_username' => $actor_username,
			'actor_role'     => $actor_role,
			'object_type'    => $object_type,
			'object_id'      => $object_id,
			'ip'             => isset( $payload['ip'] ) ? (string) $payload['ip'] : BotBlockerAuditContext::getIp(),
			'context'        => isset( $payload['context'] ) ? (string) $payload['context'] : BotBlockerAuditContext::getRequestChannel(),
			'request_path'   => BotBlockerAuditContext::getRequestPath(),
			'user_agent'     => isset( $payload['user_agent'] ) ? (string) $payload['user_agent'] : BotBlockerAuditContext::getUserAgent(),
			'data'           => wp_json_encode( $data, JSON_UNESCAPED_UNICODE ),
			'created_at'     => time(),
		);

		$row = apply_filters( 'bbcs_audit_event_before_write', $row, $event_key, $payload );
		if ( ! is_array( $row ) || empty( $row['event_key'] ) ) {
			return false;
		}

		BotBlockerAuditContext::beginWrite();
		try {
			return BotBlockerAuditRepository::insert( $row );
		} catch ( \Throwable $e ) {
			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				error_log( 'BotBlocker audit: could not record ' . $event_key . ' - ' . $e->getMessage() );
			}

			return false;
		} finally {
			BotBlockerAuditContext::endWrite();
		}
	}

	private static function hasLoggableActor( string $event_key, int $actor_user_id ): bool {
		if ( $actor_user_id !== BotBlockerAuditContext::ACTOR_UNIDENTIFIED ) {
			return true;
		}
		if ( BotBlockerAuditContext::getRequestChannel() === BotBlockerAuditContext::CHANNEL_CLI ) {
			return true;
		}
		return in_array( $event_key, self::$actorless_events, true );
	}

	/** @param mixed $severity */
	public static function normalizeSeverity( $severity ): int {
		if ( is_int( $severity ) || ( is_string( $severity ) && ctype_digit( $severity ) ) ) {
			$value = (int) $severity;
			return $value > 0 ? min( 1000, $value ) : self::SEVERITY_INFO;
		}

		if ( is_string( $severity ) ) {
			$key = strtolower( trim( $severity ) );
			if ( isset( self::$severity_aliases[ $key ] ) ) {
				return self::$severity_aliases[ $key ];
			}
		}

		return self::SEVERITY_INFO;
	}

	public static function severityLabel( $severity ): string {
		$value = self::normalizeSeverity( $severity );
		if ( $value >= self::SEVERITY_CRITICAL ) {
			return __( 'critical', 'botblocker-security' );
		}
		if ( $value >= self::SEVERITY_MEDIUM ) {
			return __( 'medium', 'botblocker-security' );
		}
		return __( 'info', 'botblocker-security' );
	}

	/**
	 * Caching waits for init, or an add-on registering the filter on init is frozen out.
	 *
	 * @return array<string, mixed>
	 */
	public static function getDefinitions(): array {
		if ( self::$definitions_cache !== null ) {
			return self::$definitions_cache;
		}

		$definitions = apply_filters( 'bbcs_audit_event_definitions', self::getDefaultDefinitions() );
		if ( ! is_array( $definitions ) ) {
			$definitions = self::getDefaultDefinitions();
		}

		if ( did_action( 'init' ) ) {
			self::$definitions_cache = $definitions;
		}

		return $definitions;
	}

	/**
	 * Templates live in the definitions map, not the row, so they stay translatable.
	 * Returns the bare event key if a placeholder has no value.
	 *
	 * @param array<string, mixed> $data
	 */
	public static function renderMessage( string $event_key, array $data ): string {
		$definitions = self::getDefinitions();
		$template    = '';
		if ( isset( $definitions[ $event_key ]['message'] ) ) {
			$template = (string) $definitions[ $event_key ]['message'];
		}

		if ( $template === '' ) {
			return $event_key;
		}

		if ( ! preg_match_all( '/%([a-z0-9_]+)%/i', $template, $matches ) ) {
			return $template;
		}

		$replacements = array();
		foreach ( $matches[1] as $name ) {
			if ( ! array_key_exists( $name, $data ) ) {
				return $event_key;
			}
			$value = $data[ $name ];
			if ( is_bool( $value ) ) {
				$value = $value ? 'yes' : 'no';
			} elseif ( is_array( $value ) ) {
				// A map carries its subject in the keys, a list in the values.
				$items = array_keys( $value ) === range( 0, count( $value ) - 1 )
					? $value
					: array_keys( $value );
				$items = array_filter( $items, 'is_scalar' );
				$value = implode( ', ', array_map( 'strval', array_slice( $items, 0, 5 ) ) );
				if ( count( $items ) > 5 ) {
					$value .= ' …';
				}
			} elseif ( ! is_scalar( $value ) ) {
				$value = '';
			}
			$value = (string) $value;
			if ( $value === '' ) {
				$value = self::EMPTY_PLACEHOLDER;
			}
			$replacements[ '%' . $name . '%' ] = $value;
		}

		return strtr( $template, $replacements );
	}

	/**
	 * @param array<string, mixed> $payload
	 */
	private static function buildDedupKey( string $event_key, array $payload, int $actor ): string {
		$object_type = isset( $payload['object_type'] ) ? (string) $payload['object_type'] : '';
		$object_id   = isset( $payload['object_id'] ) ? (int) $payload['object_id'] : 0;
		$extra       = '';
		if ( isset( $payload['dedup'] ) ) {
			$extra = is_scalar( $payload['dedup'] ) ? (string) $payload['dedup'] : wp_json_encode( $payload['dedup'] );
		}
		return md5( $event_key . '|' . $object_type . '|' . $object_id . '|' . $actor . '|' . $extra );
	}

	/**
	 * @param array<string, mixed> $data
	 * @return array<string, mixed>
	 */
	public static function redactData( array $data ): array {
		$out = array();
		foreach ( $data as $key => $value ) {
			$key_str = is_string( $key ) ? $key : (string) $key;
			if ( self::isSensitiveKey( $key_str ) ) {
				$out[ $key_str ] = '[redacted]';
				continue;
			}
			if ( is_array( $value ) ) {
				$out[ $key_str ] = self::redactData( $value );
				continue;
			}
			$out[ $key_str ] = $value;
		}
		return $out;
	}

	public static function isSensitiveKey( string $key ): bool {
		$lower = strtolower( $key );

		if ( in_array( $lower, self::$never_redacted, true ) ) {
			return false;
		}

		foreach ( self::$sensitive_substrings as $needle ) {
			if ( strpos( $lower, $needle ) !== false ) {
				return true;
			}
		}

		$segments = preg_split( '/[^a-z0-9]+/', $lower, -1, PREG_SPLIT_NO_EMPTY );
		if ( ! is_array( $segments ) ) {
			$segments = array( $lower );
		}

		foreach ( $segments as $segment ) {
			if ( in_array( $segment, self::$sensitive_segments, true ) ) {
				return true;
			}
		}

		return false;
	}

	public static function shouldLogOption( string $option_name ): bool {
		return array_key_exists( $option_name, self::auditedOptions() );
	}

	/** @return array<string, bool> */
	private static function auditedOptions(): array {
		$options = apply_filters( 'bbcs_audit_audited_options', self::$audited_options_allowlist );

		return is_array( $options ) ? $options : self::$audited_options_allowlist;
	}


	public static function canStoreOptionValue( string $option_name ): bool {
		$options = self::auditedOptions();

		return ! empty( $options[ $option_name ] );
	}

	/**
	 * @return array<string, array<string, mixed>>
	 */
	public static function getDefaultDefinitions(): array {
		return array(
			BotBlockerAuditEvents::AUTH_LOGIN_SUCCESS        => array( 'severity' => self::SEVERITY_INFO, 'object_type' => 'user', 'message' => __( 'User %login% logged in.', 'botblocker-security' ) ),
			BotBlockerAuditEvents::AUTH_LOGIN_FAILED         => array( 'severity' => self::SEVERITY_MEDIUM, 'object_type' => 'user', 'message' => __( 'Failed login attempt for %login%.', 'botblocker-security' ) ),
			BotBlockerAuditEvents::AUTH_LOGOUT               => array( 'severity' => self::SEVERITY_INFO, 'object_type' => 'user', 'message' => __( 'User %login% logged out.', 'botblocker-security' ) ),
			BotBlockerAuditEvents::AUTH_PASSWORD_RESET       => array( 'severity' => self::SEVERITY_MEDIUM, 'object_type' => 'user', 'message' => __( 'Password reset for %login%.', 'botblocker-security' ) ),
			BotBlockerAuditEvents::AUTH_2FA_VERIFIED         => array( 'severity' => self::SEVERITY_INFO, 'object_type' => 'user', 'message' => __( 'Two-factor check passed via %via%.', 'botblocker-security' ) ),
			BotBlockerAuditEvents::AUTH_2FA_FAILED           => array( 'severity' => self::SEVERITY_MEDIUM, 'object_type' => 'user', 'message' => __( 'Two-factor check failed.', 'botblocker-security' ) ),
			BotBlockerAuditEvents::AUTH_2FA_TRUSTED_DEVICE   => array( 'severity' => self::SEVERITY_INFO, 'object_type' => 'user', 'message' => __( 'Signed in from a trusted device.', 'botblocker-security' ) ),
			BotBlockerAuditEvents::AUTH_2FA_SETUP_COMPLETED  => array( 'severity' => self::SEVERITY_INFO, 'object_type' => 'user', 'message' => __( 'Two-factor setup completed.', 'botblocker-security' ) ),
			/* translators: %object_id%: numeric user ID. */
			BotBlockerAuditEvents::AUTH_2FA_RESET            => array( 'severity' => self::SEVERITY_CRITICAL, 'object_type' => 'user', 'message' => __( 'Two-factor was reset for user #%object_id%.', 'botblocker-security' ) ),
			/* translators: %object_id%: numeric user ID. */
			BotBlockerAuditEvents::AUTH_2FA_DEVICES_REVOKED  => array( 'severity' => self::SEVERITY_MEDIUM, 'object_type' => 'user', 'message' => __( 'Trusted devices revoked for user #%object_id%.', 'botblocker-security' ) ),
			BotBlockerAuditEvents::CONTENT_POST_CREATED      => array( 'severity' => self::SEVERITY_INFO, 'object_type' => 'post', 'message' => __( 'Created the %post_type% %title%.', 'botblocker-security' ) ),
			/* translators: %post_type%: post type label; %object_id%: numeric post ID. */
			BotBlockerAuditEvents::CONTENT_POST_UPDATED      => array( 'severity' => self::SEVERITY_INFO, 'object_type' => 'post', 'message' => __( 'Updated the %post_type% #%object_id%.', 'botblocker-security' ) ),
			BotBlockerAuditEvents::CONTENT_POST_TRASHED      => array( 'severity' => self::SEVERITY_INFO, 'object_type' => 'post', 'message' => __( 'Moved the %post_type% %title% to trash.', 'botblocker-security' ) ),
			BotBlockerAuditEvents::CONTENT_POST_UNTRASHED    => array( 'severity' => self::SEVERITY_INFO, 'object_type' => 'post', 'message' => __( 'Restored the %post_type% %title% from trash.', 'botblocker-security' ) ),
			BotBlockerAuditEvents::CONTENT_POST_DELETED      => array( 'severity' => self::SEVERITY_MEDIUM, 'object_type' => 'post', 'message' => __( 'Permanently deleted the %post_type% %title%.', 'botblocker-security' ) ),
			/* translators: %keys%: changed custom field names; %object_id%: numeric post ID. */
			BotBlockerAuditEvents::CONTENT_POST_META_UPDATED => array( 'severity' => self::SEVERITY_INFO, 'object_type' => 'post', 'message' => __( 'Changed custom fields %keys% on post #%object_id%.', 'botblocker-security' ) ),
			/* translators: %login%: user login; %roles%: assigned user roles. */
			BotBlockerAuditEvents::USER_CREATED              => array( 'severity' => self::SEVERITY_CRITICAL, 'object_type' => 'user', 'message' => __( 'Created user account %login% (%roles%).', 'botblocker-security' ) ),
			/* translators: %object_id%: numeric user ID. */
			BotBlockerAuditEvents::USER_UPDATED              => array( 'severity' => self::SEVERITY_INFO, 'object_type' => 'user', 'message' => __( 'Updated the profile of user #%object_id%.', 'botblocker-security' ) ),
			BotBlockerAuditEvents::USER_DELETED              => array( 'severity' => self::SEVERITY_CRITICAL, 'object_type' => 'user', 'message' => __( 'Deleted user account %login%.', 'botblocker-security' ) ),
			/* translators: %login%: user login; %from%: previous role; %to%: new role. */
			BotBlockerAuditEvents::USER_ROLE_CHANGED         => array( 'severity' => self::SEVERITY_CRITICAL, 'object_type' => 'user', 'message' => __( 'Role changed for %login%: %from% to %to%.', 'botblocker-security' ) ),
			BotBlockerAuditEvents::USER_SUPER_ADMIN_GRANTED  => array( 'severity' => self::SEVERITY_CRITICAL, 'object_type' => 'user', 'message' => __( 'Granted super admin to %login%.', 'botblocker-security' ) ),
			BotBlockerAuditEvents::USER_SUPER_ADMIN_REVOKED  => array( 'severity' => self::SEVERITY_CRITICAL, 'object_type' => 'user', 'message' => __( 'Revoked super admin from %login%.', 'botblocker-security' ) ),
			BotBlockerAuditEvents::MEDIA_CREATED             => array( 'severity' => self::SEVERITY_INFO, 'object_type' => 'attachment', 'message' => __( 'Uploaded the file %title%.', 'botblocker-security' ) ),
			BotBlockerAuditEvents::MEDIA_UPDATED             => array( 'severity' => self::SEVERITY_INFO, 'object_type' => 'attachment', 'message' => __( 'Updated the file %title%.', 'botblocker-security' ) ),
			/* translators: %object_id%: numeric attachment ID. */
			BotBlockerAuditEvents::MEDIA_DELETED             => array( 'severity' => self::SEVERITY_INFO, 'object_type' => 'attachment', 'message' => __( 'Deleted the file #%object_id%.', 'botblocker-security' ) ),
			/* translators: %object_id%: numeric comment ID. */
			BotBlockerAuditEvents::COMMENT_UPDATED           => array( 'severity' => self::SEVERITY_INFO, 'object_type' => 'comment', 'message' => __( 'Edited comment #%object_id%.', 'botblocker-security' ) ),
			// phpcs:disable WordPress.WP.I18n.UnorderedPlaceholdersText -- custom named placeholders are rendered by BotBlockerAuditLogger::renderMessage()
			/* translators: %object_id%: numeric comment ID; %from%: previous status; %to%: new status. */
			BotBlockerAuditEvents::COMMENT_STATUS_CHANGED    => array( 'severity' => self::SEVERITY_INFO, 'object_type' => 'comment', 'message' => __( 'Comment #%object_id% changed from %from% to %to%.', 'botblocker-security' ) ),
			/* translators: %object_id%: numeric comment ID. */
			BotBlockerAuditEvents::COMMENT_DELETED           => array( 'severity' => self::SEVERITY_INFO, 'object_type' => 'comment', 'message' => __( 'Deleted comment #%object_id%.', 'botblocker-security' ) ),
			BotBlockerAuditEvents::TAXONOMY_TERM_CREATED     => array( 'severity' => self::SEVERITY_INFO, 'object_type' => 'term', 'message' => __( 'Created a term in %taxonomy%.', 'botblocker-security' ) ),
			/* translators: %object_id%: numeric term ID; %taxonomy%: taxonomy name. */
			BotBlockerAuditEvents::TAXONOMY_TERM_UPDATED     => array( 'severity' => self::SEVERITY_INFO, 'object_type' => 'term', 'message' => __( 'Updated term #%object_id% in %taxonomy%.', 'botblocker-security' ) ),
			/* translators: %object_id%: numeric term ID; %taxonomy%: taxonomy name. */
			BotBlockerAuditEvents::TAXONOMY_TERM_DELETED     => array( 'severity' => self::SEVERITY_INFO, 'object_type' => 'term', 'message' => __( 'Deleted term #%object_id% from %taxonomy%.', 'botblocker-security' ) ),
			BotBlockerAuditEvents::PLUGIN_ACTIVATED          => array( 'severity' => self::SEVERITY_MEDIUM, 'object_type' => 'plugin', 'message' => __( 'Activated the plugin %plugin%.', 'botblocker-security' ) ),
			BotBlockerAuditEvents::PLUGIN_DEACTIVATED        => array( 'severity' => self::SEVERITY_MEDIUM, 'object_type' => 'plugin', 'message' => __( 'Deactivated the plugin %plugin%.', 'botblocker-security' ) ),
			BotBlockerAuditEvents::PLUGIN_DELETED            => array( 'severity' => self::SEVERITY_MEDIUM, 'object_type' => 'plugin', 'message' => __( 'Deleted the plugin %plugin%.', 'botblocker-security' ) ),
			BotBlockerAuditEvents::PLUGIN_INSTALLED          => array( 'severity' => self::SEVERITY_MEDIUM, 'object_type' => 'plugin', 'message' => __( 'Installed the plugin %plugin%.', 'botblocker-security' ) ),
			BotBlockerAuditEvents::PLUGIN_UPDATED            => array( 'severity' => self::SEVERITY_INFO, 'object_type' => 'plugin', 'message' => __( 'Updated the plugin %plugin%.', 'botblocker-security' ) ),
			/* translators: %from%: previous theme name; %to%: new theme name. */
			BotBlockerAuditEvents::THEME_SWITCHED            => array( 'severity' => self::SEVERITY_INFO, 'object_type' => 'theme', 'message' => __( 'Switched theme from %from% to %to%.', 'botblocker-security' ) ),
			BotBlockerAuditEvents::THEME_DELETED             => array( 'severity' => self::SEVERITY_MEDIUM, 'object_type' => 'theme', 'message' => __( 'Deleted the theme %theme%.', 'botblocker-security' ) ),
			BotBlockerAuditEvents::THEME_INSTALLED           => array( 'severity' => self::SEVERITY_MEDIUM, 'object_type' => 'theme', 'message' => __( 'Installed the theme %theme%.', 'botblocker-security' ) ),
			BotBlockerAuditEvents::THEME_UPDATED             => array( 'severity' => self::SEVERITY_INFO, 'object_type' => 'theme', 'message' => __( 'Updated the theme %theme%.', 'botblocker-security' ) ),
			BotBlockerAuditEvents::CORE_UPGRADED             => array( 'severity' => self::SEVERITY_INFO, 'object_type' => 'core', 'message' => __( 'WordPress core was updated.', 'botblocker-security' ) ),
			/* translators: %options%: changed option names. */
			BotBlockerAuditEvents::SETTINGS_OPTION_CHANGED   => array( 'severity' => self::SEVERITY_INFO, 'object_type' => 'option', 'message' => __( 'Changed WordPress options: %options%.', 'botblocker-security' ) ),
			/* translators: %slug%: add-on slug; %event%: lifecycle event name. */
			BotBlockerAuditEvents::BOTBLOCKER_ADDON_LIFECYCLE => array( 'severity' => self::SEVERITY_INFO, 'object_type' => 'addon', 'message' => __( 'Add-on %slug%: %event%.', 'botblocker-security' ) ),
			// phpcs:enable WordPress.WP.I18n.UnorderedPlaceholdersText
			BotBlockerAuditEvents::BOTBLOCKER_RULE_CHANGED   => array( 'severity' => self::SEVERITY_MEDIUM, 'object_type' => 'rule', 'message' => __( 'BotBlocker rule changed (%action%).', 'botblocker-security' ) ),
			BotBlockerAuditEvents::BOTBLOCKER_MAINTENANCE    => array( 'severity' => self::SEVERITY_INFO, 'object_type' => 'maintenance', 'message' => __( 'BotBlocker maintenance task ran.', 'botblocker-security' ) ),
			/* translators: %count%: number of changed settings. */
			BotBlockerAuditEvents::BOTBLOCKER_SETTINGS_CHANGED => array( 'severity' => self::SEVERITY_INFO, 'object_type' => 'settings', 'message' => __( 'BotBlocker settings changed (%count%).', 'botblocker-security' ) ),
			/* translators: %from%: previous protection state; %to%: new protection state. */
			BotBlockerAuditEvents::BOTBLOCKER_PROTECTION     => array( 'severity' => self::SEVERITY_CRITICAL, 'object_type' => 'protection', 'message' => __( 'Protection changed from %from% to %to%.', 'botblocker-security' ) ),
			BotBlockerAuditEvents::BOTBLOCKER_SECRET_BYPASS  => array( 'severity' => self::SEVERITY_CRITICAL, 'object_type' => 'protection', 'message' => __( 'Secret link used to bypass checks for one request from %ip%.', 'botblocker-security' ) ),
			BotBlockerAuditEvents::BOTBLOCKER_SECRET_OFF     => array( 'severity' => self::SEVERITY_CRITICAL, 'object_type' => 'protection', 'message' => __( 'Secret link disabled protection site-wide from %ip%.', 'botblocker-security' ) ),
			BotBlockerAuditEvents::BOTBLOCKER_SECRET_ON      => array( 'severity' => self::SEVERITY_CRITICAL, 'object_type' => 'protection', 'message' => __( 'Secret link re-enabled protection from %ip%.', 'botblocker-security' ) ),
		);
	}
}
