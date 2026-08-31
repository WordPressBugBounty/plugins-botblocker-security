<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

class BotBlockerCaptchaRegistry {

	private static $modes = array();

	public static function reset(): void {
		self::$modes = array();
	}

	public static function register( int $id, string $slug, string $name, array $config ): bool {
		if ( $id < 90 ) {
			return self::reject( $id, $slug, 'id below 90 (core reserves 0-8)' );
		}
		if ( '' === trim( $name ) ) {
			return self::reject( $id, $slug, 'empty name' );
		}
		if ( empty( $config['params_callback'] ) || ! is_callable( $config['params_callback'] ) ) {
			return self::reject( $id, $slug, 'params_callback missing or not callable' );
		}
		if ( empty( $config['verify_callback'] ) || ! is_callable( $config['verify_callback'] ) ) {
			return self::reject( $id, $slug, 'verify_callback missing or not callable' );
		}
		if ( isset( $config['keys_callback'] ) && '' !== trim( (string) $config['keys_callback'] ) && ! is_callable( $config['keys_callback'] ) ) {
			return self::reject( $id, $slug, 'keys_callback not callable' );
		}
		if ( empty( $config['js'] ) || ! is_string( $config['js'] ) ) {
			return self::reject( $id, $slug, 'js path missing' );
		}
		if ( isset( self::$modes[ $id ] ) ) {
			return self::reject( $id, $slug, 'duplicate id (already owned by "' . self::$modes[ $id ]['slug'] . '")' );
		}

		self::$modes[ $id ] = array(
			'slug'            => $slug,
			'name'            => $name,
			'params_callback' => $config['params_callback'],
			'verify_callback' => $config['verify_callback'],
			'keys_callback'   => isset( $config['keys_callback'] ) && '' !== trim( (string) $config['keys_callback'] ) && is_callable( $config['keys_callback'] ) ? $config['keys_callback'] : '',
			'js'              => $config['js'],
			'external'        => isset( $config['external'] ) && is_array( $config['external'] ) ? array_values( $config['external'] ) : array(),
			'wizard_icon'     => isset( $config['wizard_icon'] ) && is_string( $config['wizard_icon'] ) ? $config['wizard_icon'] : '',
			'wizard_subtitle' => isset( $config['wizard_subtitle'] ) && is_string( $config['wizard_subtitle'] ) ? $config['wizard_subtitle'] : '',
		);
		return true;
	}

	private static function reject( int $id, string $slug, string $reason ): bool {
		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
			error_log( '[BBCS DEBUG] [CaptchaRegistry] rejected registration: mode=' . $id . ' slug="' . $slug . '" reason=' . $reason );
		}
		return false;
	}

	public static function has( int $id ): bool {
		return isset( self::$modes[ $id ] );
	}

	/**
	 * Whether the mode is usable right now: a mode without a keys probe is
	 * always available; a configured probe must answer true (fail-closed on
	 * throw or unknown id) so unconfigured providers can never be selected.
	 */
	public static function hasKeys( int $id ): bool {
		if ( ! isset( self::$modes[ $id ] ) ) {
			return false;
		}
		$keys_callback = self::$modes[ $id ]['keys_callback'];
		if ( '' === $keys_callback || ! is_callable( $keys_callback ) ) {
			return true;
		}
		try {
			return (bool) call_user_func( $keys_callback );
		} catch ( \Throwable $e ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [CaptchaRegistry] keys callback threw for mode=' . $id . ': ' . $e->getMessage() );
			}
			return false;
		}
	}

	public static function getParams( int $id ): array {
		if ( ! isset( self::$modes[ $id ] ) ) {
			return array(
				'mode'   => 0,
				'params' => array(),
			);
		}

		$params_callback = self::$modes[ $id ]['params_callback'];
		if ( ! is_callable( $params_callback ) ) {
			return array(
				'mode'   => 0,
				'params' => array(),
			);
		}

		return call_user_func( $params_callback, $id, BotBlocker::getInstance() );
	}

	public static function getAssets( int $id ): array {
		if ( ! isset( self::$modes[ $id ] ) ) {
			return array(
				'js_content' => '',
				'external'   => array(),
			);
		}

		$js_content = '';
		$path       = (string) self::$modes[ $id ]['js'];
		if ( '' !== $path && is_readable( $path ) ) {
			$raw        = file_get_contents( $path );
			$js_content = is_string( $raw ) ? $raw : '';
		}

		return array(
			'js_content' => $js_content,
			'external'   => self::$modes[ $id ]['external'],
		);
	}

	public static function allModes(): array {
		$out = array();
		foreach ( self::$modes as $id => $mode ) {
			$out[ $id ] = array(
				'slug' => $mode['slug'],
				'name' => $mode['name'],
			);
		}
		return $out;
	}

	public static function optionsForSelect( array $core_options ): array {
		foreach ( self::$modes as $id => $mode ) {
			$key = (string) $id;
			if ( isset( $core_options[ $key ] ) ) {
				continue;
			}
			$core_options[ $key ] = self::hasKeys( $id )
				? $mode['name']
				: array(
					'label'    => $mode['name'],
					'disabled' => true,
				);
		}
		return $core_options;
	}

	public static function wizardCards(): array {
		$cards = array();
		foreach ( self::$modes as $id => $mode ) {
			$icon_url = '';
			if ( '' !== $mode['wizard_icon'] && class_exists( 'BotBlockerAddons' ) ) {
				$icon_url = BotBlockerAddons::fileUrl( $mode['slug'], $mode['wizard_icon'] );
			}
			$cards[] = array(
				'id'       => (int) $id,
				'title'    => $mode['name'],
				'subtitle' => $mode['wizard_subtitle'],
				'icon_url' => $icon_url,
			);
		}
		return $cards;
	}

	public static function verify( int $id, array $post_data, $bbcs ): ?bool {
		if ( ! isset( self::$modes[ $id ] ) ) {
			return false;
		}

		$verify_callback = self::$modes[ $id ]['verify_callback'];
		if ( ! is_callable( $verify_callback ) ) {
			return null;
		}

		try {
			return (bool) call_user_func( $verify_callback, $post_data, $bbcs );
		} catch ( \Throwable $e ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [CaptchaRegistry] verify callback threw for mode=' . $id . ': ' . $e->getMessage() );
			}
			return null;
		}
	}
}
