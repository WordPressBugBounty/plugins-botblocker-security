<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

trait BBCS_RenderImageButtonTrait {

    private function getImageButtonData() {
        $color_base64 = [
            'RED' => '1',
            'BLACK' => '2',
            'YELLOW' => '3',
            'GRAY' => '4',
            'BLUE' => '5',
            'GREEN' => '6',
            'MAROON' => '7',
            'PURPLE' => '8'
        ];

        $colors = $this->BBCS->list_of_colors_for_captcha;
        shuffle($colors);
        $color = $colors[0];
        $colorhash = hash('sha256', $this->BBCS->settings->salt . $color . $this->BBCS->time . $this->BBCS->settings->cloud_api_pass . $this->BBCS->ip);

        $buttonElements = [];
        $imageRequests = [];
        
        foreach ($colors as $btnColor) {
            $hashForID = md5($this->BBCS->time . $this->BBCS->settings->salt . $color_base64[$btnColor]);
            $buttonElements[] = '<span id="' . $hashForID . '" style="cursor: pointer;" onclick="' . $this->botblocker_check_function_name . '(\'post\', data, \'' . $btnColor . '|' . $colorhash . '\')"></span>';
            $imageRequests[] = [
                'imageParam' => $color_base64[$btnColor],
                'elementId' => $hashForID
            ];
        }
        
        shuffle($buttonElements);
        shuffle($imageRequests);

        $red = wp_rand(10, 50);
        $green = wp_rand(10, 50);
        $blue = wp_rand(10, 50);
        
        $imageForCheck = imagecreatefromjpeg($this->BBCS->dirs['public'] . 'img/'.$this->BBCS->settings->bbcs_captcha_img_pack.'/' . $color_base64[$color] . '.jpg');
        imagefilter($imageForCheck, IMG_FILTER_COLORIZE, $red, $green, $blue);
        $brightness = wp_rand(-50, 50);
        $contrast = wp_rand(-50, 50);
        imagefilter($imageForCheck, IMG_FILTER_BRIGHTNESS, $brightness);
        imagefilter($imageForCheck, IMG_FILTER_CONTRAST, $contrast);

        for ($i = 0; $i < 5; $i++) {
            $line_color = imagecolorallocate($imageForCheck, wp_rand(0, 255), wp_rand(0, 255), wp_rand(0, 255));
            imageline(
                $imageForCheck,
                wp_rand(0, imagesx($imageForCheck)),
                wp_rand(0, imagesy($imageForCheck)),
                wp_rand(0, imagesx($imageForCheck)),
                wp_rand(0, imagesy($imageForCheck)),
                $line_color
            );
        }
        
        imagefilter($imageForCheck, IMG_FILTER_GAUSSIAN_BLUR);
        imagefilter($imageForCheck, IMG_FILTER_MEAN_REMOVAL);
        ob_start();
        imagepng($imageForCheck);
        $image_data = ob_get_contents();
        imagedestroy($imageForCheck);
        ob_end_clean();

        return [
            'mode' => 2,
            'params' => [
                'targetImageData' => base64_encode($image_data),
                'instruction' => __('If you are human, click on the similar image', 'botblocker-security'),
                'buttons' => $buttonElements,
                'imageRequests' => $imageRequests,
                'ajaxUrl' => admin_url('admin-ajax.php'),
                'nonce' => wp_create_nonce('botblocker_nonce'),
                'time' => $this->BBCS->time,
                'selectRequestMode' => $this->BBCS->select_request_mode
            ]
        ];
    }
}
