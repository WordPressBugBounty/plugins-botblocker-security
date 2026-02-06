<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

trait BBCS_RenderColorButtonTrait {

    private function getColorButtonData() {
        $color_base64 = [
            'RED' => 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8DwHwAFBQIAX8jx0gAAAABJRU5ErkJggg==',
            'BLACK' => 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNk+A8AAQUBAScY42YAAAAASUVORK5CYII=',
            'YELLOW' => 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8/5/hPwAIAgL/4d1j8wAAAABJRU5ErkJggg==',
            'GRAY' => 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAQAAAC1HAwCAAAAC0lEQVR42mNs+A8AAgUBgQvw1B0AAAAASUVORK5CYII=',
            'BLUE' => 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkYPj/HwADBwIAMCbHYQAAAABJRU5ErkJggg==',
            'GREEN' => 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mNkaGD4DwACiQGBU29HsgAAAABJRU5ErkJggg==',
            'MAROON' => 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAAMSURBVBhXY2hgYAAAAYQAgVMkorQAAAAASUVORK5CYII=',
            'PURPLE' => 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAAAXNSR0IArs4c6QAAAARnQU1BAACxjwv8YQUAAAAJcEhZcwAADsMAAA7DAcdvqGQAAAAMSURBVBhXY/jP8B8ABAAB/4jQ/cwAAAAASUVORK5CYII='
        ];

        $colors = $this->BBCS->list_of_colors_for_captcha;
        shuffle($colors);
        $color = $colors[0];
        
        $colorhash = hash('sha256', $this->BBCS->settings->salt . $color . $this->BBCS->time . $this->BBCS->settings->cloud_api_pass.$this->BBCS->ip);

        shuffle($colors);
        $tags = array('div', 'span', 'b', 'strong', 'i', 'em');
        shuffle($tags);
        $buttonElements = [];

        foreach ($colors as $btnColor) {
            $buttonElements[] = '<'.$tags[0].' style="background-image: url(data:image/png;base64,'.$color_base64[$btnColor].');" class="s'.md5('botblocker-btn-color'.$this->BBCS->time).'" onclick="'.$this->botblocker_check_function_name.'(\'post\', data, \''.$btnColor.'|'.$colorhash.'\')">'.'</'.$tags[0].'>';
            $buttonElements[] = '<'.$tags[0].' style="background-image: url(data:image/png;base64,'.$color_base64[$btnColor].');display:none;" class="s'.md5('botblocker-btn-color'.$this->BBCS->time).'" onclick="'.$this->botblocker_check_function_name.'(\'post\', data, \''.$btnColor.'|'.md5($colorhash).'\')">'.'</'.$tags[0].'>';
        }
        shuffle($buttonElements);

        $imageForCheck = imagecreatetruecolor(wp_rand(1,30), wp_rand(1,30));

        $color_code = [
            'RED' => imagecolorallocate($imageForCheck, wp_rand(220,255), wp_rand(0,30), wp_rand(0,30)),
            'BLACK' => imagecolorallocate($imageForCheck, wp_rand(0,15), wp_rand(0,25), wp_rand(0,25)),
            'YELLOW' => imagecolorallocate($imageForCheck, wp_rand(245,255), wp_rand(220,255), wp_rand(0,25)),
            'GRAY' => imagecolorallocate($imageForCheck, wp_rand(120,130), wp_rand(125,135), wp_rand(125,135)),
            'BLUE' => imagecolorallocate($imageForCheck, wp_rand(0,30), wp_rand(0,30), wp_rand(155,255)),
            'GREEN' => imagecolorallocate($imageForCheck, wp_rand(0,30), wp_rand(125,250), wp_rand(0,30)),
            'MAROON' => imagecolorallocate($imageForCheck, wp_rand(120,130), wp_rand(0,20), wp_rand(0,20)),
            'PURPLE' => imagecolorallocate($imageForCheck, wp_rand(120,130), wp_rand(0,20), wp_rand(120,130))
        ];

        imagefill($imageForCheck, 0, 0, $color_code[$color]);
        ob_start();
        imagepng($imageForCheck);
        imagedestroy($imageForCheck);
        $image_data = ob_get_contents();
        ob_end_clean();

        return [
            'mode' => 1,
            'params' => [
                'buttons' => $buttonElements,
                'instruction' => __('If you are human, click on the similar color', 'botblocker-security'),
                'colorImageData' => base64_encode($image_data),
                'colorImageId' => md5('botblocker-btn-color'.$this->BBCS->time)
            ]
        ];
    }
}