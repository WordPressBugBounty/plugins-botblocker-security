<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once __DIR__ . '/captcha/render-simple-button-trait.php';
require_once __DIR__ . '/captcha/render-color-button-trait.php';
require_once __DIR__ . '/captcha/render-image-button-trait.php';
require_once __DIR__ . '/captcha/render-recaptcha-with-button-trait.php';
require_once __DIR__ . '/captcha/render-recaptcha-without-button-trait.php';
require_once __DIR__ . '/captcha/render-moving-shapes-button-trait.php';
require_once __DIR__ . '/captcha/render-animated-math-expression-trait.php';

class BotBlockerCaptchaRenderer {

    use BBCS_RenderSimpleButtonTrait;
    use BBCS_RenderColorButtonTrait;
    use BBCS_RenderImageButtonTrait;
    use BBCS_RenderRecaptchaWithButtonTrait;
    use BBCS_RenderRecaptchaWithoutButtonTrait;
    use BBCS_RenderMovingShapesButtonTrait;
    use BBCS_RenderAnimatedMathExpressionTrait;

    private $BBCS;
    private $botblocker_check_function_name;

    public function __construct($botblocker_check_function_name) {
        $this->BBCS = BotBlocker::getInstance();
        $this->botblocker_check_function_name = $botblocker_check_function_name;
    }

    public function getCaptchaData() {
        $mode = $this->BBCS->settings->bbcs_captcha_mode;
        
        switch ($mode) {
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
            default:
                return $this->getSimpleButtonData();
        }
    }
}
