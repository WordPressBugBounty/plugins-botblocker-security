<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

trait BBCS_RenderRecaptchaWithButtonTrait {

    private function getRecaptchaWithButtonData() {
        $nonce = $this->createChallenge('confirm', 3);
        $correctHash = $this->answerHash($nonce, 'confirm');
        $style0 = 'o'.md5($correctHash);
        $onestyle = [];
        $onebtns = [];
        
        $onestyle[] = '.'.$style0.' {} ';
        $onebtns[] = [
            'html' => '<div style="cursor: pointer;" class="'.$style0.' '.'s'.md5('botblocker-btn-success'.$this->BBCS->time).'" onclick="'.$this->botblocker_check_function_name.'(\'post\', data, \''.$correctHash.'\')">'.__('Go to website', 'botblocker-security').'</div>',
            'visible' => true
        ]; 

        for ($i = 0; $i < wp_rand(2,6); $i++) {
            $fakeHash = $this->answerHash($nonce, 'decoy_' . wp_rand(1, 99999));
            $fakeStyle = 'o'.md5($fakeHash);
            $onestyle[] = '.'.$fakeStyle.' {display: none;} ';
            $onebtns[] = [
                'html' => '<div style="cursor: pointer;" class="'.$fakeStyle.' '.'s'.md5('botblocker-btn-success'.$this->BBCS->time).'" onclick="'.$this->botblocker_check_function_name.'(\'post\', data, \''.$fakeHash.'\')">'.__('Go to website', 'botblocker-security').'</div>',
                'visible' => false
            ];
        }
        
        shuffle($onebtns);
        shuffle($onestyle);

        return [
            'mode' => 3,
            'params' => [
                'buttons' => $onebtns,
                'styles' => $onestyle,
                'confirmText' => __('Confirm that you are human:', 'botblocker-security'),
                'loadingText' => __('Loading...', 'botblocker-security'),
                'recaptchaKey' => $this->BBCS->settings->recaptcha_key2,
                'ajaxUrl' => admin_url('admin-ajax.php')
            ]
        ];
    }
}
