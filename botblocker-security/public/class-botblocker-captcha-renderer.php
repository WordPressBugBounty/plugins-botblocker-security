<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

require_once __DIR__ . '/captcha/render-simple-button-trait.php';
require_once __DIR__ . '/captcha/render-color-button-trait.php';
require_once __DIR__ . '/captcha/render-image-button-trait.php';
require_once __DIR__ . '/captcha/render-recaptcha-with-button-trait.php';
require_once __DIR__ . '/captcha/render-recaptcha-without-button-trait.php';
require_once __DIR__ . '/captcha/render-moving-shapes-button-trait.php';
require_once __DIR__ . '/captcha/render-animated-math-expression-trait.php';
require_once __DIR__ . '/captcha/render-hold-button-trait.php';

class BotBlockerCaptchaRenderer {

    use BBCS_RenderSimpleButtonTrait;
    use BBCS_RenderColorButtonTrait;
    use BBCS_RenderImageButtonTrait;
    use BBCS_RenderRecaptchaWithButtonTrait;
    use BBCS_RenderRecaptchaWithoutButtonTrait;
    use BBCS_RenderMovingShapesButtonTrait;
    use BBCS_RenderAnimatedMathExpressionTrait;
    use BBCS_RenderHoldButtonTrait;

    private $BBCS;
    private $botblocker_check_function_name;
    private $challengeToken = '';

    public function __construct($botblocker_check_function_name) {
        $this->BBCS = BotBlocker::getInstance();
        $this->botblocker_check_function_name = $botblocker_check_function_name;
    }

    protected function createChallenge($correctAnswer, $mode) {
        $nonce = bin2hex(random_bytes(16));
        $key = hash('sha256', $this->BBCS->settings->salt, true);
        $iv = random_bytes(16);
        $payload = wp_json_encode([
            'n' => $nonce,
            'a' => (string) $correctAnswer,
            't' => (string) $this->BBCS->time,
            'i' => $this->BBCS->ip,
            'm' => $mode
        ]);
        $encrypted = openssl_encrypt($payload, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        $this->challengeToken = base64_encode($iv . $encrypted);
        set_transient('bbcs_ct_' . $nonce, 1, 600);
        return $nonce;
    }

    protected function answerHash($nonce, $answer) {
        return hash('sha256', $this->BBCS->settings->salt . $nonce . (string) $answer . $this->BBCS->time . $this->BBCS->ip);
    }

    public function getChallengeToken() {
        return $this->challengeToken;
    }

    public static function verifyChallengeToken($salt, $token, $submittedHash, $submittedDate, $submittedIp) {
        $raw = base64_decode($token, true);
        if ($raw === false || strlen($raw) < 17) return false;
        $iv = substr($raw, 0, 16);
        $encrypted = substr($raw, 16);
        $key = hash('sha256', $salt, true);
        $payload = openssl_decrypt($encrypted, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        if ($payload === false) return false;
        $data = json_decode($payload, true);
        if (!$data || !isset($data['n'], $data['a'], $data['t'], $data['i'])) return false;
        $transient_key = 'bbcs_ct_' . $data['n'];
        if (!get_transient($transient_key)) return false;
        delete_transient($transient_key);
        if ((string) $data['t'] !== (string) $submittedDate) return false;
        $expectedHash = hash('sha256', $salt . $data['n'] . $data['a'] . $data['t'] . $data['i']);
        if (!hash_equals($expectedHash, $submittedHash)) return false;
        return $data;
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
            case 7:
                return $this->getHoldButtonData();
            default:
                return $this->getSimpleButtonData();
        }
    }
}
