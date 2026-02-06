<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

trait BBCS_RenderRecaptchaWithButtonTrait {

    private function getRecaptchaWithButtonData() {
        $hash0 = '1|'.hash('sha256', $this->BBCS->settings->salt.$this->BBCS->time.$this->BBCS->settings->cloud_api_pass);
        $style0 = 'o'.md5($hash0);
        $onestyle = [];
        $onebtns = [];
        
        $onestyle[] = '.'.$style0.' {} ';
        $onebtns[] = [
            'html' => '<div style="cursor: pointer;" class="'.$style0.' '.'s'.md5('botblocker-btn-success'.$this->BBCS->time).'" onclick="'.$this->botblocker_check_function_name.'(\'post\', data, \''.$hash0.'\')">'.__('Go to website', 'botblocker-security').'</div>',
            'visible' => true
        ]; 

        for ($i = 0; $i < wp_rand(2,6); $i++) {
            $hash0 = '1|'.hash('sha256', $this->BBCS->settings->salt.$this->BBCS->time.$this->BBCS->settings->cloud_api_pass.wp_rand(1,99999));
            $style0 = 'o'.md5($hash0);
            $onestyle[] = '.'.$style0.' {display: none;} ';
            $onebtns[] = [
                'html' => '<div style="cursor: pointer;" class="'.$style0.' '.'s'.md5('botblocker-btn-success'.$this->BBCS->time).'" onclick="'.$this->botblocker_check_function_name.'(\'post\', data, \''.$hash0.'\')">'.__('Go to website', 'botblocker-security').'</div>',
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
