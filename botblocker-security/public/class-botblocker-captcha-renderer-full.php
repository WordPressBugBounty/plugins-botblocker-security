<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! defined( 'WPINC' ) || ! defined( 'BOTBLOCKER' ) ) {
	exit;
}

/**
 * BotBlocker Captcha Renderer
 *
 * @package    BotBlocker
 * @subpackage BotBlocker/captcha
 * @author     BotBlocker
 * @copyright  Copyright (c) 2026, BotBlocker
 *
 */

require_once __DIR__ . '/captcha-full/render-simple-button-full-trait.php';
require_once __DIR__ . '/captcha-full/render-color-button-full-trait.php';
require_once __DIR__ . '/captcha-full/render-image-button-full-trait.php';
require_once __DIR__ . '/captcha-full/render-recaptcha-with-button-full-trait.php';
require_once __DIR__ . '/captcha-full/render-recaptcha-without-button-full-trait.php';
require_once __DIR__ . '/captcha-full/render-moving-shapes-button-full-trait.php';
require_once __DIR__ . '/captcha-full/render-animated-math-full-trait.php';
require_once __DIR__ . '/captcha-full/render-hold-button-full-trait.php';
require_once __DIR__ . '/captcha-full/render-silent-auto-full-trait.php';
require_once __DIR__ . '/captcha-shared/class-botblocker-captcha-challenge-trait.php';

/**
 * Class BotBlockerCaptchaRenderer
 *
 * Provides unified rendering interface for different captcha types
 */
class BotBlockerCaptchaRendererFull {

	use BBCS_CaptchaChallengeTrait;
	use BotBlockerCaptchaFullRenderSimpleButtonTrait;
	use BotBlockerCaptchaFullRenderColorButtonTrait;
	use BotBlockerCaptchaFullRenderImageButtonTrait;
	use BotBlockerCaptchaFullRenderRecaptchaWithButtonTrait;
	use BotBlockerCaptchaFullRenderRecaptchaWithoutButtonTrait;
	use BotBlockerCaptchaFullRenderMovingShapesButtonTrait;
	use BotBlockerCaptchaFullRenderAnimatedMathTrait;
	use BotBlockerCaptchaFullRenderHoldButtonTrait;
	use BotBlockerCaptchaFullRenderSilentAutoTrait;
	/**
	 * @var BotBlocker Instance of BotBlocker
	 */
	private $BBCS;

	/**
	 * @var string JavaScript function name for cloud test
	 */
	private $botblocker_check_function_name;
	private $challengeToken = '';

	/**
	 * BotBlockerCaptchaRenderer constructor
	 *
	 * @param BotBlocker $BBCS Instance of BotBlocker
	 * @param string $botblocker_check_function_name JavaScript function name for cloud testing
	 */
	public function __construct( string $botblocker_check_function_name ) {
		$this->BBCS                           = BotBlocker::getInstance();
		$this->botblocker_check_function_name = $botblocker_check_function_name;
	}

	/**
	 * Render button based on captcha mode setting
	 *
	 * @return string JavaScript code for rendering the button
	 */
	public function render(): string {
		$mode = $this->BBCS->settings->bbcs_captcha_mode;

		switch ( $mode ) {
			case 0:
				return $this->renderSimpleButton();
			case 1:
				return $this->renderColorButton();
			case 2:
				return $this->renderImageButton();
			case 3:
				return $this->renderRecaptchaWithButton();
			case 4:
				return $this->renderRecaptchaWithoutButton();
			case 5:
				return $this->renderMovingShapesButton();
			case 6:
				return $this->renderAnimatedMathExpression();
			case 7:
				return $this->renderHoldButton();
			case 8:
				return $this->renderSilentAuto();
			default:
				return $this->renderSimpleButton();
		}
	}

}
