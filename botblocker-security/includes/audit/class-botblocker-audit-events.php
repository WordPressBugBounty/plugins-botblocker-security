<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BotBlockerAuditEvents {

	public const AUTH_LOGIN_SUCCESS         = 'auth.login.success';
	public const AUTH_LOGIN_FAILED          = 'auth.login.failed';
	public const AUTH_LOGOUT                = 'auth.logout';
	public const AUTH_PASSWORD_RESET        = 'auth.password_reset';
	public const AUTH_2FA_VERIFIED          = 'auth.2fa.verified';
	public const AUTH_2FA_FAILED            = 'auth.2fa.failed';
	public const AUTH_2FA_TRUSTED_DEVICE    = 'auth.2fa.trusted_device';
	public const AUTH_2FA_SETUP_COMPLETED   = 'auth.2fa.setup_completed';
	public const AUTH_2FA_RESET             = 'auth.2fa.reset';
	public const AUTH_2FA_DEVICES_REVOKED   = 'auth.2fa.devices_revoked';

	public const CONTENT_POST_CREATED       = 'content.post.created';
	public const CONTENT_POST_UPDATED       = 'content.post.updated';
	public const CONTENT_POST_TRASHED       = 'content.post.trashed';
	public const CONTENT_POST_UNTRASHED     = 'content.post.untrashed';
	public const CONTENT_POST_DELETED       = 'content.post.deleted';
	public const CONTENT_POST_META_UPDATED  = 'content.post_meta.updated';

	public const USER_CREATED               = 'user.created';
	public const USER_UPDATED               = 'user.updated';
	public const USER_DELETED               = 'user.deleted';
	public const USER_ROLE_CHANGED          = 'user.role.changed';
	public const USER_SUPER_ADMIN_GRANTED   = 'user.super_admin.granted';
	public const USER_SUPER_ADMIN_REVOKED   = 'user.super_admin.revoked';

	public const MEDIA_CREATED              = 'media.created';
	public const MEDIA_UPDATED              = 'media.updated';
	public const MEDIA_DELETED              = 'media.deleted';

	public const COMMENT_UPDATED            = 'comment.updated';
	public const COMMENT_STATUS_CHANGED     = 'comment.status_changed';
	public const COMMENT_DELETED            = 'comment.deleted';

	public const TAXONOMY_TERM_CREATED      = 'taxonomy.term.created';
	public const TAXONOMY_TERM_UPDATED      = 'taxonomy.term.updated';
	public const TAXONOMY_TERM_DELETED      = 'taxonomy.term.deleted';

	public const PLUGIN_ACTIVATED           = 'plugin.activated';
	public const PLUGIN_DEACTIVATED         = 'plugin.deactivated';
	public const PLUGIN_DELETED             = 'plugin.deleted';
	public const PLUGIN_INSTALLED           = 'plugin.installed';
	public const PLUGIN_UPDATED             = 'plugin.updated';

	public const THEME_SWITCHED             = 'theme.switched';
	public const THEME_DELETED              = 'theme.deleted';
	public const THEME_INSTALLED            = 'theme.installed';
	public const THEME_UPDATED              = 'theme.updated';

	public const CORE_UPGRADED              = 'core.upgraded';

	public const SETTINGS_OPTION_CHANGED    = 'settings.option.changed';

	public const BOTBLOCKER_ADDON_LIFECYCLE = 'botblocker.addon.lifecycle';
	public const BOTBLOCKER_RULE_CHANGED    = 'botblocker.rule.changed';
	public const BOTBLOCKER_MAINTENANCE     = 'botblocker.maintenance';

	public const BOTBLOCKER_PROTECTION      = 'botblocker.protection.toggled';
	public const BOTBLOCKER_SETTINGS_CHANGED = 'botblocker.settings.changed';

	public const BOTBLOCKER_SECRET_BYPASS   = 'botblocker.secret.bypass';
	public const BOTBLOCKER_SECRET_OFF      = 'botblocker.secret.off';
	public const BOTBLOCKER_SECRET_ON       = 'botblocker.secret.on';

	public const RULE_LIST_RULE  = 'rule';
	public const RULE_LIST_IPV4  = 'ipv4';
	public const RULE_LIST_IPV6  = 'ipv6';
	public const RULE_LIST_GEO   = 'geo';
	public const RULE_LIST_ASN   = 'asn';
	public const RULE_LIST_PATH  = 'path';
	public const RULE_LIST_PROXY = 'proxy';
	public const RULE_LIST_WHITE = 'white';
	public const RULE_LIST_LLM   = 'llm';
	public const RULE_LIST_TLS   = 'tls';

	public const RULE_ACTION_CREATED  = 'created';
	public const RULE_ACTION_UPDATED  = 'updated';
	public const RULE_ACTION_DELETED  = 'deleted';
	public const RULE_ACTION_TOGGLED  = 'toggled';
	public const RULE_ACTION_IMPORTED = 'imported';
	public const RULE_ACTION_CLEARED  = 'cleared';
	public const RULE_ACTION_SYNCED   = 'synced';
}
