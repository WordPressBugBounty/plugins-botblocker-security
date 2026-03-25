<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

trait BBCS_RenderColorButtonTrait {

    private function getColorButtonData() {
        $colors = $this->BBCS->list_of_colors_for_captcha;
        shuffle($colors);
        $color = $colors[0];

        $nonce = $this->createChallenge($color, 1);

        shuffle($colors);
        $colorClass = 's' . md5('botblocker-btn-color' . $this->BBCS->time);
        $buttonData = [];

        $color_rgb = [
            'RED' => [wp_rand(220,255), wp_rand(0,30), wp_rand(0,30)],
            'BLACK' => [wp_rand(0,15), wp_rand(0,25), wp_rand(0,25)],
            'YELLOW' => [wp_rand(245,255), wp_rand(220,255), wp_rand(0,25)],
            'GRAY' => [wp_rand(120,130), wp_rand(125,135), wp_rand(125,135)],
            'BLUE' => [wp_rand(0,30), wp_rand(0,30), wp_rand(155,255)],
            'GREEN' => [wp_rand(0,30), wp_rand(125,250), wp_rand(0,30)],
            'MAROON' => [wp_rand(120,130), wp_rand(0,20), wp_rand(0,20)],
            'PURPLE' => [wp_rand(120,130), wp_rand(0,20), wp_rand(120,130)]
        ];

        foreach ($colors as $btnColor) {
            $sz = wp_rand(8, 20);
            $img = imagecreatetruecolor($sz, $sz);
            $rgb = $color_rgb[$btnColor];
            $c = imagecolorallocate($img, $rgb[0], $rgb[1], $rgb[2]);
            imagefill($img, 0, 0, $c);
            for ($n = 0; $n < wp_rand(2, 6); $n++) {
                $nc = imagecolorallocate($img, wp_rand(0,255), wp_rand(0,255), wp_rand(0,255));
                imagesetpixel($img, wp_rand(0, $sz-1), wp_rand(0, $sz-1), $nc);
            }
            ob_start();
            imagepng($img);
            if (PHP_VERSION_ID < 80000) {
                imagedestroy($img);
            }
            $btnImgData = base64_encode(ob_get_clean());

            $buttonData[] = [
                'image' => $btnImgData,
                'hash' => $this->answerHash($nonce, $btnColor),
            ];
        }
        shuffle($buttonData);

        $targetSz = wp_rand(14, 30);
        $targetImg = imagecreatetruecolor($targetSz, $targetSz);
        $rgb = $color_rgb[$color];
        $tc = imagecolorallocate($targetImg, $rgb[0], $rgb[1], $rgb[2]);
        imagefill($targetImg, 0, 0, $tc);
        ob_start();
        imagepng($targetImg);
        if (PHP_VERSION_ID < 80000) {
            imagedestroy($targetImg);
        }
        $targetImgData = base64_encode(ob_get_clean());

        return [
            'mode' => 1,
            'params' => [
                'buttons' => $buttonData,
                'instruction' => __('Click on the matching color', 'botblocker-security'),
                'colorImageData' => $targetImgData,
                'colorClass' => $colorClass,
            ]
        ];
    }
}