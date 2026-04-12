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
require_once __DIR__ . '/captcha/render-silent-auto-trait.php';

class BotBlockerCaptchaRenderer {

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

    public function __construct($botblocker_check_function_name) {
        $this->BBCS = BotBlocker::getInstance();
        $this->botblocker_check_function_name = $botblocker_check_function_name;
    }

    protected function createChallenge($correctAnswer, $mode) {
        $nonce = bin2hex(random_bytes(16));
        $ttl = ($mode === 8) ? 3600 : 600;

        if (function_exists('openssl_encrypt')) {
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
            set_transient('bbcs_ct_' . $nonce, 1, $ttl);
        } else {
            $payload = wp_json_encode([
                'n' => $nonce,
                't' => (string) $this->BBCS->time,
                'i' => $this->BBCS->ip,
                'm' => $mode
            ]);
            $data = base64_encode($payload);
            $hmac = hash_hmac('sha256', $data, $this->BBCS->settings->salt);
            $this->challengeToken = $data . '.' . $hmac;
            set_transient('bbcs_ct_' . $nonce, ['a' => (string) $correctAnswer], $ttl);
        }

        return $nonce;
    }

    protected function answerHash($nonce, $answer) {
        return hash('sha256', $this->BBCS->settings->salt . $nonce . (string) $answer . $this->BBCS->time . $this->BBCS->ip);
    }

    public function getChallengeToken() {
        return $this->challengeToken;
    }

    public static function verifyChallengeToken($salt, $token, $submittedHash, $submittedDate, $submittedIp) {
        if (strpos($token, '.') !== false) {
            return self::verifyChallengeTokenHmac($salt, $token, $submittedHash, $submittedDate, $submittedIp);
        }

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

    /**
     * Verify challenge token with diagnostic reason codes.
     *
     * Returns ['ok' => true, 'data' => array, 'reason' => ''] on success,
     * or ['ok' => false, 'data' => null, 'reason' => string] on failure.
     *
     * Reason codes: TD = token decrypt/decode failed, TT = transient missing/expired,
     * DM = date mismatch, HM = hash mismatch.
     */
    public static function verifyChallengeTokenDiag($salt, $token, $submittedHash, $submittedDate, $submittedIp) {
        if (strpos($token, '.') !== false) {
            return self::verifyChallengeTokenHmacDiag($salt, $token, $submittedHash, $submittedDate, $submittedIp);
        }

        $raw = base64_decode($token, true);
        if ($raw === false || strlen($raw) < 17) return ['ok' => false, 'data' => null, 'reason' => 'TD'];
        $iv = substr($raw, 0, 16);
        $encrypted = substr($raw, 16);
        $key = hash('sha256', $salt, true);
        $payload = openssl_decrypt($encrypted, 'aes-256-cbc', $key, OPENSSL_RAW_DATA, $iv);
        if ($payload === false) return ['ok' => false, 'data' => null, 'reason' => 'TD'];
        $data = json_decode($payload, true);
        if (!$data || !isset($data['n'], $data['a'], $data['t'], $data['i'])) return ['ok' => false, 'data' => null, 'reason' => 'TD'];
        $transient_key = 'bbcs_ct_' . $data['n'];
        if (!get_transient($transient_key)) return ['ok' => false, 'data' => null, 'reason' => 'TT'];
        delete_transient($transient_key);
        if ((string) $data['t'] !== (string) $submittedDate) return ['ok' => false, 'data' => null, 'reason' => 'DM'];
        $expectedHash = hash('sha256', $salt . $data['n'] . $data['a'] . $data['t'] . $data['i']);
        if (!hash_equals($expectedHash, $submittedHash)) return ['ok' => false, 'data' => null, 'reason' => 'HM'];
        return ['ok' => true, 'data' => $data, 'reason' => ''];
    }

    private static function verifyChallengeTokenHmacDiag($salt, $token, $submittedHash, $submittedDate, $submittedIp) {
        $parts = explode('.', $token, 2);
        if (count($parts) !== 2) return ['ok' => false, 'data' => null, 'reason' => 'TD'];

        $data = $parts[0];
        $hmac = $parts[1];
        $expectedHmac = hash_hmac('sha256', $data, $salt);
        if (!hash_equals($expectedHmac, $hmac)) return ['ok' => false, 'data' => null, 'reason' => 'TD'];

        $decoded = base64_decode($data, true);
        if ($decoded === false) return ['ok' => false, 'data' => null, 'reason' => 'TD'];
        $payload = json_decode($decoded, true);
        if (!$payload || !isset($payload['n'], $payload['t'], $payload['i'])) return ['ok' => false, 'data' => null, 'reason' => 'TD'];

        $transient_key = 'bbcs_ct_' . $payload['n'];
        $transient_data = get_transient($transient_key);
        if (!$transient_data || !is_array($transient_data) || !isset($transient_data['a'])) return ['ok' => false, 'data' => null, 'reason' => 'TT'];
        delete_transient($transient_key);

        if ((string) $payload['t'] !== (string) $submittedDate) return ['ok' => false, 'data' => null, 'reason' => 'DM'];

        $answer = $transient_data['a'];
        $expectedHash = hash('sha256', $salt . $payload['n'] . $answer . $payload['t'] . $payload['i']);
        if (!hash_equals($expectedHash, $submittedHash)) return ['ok' => false, 'data' => null, 'reason' => 'HM'];

        return ['ok' => true, 'data' => [
            'n' => $payload['n'],
            'a' => $answer,
            't' => $payload['t'],
            'i' => $payload['i'],
            'm' => $payload['m'] ?? null
        ], 'reason' => ''];
    }

    private static function verifyChallengeTokenHmac($salt, $token, $submittedHash, $submittedDate, $submittedIp) {
        $parts = explode('.', $token, 2);
        if (count($parts) !== 2) return false;

        $data = $parts[0];
        $hmac = $parts[1];
        $expectedHmac = hash_hmac('sha256', $data, $salt);
        if (!hash_equals($expectedHmac, $hmac)) return false;

        $decoded = base64_decode($data, true);
        if ($decoded === false) return false;
        $payload = json_decode($decoded, true);
        if (!$payload || !isset($payload['n'], $payload['t'], $payload['i'])) return false;

        $transient_key = 'bbcs_ct_' . $payload['n'];
        $transient_data = get_transient($transient_key);
        if (!$transient_data || !is_array($transient_data) || !isset($transient_data['a'])) return false;
        delete_transient($transient_key);

        if ((string) $payload['t'] !== (string) $submittedDate) return false;

        $answer = $transient_data['a'];
        $expectedHash = hash('sha256', $salt . $payload['n'] . $answer . $payload['t'] . $payload['i']);
        if (!hash_equals($expectedHash, $submittedHash)) return false;

        return [
            'n' => $payload['n'],
            'a' => $answer,
            't' => $payload['t'],
            'i' => $payload['i'],
            'm' => $payload['m'] ?? null
        ];
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
            case 8:
                return $this->getSilentAutoData();
            default:
                return $this->getSimpleButtonData();
        }
    }
}
