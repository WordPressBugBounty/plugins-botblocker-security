<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// BACKUP
add_action( 'wp_ajax_bbcs_backup_data_settings', array( 'BotBlockerAjaxBackup', 'handleBackup' ) );
add_action( 'wp_ajax_bbcs_download_backup', array( 'BotBlockerAjaxBackup', 'handleDownload' ) );
add_action( 'wp_ajax_bbcs_import_data_settings', array( 'BotBlockerAjaxBackup', 'handleImport' ) );

// SECRET LINKS
add_action( 'wp_ajax_bbcs_regenerate_secret_links', array( 'BotBlockerAjaxSecretLinks', 'handleRegenerateSecretLinks' ) );

// ADDONS
add_action( 'wp_ajax_bbcs_load_market', array( 'BotBlockerAjaxAddons', 'handleLoadMarket' ) );

// MAINTENANCE
add_action( 'wp_ajax_bbcs_database_reinstallation', array( 'BotBlockerAjaxMaintenance', 'handleDatabaseReinstallation' ) );
add_action( 'wp_ajax_bbcs_clear_hits_database', array( 'BotBlockerAjaxMaintenance', 'handleClearHits' ) );
add_action( 'wp_ajax_bbcs_clear_transients', array( 'BotBlockerAjaxMaintenance', 'handleClearTransients' ) );
add_action( 'wp_ajax_bbcs_update_asn_database', array( 'BotBlockerAjaxMaintenance', 'handleUpdateAsnDatabase' ) );
add_action( 'wp_ajax_bbcs_flush_rewrite_rules', array( 'BotBlockerAjaxMaintenance', 'handleFlushRewriteRules' ) );
add_action( 'wp_ajax_bbcs_flush_object_cache', array( 'BotBlockerAjaxMaintenance', 'handleFlushObjectCache' ) );

// CACHE
add_action( 'wp_ajax_bbcs_toggle_redis_and_memcached', array( 'BotBlockerAjaxCache', 'handleToggleRedisMemcached' ) );
add_action( 'wp_ajax_bbcs_switch_ptr_cache_in_db', array( 'BotBlockerAjaxCache', 'handleSwitchPtrCacheInDb' ) );
add_action( 'wp_ajax_bbcs_switch_ui_cache_in_db', array( 'BotBlockerAjaxCache', 'handleSwitchUiCacheInDb' ) );

// EARLY PHASE
add_action( 'wp_ajax_bbcs_toggle_early_phase_in_db', array( 'BotBlockerAjaxEarlyPhase', 'handleToggleEarlyPhase' ) );

// EMAIL
add_action( 'wp_ajax_bbcs_send_email', array( 'BotBlockerAjaxEmail', 'handleSendEmail' ) );
add_action( 'wp_ajax_bbcs_contact_email', array( 'BotBlockerAjaxEmail', 'handleContactEmail' ) );

// DEBUG
add_action( 'wp_ajax_bbcs_create_salt_file', array( 'BotBlockerAjaxDebug', 'handleCreateSaltFile' ) );
add_action( 'wp_ajax_bbcs_clear_wp_debug_log', array( 'BotBlockerAjaxDebug', 'handleClearDebugLog' ) );
add_action( 'wp_ajax_bbcs_download_wp_debug_log', array( 'BotBlockerAjaxDebug', 'handleDownloadDebugLog' ) );
add_action( 'wp_ajax_bbcs_download_debug_log', array( 'BotBlockerAjaxDebug', 'handleStreamDebugLog' ) );

// PROFILE
add_action( 'wp_ajax_bbcs_apply_security_profile', array( 'BotBlockerAjaxProfile', 'handleApplyProfile' ) );

// HITS
add_action( 'wp_ajax_bbcs_get_botblocker_hits', array( 'BotBlockerAjaxHits', 'handleGetBotHits' ) );
add_action( 'wp_ajax_bbcs_get_botblocker_admin_hits', array( 'BotBlockerAjaxHits', 'handleGetAdminHits' ) );
add_action( 'wp_ajax_bbcs_get_botblocker_other_hits', array( 'BotBlockerAjaxHits', 'handleGetOtherHits' ) );
add_action( 'wp_ajax_bbcs_get_botblocker_all_hits', array( 'BotBlockerAjaxHits', 'handleGetAllHits' ) );
add_action( 'wp_ajax_bbcs_hit_to_rule', array( 'BotBlockerAjaxHits', 'handleHitToRule' ) );

// AUDIT LOG
add_action( 'wp_ajax_bbcs_get_audit_log', array( 'BotBlockerAjaxAudit', 'handleGetAuditLog' ) );
add_action( 'wp_ajax_bbcs_export_audit_log', array( 'BotBlockerAjaxAudit', 'handleExportAuditLog' ) );

// RULES
add_action( 'wp_ajax_bbcs_get_botblocker_rules', array( 'BotBlockerAjaxRules', 'handleGetRules' ) );
add_action( 'wp_ajax_bbcs_get_rule_details', array( 'BotBlockerAjaxRules', 'handleGetDetails' ) );
add_action( 'wp_ajax_bbcs_update_rule', array( 'BotBlockerAjaxRules', 'handleUpdateRule' ) );
add_action( 'wp_ajax_bbcs_delete_rule', array( 'BotBlockerAjaxRules', 'handleDeleteRule' ) );
add_action( 'wp_ajax_bbcs_toggle_rule', array( 'BotBlockerAjaxRules', 'handleToggleRule' ) );
add_action( 'wp_ajax_bbcs_create_rule', array( 'BotBlockerAjaxRules', 'handleCreateRule' ) );
add_action( 'wp_ajax_bbcs_export_rules', array( 'BotBlockerAjaxRules', 'handleExport' ) );
add_action( 'wp_ajax_bbcs_import_rules', array( 'BotBlockerAjaxRules', 'handleImport' ) );
add_action( 'wp_ajax_bbcs_clear_all_rules', array( 'BotBlockerAjaxRules', 'handleClearAll' ) );
add_action( 'wp_ajax_bbcs_rules_to_php', array( 'BotBlockerAjaxRules', 'handleRegenerateFile' ) );
add_action( 'wp_ajax_bbcs_create_ip_rule', array( 'BotBlockerAjaxRules', 'handleCreateIpRule' ) );

// RULES IPv4
add_action( 'wp_ajax_bbcs_get_botblocker_ipv4_rules', array( 'BotBlockerAjaxIpv4Rules', 'handleGetRules' ) );
add_action( 'wp_ajax_bbcs_delete_ipv4_rule', array( 'BotBlockerAjaxIpv4Rules', 'handleDeleteRule' ) );
add_action( 'wp_ajax_bbcs_toggle_ipv4_rule', array( 'BotBlockerAjaxIpv4Rules', 'handleToggleRule' ) );
add_action( 'wp_ajax_bbcs_create_ipv4_rule', array( 'BotBlockerAjaxIpv4Rules', 'handleCreateRule' ) );
add_action( 'wp_ajax_bbcs_update_ipv4_rule', array( 'BotBlockerAjaxIpv4Rules', 'handleUpdateRule' ) );
add_action( 'wp_ajax_bbcs_export_ipv4_rules', array( 'BotBlockerAjaxIpv4Rules', 'handleExport' ) );
add_action( 'wp_ajax_bbcs_import_ipv4_rules', array( 'BotBlockerAjaxIpv4Rules', 'handleImport' ) );
add_action( 'wp_ajax_bbcs_clear_all_ipv4_rules', array( 'BotBlockerAjaxIpv4Rules', 'handleClearAll' ) );
add_action( 'wp_ajax_bbcs_get_ipv4_rule_details', array( 'BotBlockerAjaxIpv4Rules', 'handleGetDetails' ) );
add_action( 'wp_ajax_bbcs_import_ipv4_whitelist', array( 'BotBlockerAjaxIpv4Rules', 'handleImportWhitelist' ) );
add_action( 'wp_ajax_bbcs_import_ipv4_blacklist', array( 'BotBlockerAjaxIpv4Rules', 'handleImportBlacklist' ) );
add_action( 'wp_ajax_bbcs_ipv4_to_php', array( 'BotBlockerAjaxIpv4Rules', 'handleRegenerateFile' ) );

// RULES IPv6
add_action( 'wp_ajax_bbcs_get_botblocker_ipv6_rules', array( 'BotBlockerAjaxIpv6Rules', 'handleGetRules' ) );
add_action( 'wp_ajax_bbcs_delete_ipv6_rule', array( 'BotBlockerAjaxIpv6Rules', 'handleDeleteRule' ) );
add_action( 'wp_ajax_bbcs_toggle_ipv6_rule', array( 'BotBlockerAjaxIpv6Rules', 'handleToggleRule' ) );
add_action( 'wp_ajax_bbcs_create_ipv6_rule', array( 'BotBlockerAjaxIpv6Rules', 'handleCreateRule' ) );
add_action( 'wp_ajax_bbcs_update_ipv6_rule', array( 'BotBlockerAjaxIpv6Rules', 'handleUpdateRule' ) );
add_action( 'wp_ajax_bbcs_export_ipv6_rules', array( 'BotBlockerAjaxIpv6Rules', 'handleExport' ) );
add_action( 'wp_ajax_bbcs_import_ipv6_rules', array( 'BotBlockerAjaxIpv6Rules', 'handleImport' ) );
add_action( 'wp_ajax_bbcs_clear_all_ipv6_rules', array( 'BotBlockerAjaxIpv6Rules', 'handleClearAll' ) );
add_action( 'wp_ajax_bbcs_get_ipv6_rule_details', array( 'BotBlockerAjaxIpv6Rules', 'handleGetDetails' ) );
add_action( 'wp_ajax_bbcs_import_ipv6_whitelist', array( 'BotBlockerAjaxIpv6Rules', 'handleImportWhitelist' ) );
add_action( 'wp_ajax_bbcs_import_ipv6_blacklist', array( 'BotBlockerAjaxIpv6Rules', 'handleImportBlacklist' ) );
add_action( 'wp_ajax_bbcs_ipv6_to_php', array( 'BotBlockerAjaxIpv6Rules', 'handleRegenerateFile' ) );

// PATHS
add_action( 'wp_ajax_bbcs_get_botblocker_paths', array( 'BotBlockerAjaxPaths', 'handleGetPaths' ) );
add_action( 'wp_ajax_bbcs_get_path_details', array( 'BotBlockerAjaxPaths', 'handleGetDetails' ) );
add_action( 'wp_ajax_bbcs_update_path', array( 'BotBlockerAjaxPaths', 'handleUpdatePath' ) );
add_action( 'wp_ajax_bbcs_delete_path', array( 'BotBlockerAjaxPaths', 'handleDeletePath' ) );
add_action( 'wp_ajax_bbcs_toggle_path', array( 'BotBlockerAjaxPaths', 'handleTogglePath' ) );
add_action( 'wp_ajax_bbcs_create_path', array( 'BotBlockerAjaxPaths', 'handleCreatePath' ) );
add_action( 'wp_ajax_bbcs_export_paths', array( 'BotBlockerAjaxPaths', 'handleExport' ) );
add_action( 'wp_ajax_bbcs_import_paths', array( 'BotBlockerAjaxPaths', 'handleImport' ) );
add_action( 'wp_ajax_bbcs_clear_all_paths', array( 'BotBlockerAjaxPaths', 'handleClearAll' ) );
add_action( 'wp_ajax_bbcs_path_to_php', array( 'BotBlockerAjaxPaths', 'handleRegenerateFile' ) );

// WHITE BOTS
add_action( 'wp_ajax_bbcs_get_botblocker_white', array( 'BotBlockerAjaxWhiteBots', 'handleGetWhite' ) );
add_action( 'wp_ajax_bbcs_get_white_details', array( 'BotBlockerAjaxWhiteBots', 'handleGetDetails' ) );
add_action( 'wp_ajax_bbcs_update_white', array( 'BotBlockerAjaxWhiteBots', 'handleUpdateWhite' ) );
add_action( 'wp_ajax_bbcs_delete_white', array( 'BotBlockerAjaxWhiteBots', 'handleDeleteWhite' ) );
add_action( 'wp_ajax_bbcs_toggle_white', array( 'BotBlockerAjaxWhiteBots', 'handleToggleWhite' ) );
add_action( 'wp_ajax_bbcs_create_white', array( 'BotBlockerAjaxWhiteBots', 'handleCreateWhite' ) );
add_action( 'wp_ajax_bbcs_export_white', array( 'BotBlockerAjaxWhiteBots', 'handleExport' ) );
add_action( 'wp_ajax_bbcs_import_white', array( 'BotBlockerAjaxWhiteBots', 'handleImport' ) );
add_action( 'wp_ajax_bbcs_clear_all_white', array( 'BotBlockerAjaxWhiteBots', 'handleClearAll' ) );
add_action( 'wp_ajax_bbcs_se_to_php', array( 'BotBlockerAjaxWhiteBots', 'handleRegenerateFile' ) );

// ASN
add_action( 'wp_ajax_bbcs_get_asn', array( 'BotBlockerAjaxAsn', 'handleGetAsn' ) );
add_action( 'wp_ajax_bbcs_get_asn_details', array( 'BotBlockerAjaxAsn', 'handleGetDetails' ) );
add_action( 'wp_ajax_bbcs_create_asn', array( 'BotBlockerAjaxAsn', 'handleCreateAsn' ) );
add_action( 'wp_ajax_bbcs_update_asn', array( 'BotBlockerAjaxAsn', 'handleUpdateAsn' ) );
add_action( 'wp_ajax_bbcs_delete_asn', array( 'BotBlockerAjaxAsn', 'handleDeleteAsn' ) );
add_action( 'wp_ajax_bbcs_toggle_asn', array( 'BotBlockerAjaxAsn', 'handleToggleAsn' ) );
add_action( 'wp_ajax_bbcs_export_asn', array( 'BotBlockerAjaxAsn', 'handleExport' ) );
add_action( 'wp_ajax_bbcs_import_asn', array( 'BotBlockerAjaxAsn', 'handleImport' ) );
add_action( 'wp_ajax_bbcs_clear_all_asn', array( 'BotBlockerAjaxAsn', 'handleClearAll' ) );
add_action( 'wp_ajax_bbcs_asn_to_php', array( 'BotBlockerAjaxAsn', 'handleRegenerateFile' ) );

// PROXY
add_action( 'wp_ajax_bbcs_get_botblocker_proxies', array( 'BotBlockerAjaxProxy', 'handleGetProxies' ) );
add_action( 'wp_ajax_bbcs_get_proxy_details', array( 'BotBlockerAjaxProxy', 'handleGetDetails' ) );
add_action( 'wp_ajax_bbcs_update_proxy', array( 'BotBlockerAjaxProxy', 'handleUpdateProxy' ) );
add_action( 'wp_ajax_bbcs_delete_proxy', array( 'BotBlockerAjaxProxy', 'handleDeleteProxy' ) );
add_action( 'wp_ajax_bbcs_create_proxy', array( 'BotBlockerAjaxProxy', 'handleCreateProxy' ) );
add_action( 'wp_ajax_bbcs_export_proxies', array( 'BotBlockerAjaxProxy', 'handleExport' ) );
add_action( 'wp_ajax_bbcs_import_proxies', array( 'BotBlockerAjaxProxy', 'handleImport' ) );
add_action( 'wp_ajax_bbcs_clear_all_proxies', array( 'BotBlockerAjaxProxy', 'handleClearAll' ) );
add_action( 'wp_ajax_bbcs_render_proxy_file', array( 'BotBlockerAjaxProxy', 'handleRenderFile' ) );

// GEO
add_action( 'wp_ajax_bbcs_get_geo_countries', array( 'BotBlockerAjaxGeo', 'handleGetCountriesLegacy' ) );
add_action( 'wp_ajax_bbcs_save_geo_countries', array( 'BotBlockerAjaxGeo', 'handleSaveCountriesLegacy' ) );
add_action( 'wp_ajax_bbcs_get_countries_table', array( 'BotBlockerAjaxGeo', 'handleGetCountries' ) );
add_action( 'wp_ajax_bbcs_toggle_country', array( 'BotBlockerAjaxGeo', 'handleToggleCountry' ) );
add_action( 'wp_ajax_bbcs_create_country', array( 'BotBlockerAjaxGeo', 'handleCreateCountry' ) );
add_action( 'wp_ajax_bbcs_delete_country', array( 'BotBlockerAjaxGeo', 'handleDeleteCountry' ) );
add_action( 'wp_ajax_bbcs_clear_all_countries', array( 'BotBlockerAjaxGeo', 'handleClearAll' ) );

// LLM
add_action( 'wp_ajax_bbcs_get_botblocker_llm', array( 'BotBlockerAjaxLlm', 'handleGetProviders' ) );
add_action( 'wp_ajax_bbcs_toggle_llm_provider', array( 'BotBlockerAjaxLlm', 'handleToggleProvider' ) );
add_action( 'wp_ajax_bbcs_sync_llm_cloud', array( 'BotBlockerAjaxLlm', 'handleSyncCloud' ) );
add_action( 'wp_ajax_bbcs_get_llm_sync_status', array( 'BotBlockerAjaxLlm', 'handleGetSyncStatus' ) );
add_action( 'wp_ajax_bbcs_export_llm_json', array( 'BotBlockerAjaxLlm', 'handleExportJson' ) );
add_action( 'wp_ajax_bbcs_llm_to_php', array( 'BotBlockerAjaxLlm', 'handleRegenerateFile' ) );

// TLS FINGERPRINTS
add_action( 'wp_ajax_bbcs_import_tls_fingerprints', array( 'BotBlockerAjaxTlsFingerprints', 'handleImport' ) );
add_action( 'wp_ajax_bbcs_clear_all_tls_fingerprints', array( 'BotBlockerAjaxTlsFingerprints', 'handleClearAll' ) );
add_action( 'wp_ajax_bbcs_sync_tls_fingerprints', array( 'BotBlockerAjaxTlsFingerprints', 'handleSyncCloud' ) );

// RUGOV
add_action( 'wp_ajax_bbcs_update_rugov', array( 'BotBlockerAjaxRuGov', 'handleUpdate' ) );

// RULES STATS
add_action( 'wp_ajax_bbcs_refresh_rules_stats', array( 'BotBlockerAjaxRulesStats', 'handleRefreshStats' ) );
