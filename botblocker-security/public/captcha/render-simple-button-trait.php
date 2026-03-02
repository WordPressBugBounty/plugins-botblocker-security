<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

trait BBCS_RenderSimpleButtonTrait {

    private function getSimpleButtonData() {
        $nonce = $this->createChallenge('confirm', 0);
        $hash0 = $this->answerHash($nonce, 'confirm');
        $btnClass = 's' . md5('botblocker-btn-success' . $this->BBCS->time);

        return [
            'mode' => 0,
            'params' => [
                'buttonHash' => $hash0,
                'buttonClass' => $btnClass,
                'confirmText' => __('Confirm that you are human:', 'botblocker-security'),
                'buttonText' => __("I'm not a robot", 'botblocker-security'),
            ]
        ];
    }
}
