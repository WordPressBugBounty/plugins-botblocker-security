<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

trait BBCS_RenderRecaptchaWithoutButtonTrait {

    private function getRecaptchaWithoutButtonData() {
        $hash0 = '1|'.hash('sha256', $this->BBCS->settings->salt.$this->BBCS->time.$this->BBCS->settings->cloud_api_pass);

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
