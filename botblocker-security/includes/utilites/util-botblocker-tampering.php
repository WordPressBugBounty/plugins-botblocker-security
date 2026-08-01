<?php
declare(strict_types=1);
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Mapping: data file basename -> render/regenerate function.
 * Used by bbcs_safe_load_with_recovery() to rebuild tampered files from DB.
 */
function bbcs_data_file_render_map(): array {
	return array(
		'ip.php'             => 'BotBlockerFileRenderer::renderIps',
		'rules.php'          => 'BotBlockerFileRenderer::renderRules',
		'paths.php'          => 'BotBlockerFileRenderer::renderPaths',
		'proxy.php'          => 'BotBlockerFileRenderer::renderProxy',
		'search_engines.php' => 'BotBlockerFileRenderer::renderSearchEngines',
		'llm_trusted.php'    => 'BotBlockerFileRenderer::renderLlmTrusted',
		'asn_rules.php'      => 'BotBlockerFileRenderer::renderAsn',
		'settings.php'       => 'BotBlockerFileRenderer::generateSettingsFile',
		'salt.php'           => 'BotBlockerInstall::createSaltFile',
		'tls_fingerprints.php' => 'BotBlockerFileRenderer::renderTlsFingerprints',
	);
}

/**
 * Load a data file with integrity check, auto-recovery, and admin alert.
 *
 * Flow:
 *   1. Try bbcs_safe_load_data_file (hash-based).
 *   2. If data is empty AND file exists AND has a (failed) hash ->
 *      log, set admin alert transient, regenerate from DB, reload.
 *   3. If regeneration fails -> return [].
 *
 * @param string $file Absolute path to the data file.
 * @return array The loaded data, or empty array on irrecoverable failure.
 */
function bbcs_safe_load_with_recovery( string $file ): array {
	$data = bbcs_safe_load_data_file( $file );
	if ( ! empty( $data ) ) {
		return $data;
	}

	$basename = basename( $file );

	// File missing - hosting migration or accidental deletion. Regenerate from DB.
	if ( ! file_exists( $file ) ) {
		$map    = bbcs_data_file_render_map();
		$render = $map[ $basename ] ?? '';
		if ( $render !== '' && is_callable( $render ) ) {
			call_user_func( $render );
			return bbcs_safe_load_data_file( $file );
		}
		return array();
	}

	$content = @file_get_contents( $file );
	if ( $content === false || strrpos( $content, '// HASH:' ) === false ) {
		return array(); // file not signed yet - not tampered, just first load
	}

	// Hash exists but verification failed -> tampered.
	if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
		error_log( '[BBCS DEBUG] [Integrity] integrity failure - regenerating: ' . $basename );
	}

	if ( function_exists( 'set_transient' ) ) {
		$alert = array(
			'file' => $basename,
			'time' => time(),
		);
		set_transient( 'bbcs_file_tampered_' . md5( $file ), $alert, DAY_IN_SECONDS );
	}

	// Regenerate from DB.
	$map    = bbcs_data_file_render_map();
	$render = $map[ $basename ] ?? '';
	if ( $render !== '' && is_callable( $render ) ) {
		call_user_func( $render );
		return bbcs_safe_load_data_file( $file );
	}

	return array();
}
