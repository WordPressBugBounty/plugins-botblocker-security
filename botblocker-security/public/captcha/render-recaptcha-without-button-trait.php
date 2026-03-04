<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

trait BBCS_RenderRecaptchaWithoutButtonTrait {

    private function getRecaptchaWithoutButtonData() {
        $nonce = $this->createChallenge('confirm', 4);
        $hash0 = $this->answerHash($nonce, 'confirm');

        return [
            'mode' => 4,
            'params' => [
                'confirmText' => __('Confirm that you are human:', 'botblocker-security'),
                'recaptchaKey' => $this->BBCS->settings->recaptcha_key2,
                'hash' => $hash0
            ]
        ];
    }
}
