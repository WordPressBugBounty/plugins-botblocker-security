<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require_once __DIR__ . '/captcha/render-simple-button-trait.php';
require_once __DIR__ . '/captcha/render-color-button-trait.php';
require_once __DIR__ . '/captcha/render-image-button-trait.php';
require_once __DIR__ . '/captcha/render-recaptcha-with-button-trait.php';
require_once __DIR__ . '/captcha/render-recaptcha-without-button-trait.php';
require_once __DIR__ . '/captcha/render-moving-shapes-button-trait.php';
require_once __DIR__ . '/captcha/render-animated-math-expression-trait.php';
require_once __DIR__ . '/captcha/render-hold-button-trait.php';
require_once __DIR__ . '/captcha/render-silent-auto-trait.php';
require_once __DIR__ . '/captcha-shared/class-botblocker-captcha-challenge-trait.php';

class BotBlockerCaptchaRenderer {

	use BBCS_CaptchaChallengeTrait;
	use BBCS_RenderSimpleButtonTrait;
	use BBCS_RenderColorButtonTrait;
	use BBCS_RenderImageButtonTrait;
	use BBCS_RenderRecaptchaWithButtonTrait;
	use BBCS_RenderRecaptchaWithoutButtonTrait;
	use BBCS_RenderMovingShapesButtonTrait;
	use BBCS_RenderAnimatedMathExpressionTrait;
	use BBCS_RenderHoldButtonTrait;
	use BBCS_RenderSilentAutoTrait;

	private $BBCS;
	private $botblocker_check_function_name;
	private $challengeToken = '';

	public function __construct( string $botblocker_check_function_name ) {
		$this->BBCS                           = BotBlocker::getInstance();
		$this->botblocker_check_function_name = $botblocker_check_function_name;
	}

	public static function t( string $text ): string {
		$bbcs = BotBlocker::getInstance();
		if ( (int) $bbcs->settings->secure_mode === BotBlocker::SECURE_MODE_FRONTEND && function_exists( '__' ) ) {
			// phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText -- $text is a dynamic translation key passed via the t() wrapper
			return __( $text, 'botblocker-security' );
		}
		return $text;
	}

	public static function verifyChallengeToken( string $salt, string $token, string $submittedHash, string $submittedDate, string $submittedIp ) {
		if ( strpos( $token, '.' ) !== false ) {
			return self::verifyChallengeTokenHmac( $salt, $token, $submittedHash, $submittedDate, $submittedIp );
		}

		$raw = base64_decode( $token, true );
		if ( $raw === false || strlen( $raw ) < 17 ) {
			return false;
		}
		$iv        = substr( $raw, 0, 16 );
		$encrypted = substr( $raw, 16 );
		$key       = hash( 'sha256', $salt, true );
		$payload   = openssl_decrypt( $encrypted, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv );
		if ( $payload === false ) {
			return false;
		}
		$data = json_decode( $payload, true );
		if ( ! $data || ! isset( $data['n'], $data['a'], $data['t'], $data['i'] ) ) {
			return false;
		}
		$transient_key = 'bbcs_ct_' . $data['n'];
		if ( ! get_transient( $transient_key ) ) {
			return false;
		}
		delete_transient( $transient_key );
		if ( (string) $data['t'] !== (string) $submittedDate ) {
			return false;
		}
		$expectedHash = hash( 'sha256', $salt . $data['n'] . $data['a'] . $data['t'] . $data['i'] );
		if ( ! hash_equals( $expectedHash, $submittedHash ) ) {
			return false;
		}
		return $data;
	}

	/**
	 * Verify challenge token with diagnostic reason codes.
	 *
	 * Returns ['ok' => true, 'data' => array, 'reason' => ''] on success,
	 * or ['ok' => false, 'data' => null, 'reason' => string] on failure.
	 *
	 * Reason codes: TD = token decrypt/decode failed, TT = transient missing/expired,
	 * DM = date mismatch, HM = hash mismatch.
	 */
	public static function verifyChallengeTokenDiag( string $salt, string $token, string $submittedHash, string $submittedDate, string $submittedIp ): array {
		if ( strpos( $token, '.' ) !== false ) {
			return self::verifyChallengeTokenHmacDiag( $salt, $token, $submittedHash, $submittedDate, $submittedIp );
		}

		$raw = base64_decode( $token, true );
		if ( $raw === false || strlen( $raw ) < 17 ) {
			return array(
				'ok'     => false,
				'data'   => null,
				'reason' => 'TD',
			);
		}
		$iv        = substr( $raw, 0, 16 );
		$encrypted = substr( $raw, 16 );
		$key       = hash( 'sha256', $salt, true );
		$payload   = openssl_decrypt( $encrypted, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv );
		if ( $payload === false ) {
			return array(
				'ok'     => false,
				'data'   => null,
				'reason' => 'TD',
			);
		}
		$data = json_decode( $payload, true );
		if ( ! $data || ! isset( $data['n'], $data['a'], $data['t'], $data['i'] ) ) {
			return array(
				'ok'     => false,
				'data'   => null,
				'reason' => 'TD',
			);
		}
		$transient_key = 'bbcs_ct_' . $data['n'];
		if ( ! get_transient( $transient_key ) ) {
			return array(
				'ok'     => false,
				'data'   => null,
				'reason' => 'TT',
			);
		}
		delete_transient( $transient_key );
		if ( (string) $data['t'] !== (string) $submittedDate ) {
			return array(
				'ok'     => false,
				'data'   => null,
				'reason' => 'DM',
			);
		}
		$expectedHash = hash( 'sha256', $salt . $data['n'] . $data['a'] . $data['t'] . $data['i'] );
		if ( ! hash_equals( $expectedHash, $submittedHash ) ) {
			return array(
				'ok'     => false,
				'data'   => null,
				'reason' => 'HM',
			);
		}
		return array(
			'ok'     => true,
			'data'   => $data,
			'reason' => '',
		);
	}

	private static function verifyChallengeTokenHmacDiag( string $salt, string $token, string $submittedHash, string $submittedDate, string $submittedIp ): array {
		$parts = explode( '.', $token, 2 );
		if ( count( $parts ) !== 2 ) {
			return array(
				'ok'     => false,
				'data'   => null,
				'reason' => 'TD',
			);
		}

		$data         = $parts[0];
		$hmac         = $parts[1];
		$expectedHmac = hash_hmac( 'sha256', $data, $salt );
		if ( ! hash_equals( $expectedHmac, $hmac ) ) {
			return array(
				'ok'     => false,
				'data'   => null,
				'reason' => 'TD',
			);
		}

		$decoded = base64_decode( $data, true );
		if ( $decoded === false ) {
			return array(
				'ok'     => false,
				'data'   => null,
				'reason' => 'TD',
			);
		}
		$payload = json_decode( $decoded, true );
		if ( ! $payload || ! isset( $payload['n'], $payload['t'], $payload['i'] ) ) {
			return array(
				'ok'     => false,
				'data'   => null,
				'reason' => 'TD',
			);
		}

		$transient_key  = 'bbcs_ct_' . $payload['n'];
		$transient_data = get_transient( $transient_key );
		if ( ! $transient_data || ! is_array( $transient_data ) || ! isset( $transient_data['a'] ) ) {
			return array(
				'ok'     => false,
				'data'   => null,
				'reason' => 'TT',
			);
		}
		delete_transient( $transient_key );

		if ( (string) $payload['t'] !== (string) $submittedDate ) {
			return array(
				'ok'     => false,
				'data'   => null,
				'reason' => 'DM',
			);
		}

		$answer       = $transient_data['a'];
		$expectedHash = hash( 'sha256', $salt . $payload['n'] . $answer . $payload['t'] . $payload['i'] );
		if ( ! hash_equals( $expectedHash, $submittedHash ) ) {
			return array(
				'ok'     => false,
				'data'   => null,
				'reason' => 'HM',
			);
		}

		return array(
			'ok'     => true,
			'data'   => array(
				'n' => $payload['n'],
				'a' => $answer,
				't' => $payload['t'],
				'i' => $payload['i'],
				'm' => $payload['m'] ?? null,
			),
			'reason' => '',
		);
	}

	private static function verifyChallengeTokenHmac( string $salt, string $token, string $submittedHash, string $submittedDate, string $submittedIp ) {
		$parts = explode( '.', $token, 2 );
		if ( count( $parts ) !== 2 ) {
			return false;
		}

		$data         = $parts[0];
		$hmac         = $parts[1];
		$expectedHmac = hash_hmac( 'sha256', $data, $salt );
		if ( ! hash_equals( $expectedHmac, $hmac ) ) {
			return false;
		}

		$decoded = base64_decode( $data, true );
		if ( $decoded === false ) {
			return false;
		}
		$payload = json_decode( $decoded, true );
		if ( ! $payload || ! isset( $payload['n'], $payload['t'], $payload['i'] ) ) {
			return false;
		}

		$transient_key  = 'bbcs_ct_' . $payload['n'];
		$transient_data = get_transient( $transient_key );
		if ( ! $transient_data || ! is_array( $transient_data ) || ! isset( $transient_data['a'] ) ) {
			return false;
		}
		delete_transient( $transient_key );

		if ( (string) $payload['t'] !== (string) $submittedDate ) {
			return false;
		}

		$answer       = $transient_data['a'];
		$expectedHash = hash( 'sha256', $salt . $payload['n'] . $answer . $payload['t'] . $payload['i'] );
		if ( ! hash_equals( $expectedHash, $submittedHash ) ) {
			return false;
		}

		return array(
			'n' => $payload['n'],
			'a' => $answer,
			't' => $payload['t'],
			'i' => $payload['i'],
			'm' => $payload['m'] ?? null,
		);
	}

	public function getCaptchaData(): array {
		$mode = $this->BBCS->settings->bbcs_captcha_mode;

		if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
			// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
			error_log( '[BBCS DEBUG] [Renderer] getCaptchaData: mode=' . $mode . ' secure_mode=' . $this->BBCS->settings->secure_mode . ' recaptcha_key2=' . ( empty( $this->BBCS->settings->recaptcha_key2 ) ? 'EMPTY' : 'SET' ) );
		}

		switch ( $mode ) {
			case 0:
				return $this->getSimpleButtonData();
			case 1:
				return $this->getColorButtonData();
			case 2:
				return $this->getImageButtonData();
			case 3:
				return $this->getRecaptchaWithButtonData();
			case 4:
				return $this->getRecaptchaWithoutButtonData();
			case 5:
				return $this->getMovingShapesButtonData();
			case 6:
				return $this->getAnimatedMathExpressionData();
			case 7:
				return $this->getHoldButtonData();
			case 8:
				return $this->getSilentAutoData();
			default:
				return $this->getCaptchaDataAddon( (int) $mode );
		}
	}

	private function getCaptchaDataAddon( int $mode ): array {
		if ( ! class_exists( 'BotBlockerCaptchaRegistry' ) || ! BotBlockerCaptchaRegistry::has( $mode ) ) {
			return $this->getSimpleButtonData();
		}

		try {
			$data = BotBlockerCaptchaRegistry::getParams( $mode );
			if ( ! isset( $data['params'] ) || ! is_array( $data['params'] ) ) {
				return $this->getSimpleButtonData();
			}
			// Core owns the challenge token — the addon never sees the salt.
			$nonce                  = $this->createChallenge( 'confirm', $mode );
			$data['mode']           = $mode;
			$data['params']['hash'] = $this->answerHash( $nonce, 'confirm' );
			return $data;
		} catch ( \Throwable $e ) {
			if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) {
				// phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log -- guarded by BBCS_DEBUG
				error_log( '[BBCS DEBUG] [Renderer] addon captcha mode ' . $mode . ' failed, falling back to mode 0: ' . $e->getMessage() );
			}
			return $this->getSimpleButtonData();
		}
	}

	public function render(): string {
		$data = $this->getCaptchaData();
		$mode = (int) ( $data['mode'] ?? 0 );
		$fn   = $this->botblocker_check_function_name;
		$dir  = $this->BBCS->dirs['public'];

		require_once dirname( __DIR__ ) . '/includes/botblocker/class-botblocker-security-page-assets.php';

		$js  = 'window.bbcsJsData = ' . BotBlockerSecurityPageAssets::json( array( 'checkFunctionName' => $fn ) ) . ";\n";
		$js .= 'var bbcsCaptchaData = ' . BotBlockerSecurityPageAssets::json( $data ) . ";\n";
		$js .= BotBlockerSecurityPageAssets::read( $dir, 'captcha-js/captcha.js' ) . "\n";
		$js .= BotBlockerSecurityPageAssets::read( $dir, 'captcha-js/mode' . $mode . '.js' ) . "\n";
		$js .= "renderCaptcha();\n";

		return $js;
	}
}
