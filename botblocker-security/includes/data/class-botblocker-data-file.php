<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'BotBlockerDataFile' ) ) {

	class BotBlockerDataFile {

		// Layer-1 shared class: bump on every member addition (see BotBlockerCompiledFile::EXPECTED_CLASS_REV).
		public const CLASS_REV = 2;

		public static $invalidated_file = '';

		public static function invalidateCompiled( string $file ): void {
			self::$invalidated_file = $file;
			if ( function_exists( 'opcache_invalidate' ) ) {
				@opcache_invalidate( $file, true );
			}
		}

		public static function sign( string $content ): string {
			$content = rtrim( $content );
			return $content . "\n// HASH: " . hash( 'sha256', $content ) . "\n";
		}

		public static function verify( string $content ): bool {
			$pos = strrpos( $content, '// HASH:' );
			if ( $pos === false ) {
				return false;
			}
			if ( ! preg_match( '~//\s*HASH:\s*([a-f0-9]{64})\s*$~', $content, $m ) ) {
				return false;
			}
			$hash     = $m[1];
			$stripped = rtrim( substr( $content, 0, $pos ) );
			$computed = hash( 'sha256', $stripped );
			return hash_equals( $computed, $hash );
		}

		public static function safeLoad( string $file ): array {
			if ( ! file_exists( $file ) ) {
				return array();
			}

			$content = file_get_contents( $file );
			if ( $content === false ) {
				return array();
			}

			$verified = self::verify( $content );
			if ( ! $verified ) {
				return array();
			}

			$data = include $file;
			return is_array( $data ) ? $data : array();
		}

		/**
		 * Mapping: data file basename -> render/regenerate function.
		 * Used by safeLoadWithRecovery() to rebuild tampered files from DB.
		 */
		public static function renderMap(): array {
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
				'geo_countries.php'    => 'BotBlockerFileRenderer::renderCountries',
				'hot-bans.php'         => 'BotBlockerFileRenderer::renderHotBans',
				'addons.php'           => 'BotBlockerFileRenderer::renderAddons',
				'bot-signatures-processed.php' => 'BotBlockerFileRenderer::renderBotSignatures',
			);
		}

		/**
		 * Load a data file with integrity check, auto-recovery, and admin alert.
		 *
		 * Flow:
		 *   1. Try safeLoad (hash-based).
		 *   2. If data is empty AND file exists AND has a (failed) hash ->
		 *      log, set admin alert transient, regenerate from DB, reload.
		 *   3. If regeneration fails -> return [].
		 *
		 * @param string $file Absolute path to the data file.
		 * @return array The loaded data, or empty array on irrecoverable failure.
		 */
		public static function safeLoadWithRecovery( string $file ): array {
			$data = self::safeLoad( $file );
			if ( ! empty( $data ) ) {
				return $data;
			}

			$basename = basename( $file );

			// File missing - hosting migration or accidental deletion. Regenerate from DB.
			if ( ! file_exists( $file ) ) {
				$map    = self::renderMap();
				$render = $map[ $basename ] ?? '';
				if ( $render !== '' && is_callable( $render ) ) {
					call_user_func( $render );
					return self::safeLoad( $file );
				}
				return array();
			}

			$content = @file_get_contents( $file );
			if ( $content === false ) {
				return array();
			}

			// Unsigned or hash-failed content is untrusted: never executed.
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
			$map    = self::renderMap();
			$render = $map[ $basename ] ?? '';
			if ( $render !== '' && is_callable( $render ) ) {
				call_user_func( $render );
				return self::safeLoad( $file );
			}

			return array();
		}
	}
}
