<?php
if ( ! defined( 'ABSPATH' ) ) exit;
if (!defined('WPINC') || !defined('BOTBLOCKER')) {
    exit;
}

/**
 * BotBlocker Captcha Renderer
 *
 * @package    BotBlocker
 * @subpackage BotBlocker/captcha
 * @author     BotBlocker
 * @copyright  Copyright (c) 2025, BotBlocker
 * 
 */

/**
 * Class BotBlockerCaptchaRenderer
 * 
 * Provides unified rendering interface for different captcha types
 */
class BotBlockerCaptchaRendererFull {
    /**
     * @var BotBlocker Instance of BotBlocker
     */
    private $BBCS;
    
    /**
     * @var string JavaScript function name for cloud test
     */
    private $botblocker_check_function_name;
    private $challengeToken = '';
    
    /**
     * BotBlockerCaptchaRenderer constructor
     * 
     * @param BotBlocker $BBCS Instance of BotBlocker
     * @param string $botblocker_check_function_name JavaScript function name for cloud testing
     */
    public function __construct($botblocker_check_function_name) {
        $this->BBCS = BotBlocker::getInstance();
        $this->botblocker_check_function_name = $botblocker_check_function_name;
    }

    private function createChallenge($correctAnswer, $mode) {
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

    private function answerHash($nonce, $answer) {
        return hash('sha256', $this->BBCS->settings->salt . $nonce . (string) $answer . $this->BBCS->time . $this->BBCS->ip);
    }

    public function getChallengeToken() {
        return $this->challengeToken;
    }
    
    /**
     * Render button based on captcha mode setting
     * 
     * @return string JavaScript code for rendering the button
     */
    public function render() {
        $mode = $this->BBCS->settings->bbcs_captcha_mode;
        
        switch ($mode) {
            case 0:
                return $this->renderSimpleButton();
            case 1:
                return $this->renderColorButton();
            case 2:
                return $this->renderImageButton();
            case 3:
                return $this->renderRecaptchaWithButton();
            case 4:
                return $this->renderRecaptchaWithoutButton();
            case 5:
                return $this->renderMovingShapesButton();
            case 6:
                return $this->renderAnimatedMathExpression();
            case 7:
                return $this->renderHoldButton();
            default:
                return $this->renderSimpleButton();
        }
    }
    
    /**
     * Mode 0: One big "I'm not a robot" button
     * 
     * @return string JavaScript code
     */
    private function renderSimpleButton() {
        $nonce = $this->createChallenge('confirm', 0);
        $hash0 = $this->answerHash($nonce, 'confirm');
        $btnClass = 's'.md5('botblocker-btn-success'.$this->BBCS->time);

        return 'document.getElementById("content").innerHTML = clean_and_decode_base64_to_utf8("'.base64_encode('<p>Confirm that you are human:</p><div style="cursor:pointer;" class="'.$btnClass.'" onclick="'.$this->botblocker_check_function_name.'(\'post\', data, \''.$hash0.'\')">I\'m not a robot</div>').'");';
    }
    
    /**
     * Mode 1: Click on similar color
     * 
     * @return string JavaScript code
     */
    private function renderColorButton() {
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
        
        $nonce = $this->createChallenge($color, 1);

        shuffle($colors);
        $tags = array('div', 'span', 'b', 'strong', 'i', 'em');
        shuffle($tags);
        $buttons = [];

        foreach ($colors as $btnColor) {
            $hash = $this->answerHash($nonce, $btnColor);
            $buttons[] = '<'.$tags[0].' style=\"background-image: url(data:image/png;base64,'.$color_base64[$btnColor].');\" class=\"'.'s'.md5('botblocker-btn-color'.$this->BBCS->time).'\" onclick=\"'.$this->botblocker_check_function_name.'(\'post\', data, \''.$hash.'\')\"></'.$tags[0].'> ';
        }
        shuffle($buttons);
        $buttons = '<div style=\"max-width: 200px;\">'.implode('',$buttons).'</div>';

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
        if (PHP_VERSION_ID < 80000) {
            imagedestroy($imageForCheck);
        }
        $image_data = ob_get_contents();
        ob_end_clean();

        return 'document.getElementById("content").innerHTML = "<div class=\"s'.md5('botblocker-btn-color'.$this->BBCS->time).'\" style=\"cursor: none; pointer-events: none; background-image: url(data:image/png;base64,'.base64_encode($image_data).');\" /></div><p>'.'If you are human, click on the similar color'.'</p>'.$buttons.'";';
    }
    
    /**
     * Mode 2: Select similar image
     * 
     * @return string JavaScript code
     */
    private function renderImageButton() {
        $color_ids = array(
            'RED'    => '1',
            'BLACK'  => '2',
            'YELLOW' => '3',
            'GRAY'   => '4',
            'BLUE'   => '5',
            'GREEN'  => '6',
            'MAROON' => '7',
            'PURPLE' => '8',
        );

        $colors = $this->BBCS->list_of_colors_for_captcha;
        shuffle($colors);
        $color     = $colors[0];
        $colorhash = hash('sha256', $this->BBCS->settings->salt . $color . $this->BBCS->time . $this->BBCS->settings->cloud_api_pass . $this->BBCS->ip);
        $img_dir   = $this->BBCS->dirs['public'] . 'img/' . $this->BBCS->settings->bbcs_captcha_img_pack . '/';
        $fn        = $this->botblocker_check_function_name;

        $inline_mode = isset($this->BBCS->settings->bbcs_captcha_img_inline)
            ? (int) $this->BBCS->settings->bbcs_captcha_img_inline
            : 1;

        $red   = wp_rand(10, 50);
        $green = wp_rand(10, 50);
        $blue  = wp_rand(10, 50);

        $image_for_check = imagecreatefromjpeg($img_dir . $color_ids[$color] . '.jpg');
        imagefilter($image_for_check, IMG_FILTER_COLORIZE, $red, $green, $blue);
        imagefilter($image_for_check, IMG_FILTER_BRIGHTNESS, wp_rand(-50, 50));
        imagefilter($image_for_check, IMG_FILTER_CONTRAST, wp_rand(-50, 50));

        for ($i = 0; $i < 5; $i++) {
            $line_color = imagecolorallocate($image_for_check, wp_rand(0, 255), wp_rand(0, 255), wp_rand(0, 255));
            imageline(
                $image_for_check,
                wp_rand(0, imagesx($image_for_check)),
                wp_rand(0, imagesy($image_for_check)),
                wp_rand(0, imagesx($image_for_check)),
                wp_rand(0, imagesy($image_for_check)),
                $line_color
            );
        }

        imagefilter($image_for_check, IMG_FILTER_GAUSSIAN_BLUR);
        imagefilter($image_for_check, IMG_FILTER_MEAN_REMOVAL);
        ob_start();
        imagepng($image_for_check);
        $image_data = ob_get_contents();
        if (PHP_VERSION_ID < 80000) {
            imagedestroy($image_for_check);
        }
        ob_end_clean();

        $target_b64 = base64_encode($image_data);

        if ($inline_mode === 1) {
            $buttons_js = array();
            foreach ($colors as $btn_color) {
                $hash_for_id = md5($this->BBCS->time . $this->BBCS->settings->salt . $color_ids[$btn_color]);
                $img_path    = $img_dir . $color_ids[$btn_color] . '.jpg';
                // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
                $raw         = file_exists($img_path) ? file_get_contents($img_path) : '';
                $b64         = base64_encode($raw);
                $click_hash  = $btn_color . '|' . $colorhash;

                $buttons_js[] = '{id:"' . $hash_for_id . '",d:"' . $b64 . '",h:"' . $click_hash . '"}';
            }
            shuffle($buttons_js);

            // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
            return '
            (function() {
                var c = document.getElementById("content");
                c.innerHTML = "";
                var tImg = document.createElement("img");
                tImg.src = "data:image/png;base64,' . $target_b64 . '";
                c.appendChild(tImg);
                var p = document.createElement("p");
                p.textContent = "If you are human, click on the similar image";
                c.appendChild(p);
                var row = document.createElement("p");
                row.style.maxWidth = "500px";
                var items = [' . implode(',', $buttons_js) . '];
                for (var i = 0; i < items.length; i++) {
                    (function(item) {
                        var span = document.createElement("span");
                        span.id = item.id;
                        span.style.cursor = "pointer";
                        var img = document.createElement("img");
                        img.src = "data:image/jpeg;base64," + item.d;
                        span.appendChild(img);
                        span.addEventListener("click", function() {
                            ' . $fn . '("post", data, item.h);
                        });
                        row.appendChild(span);
                    })(items[i]);
                }
                c.appendChild(row);
            })();
            ';
        }

        /*
         * Legacy mode: button images loaded via separate AJAX requests
         * using fetchAndSetImage() for each of 8 images.
         */
        $buttons = [];
        $javaScriptFunction = [];
        
        foreach ($colors as $btnColor) {
            $hashForID = md5($this->BBCS->time . $this->BBCS->settings->salt . $color_ids[$btnColor]);
            $buttons[] = '<span id=\"' . $hashForID . '\" style=\"cursor: pointer;\" onclick=\"' . $fn . '(\'post\', data, \'' . $btnColor . '|' . $colorhash . '\')\">' . '</span> ';
            $javaScriptFunction[] = 'fetchAndSetImage("' . $color_ids[$btnColor] . '", "' . $hashForID . '");';
        }
        
        shuffle($buttons);
        shuffle($javaScriptFunction);
        $buttons = '<p style=\"max-width: 500px;\">' . implode('', $buttons) . '</p>';

        $output = '';
        // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
        $output .= 'document.getElementById("content").innerHTML = "<img src=\"data:image/png;base64,' . $target_b64 . '\" /><p>If you are human, click on the similar image</p>' . $buttons . '";';
        
        $output .= 'function fetchAndSetImage(param, imageId) {
        var url = \'' . admin_url('admin-ajax.php') . '\';
        var formData = new FormData();
        formData.append(\'action\', \'bbcs_botblocker_check\');
        formData.append(\'nonce\', \'' . wp_create_nonce('botblocker_nonce') . '\');
        formData.append(\'img\', param);
        formData.append(\'time\', "' . $this->BBCS->time . '");
        formData.append(\'' . $this->BBCS->select_request_mode . '\', \'img\');

        var requestOptions = {
            method: \'POST\',
            body: formData
        };

        fetch(url, requestOptions)
            .then(function(response) {
                if (!response.ok) { throw new Error("HTTP " + response.status); }
                return response.blob();
            })
            .then(function(blob) {
                var imageUrl = URL.createObjectURL(blob);
                var img = document.createElement(\'img\'); 
                img.src = imageUrl; 
                var span = document.getElementById(imageId);
                if (span) { span.appendChild(img); }
            })
            .catch(function(error) { console.error(\'Retrieve image error:\', error); });
        }
        ' . implode("\n", $javaScriptFunction);
        
        return $output;
    }
    
    /**
     * Mode 3: ReCAPTCHA v2 + button "I'm not a robot"
     * 
     * @return string JavaScript code
     */
    private function renderRecaptchaWithButton() {
        $nonce = $this->createChallenge('confirm', 3);
        $hash0 = $this->answerHash($nonce, 'confirm');
        $btnClass = 's'.md5('botblocker-btn-success'.$this->BBCS->time);

        return '
        var script = document.createElement("script");
        script.src = "https://www.google.com/recaptcha/api.js";
        document.body.appendChild(script);
        script.onload = function() {
            document.getElementById("content").innerHTML = "<div style=\"max-width: 302px; text-align: center;margin: 0 auto;\"><p>'.'Confirm that you are human:'.'</p><p class=\"g-recaptcha\" style=\"display: inline-block;\" data-sitekey=\"'.$this->BBCS->settings->recaptcha_key2.'\" data-callback=\"onRecaptchaSuccess\">'.'Loading...'.'</p></div>";
        }

        window.onRecaptchaSuccess = function(token) {
            data += "&g-recaptcha-response=" + token;
            document.getElementById("content").innerHTML = clean_and_decode_base64_to_utf8("'.base64_encode('<div style="max-width: 302px; text-align: center;margin: 0 auto;"><div style="cursor: pointer;" class="'.$btnClass.'" onclick="'.$this->botblocker_check_function_name.'(\'post\', data, \''.$hash0.'\')">Go to website</div></div>').'");
        }
        ';
    }
    
    /**
     * Mode 4: ReCAPTCHA v2 without buttons
     * 
     * @return string JavaScript code
     */
    private function renderRecaptchaWithoutButton() {
        $nonce = $this->createChallenge('confirm', 4);
        $hash0 = $this->answerHash($nonce, 'confirm');

        return '
        var script = document.createElement("script");
        script.src = "https://www.google.com/recaptcha/api.js";
        document.body.appendChild(script);
        script.onload = function() {
            document.getElementById("content").innerHTML = "<div style=\"max-width: 302px; text-align: center;margin: 0 auto;\"><p>'.'Confirm that you are human:'.'</p><p class=\"g-recaptcha\" style=\"display: inline-block;\" data-sitekey=\"'.$this->BBCS->settings->recaptcha_key2.'\" data-callback=\"onRecaptchaSuccess\">'.'Loading...'.'</p></div>";
        }

        window.onRecaptchaSuccess = function(token) {
            data += "&g-recaptcha-response=" + token;
            document.getElementById("content").innerHTML = "'.'Loading...'.'";
            '.$this->botblocker_check_function_name.'(\'post\', data, \''.$hash0.'\');
        }
        ';
    }

    /**
     * Mode 5: Moving shapes captcha
     * User must click on the specified shape
     * 
     * @return string JavaScript code
     */
    private function renderMovingShapesButton() {
  
        $shapes = ['circle', 'square', 'triangle', 'star', 'hexagon'];
        $colors = ['red', 'blue', 'green', 'purple', 'orange'];
        
        shuffle($shapes);
        shuffle($colors);
  
        $correctShape = $shapes[0];
        $correctColor = $colors[0];

        $nonce = $this->createChallenge($correctShape . '_' . $correctColor, 5);

        $shapesData = [];
        $usedCombinations = [];

        $shapesData[] = [
            'type' => $correctShape,
            'color' => $correctColor,
            'hash' => $this->answerHash($nonce, $correctShape . '_' . $correctColor)
        ];
        $usedCombinations[] = "{$correctShape}_{$correctColor}";

        $maxRetries = 50;
        $retries = 0;
        while (count($shapesData) < 5 && $retries < $maxRetries) {
            $retries++;
            $randomShape = $shapes[array_rand($shapes)];
            $randomColor = $colors[array_rand($colors)];
            
            $combination = "{$randomShape}_{$randomColor}";
            if (in_array($combination, $usedCombinations)) {
                continue; 
            }

            $shapesData[] = [
                'type' => $randomShape,
                'color' => $randomColor,
                'hash' => $this->answerHash($nonce, $randomShape . '_' . $randomColor)
            ];
            
            $usedCombinations[] = $combination;
        }

        shuffle($shapesData);

        $shapeLabels = [
            'circle' => 'Circle',
            'square' => 'Square',
            'triangle' => 'Triangle',
            'star' => 'Star',
            'hexagon' => 'Hexagon',
        ];

        $colorLabels = [
            'red' => 'Red',
            'blue' => 'Blue',
            'green' => 'Green',
            'purple' => 'Purple',
            'orange' => 'Orange',
        ];

        $findShapeText = 'Find the shape:' . ' ';
        $shapeText = $shapeLabels[$correctShape] . ', ';
        $withColorText = 'with color:' . ' ';
        $colorText = $colorLabels[$correctColor];
        
        $instruction = "{$findShapeText} {$shapeText} {$withColorText} {$colorText}";

        $shapesDataJSON = [];
        foreach ($shapesData as $shape) {
            $shapesDataJSON[] = "{
                type: \"{$shape['type']}\", 
                color: \"{$shape['color']}\", 
                x: Math.random() * 250 + 25, 
                y: Math.random() * 150 + 25, 
                size: 25, 
                speedX: Math.random() * 2 - 1, 
                speedY: Math.random() * 2 - 1, 
                hash: \"{$shape['hash']}\"
            }";
        }
        
        $shapesDataString = implode(",\n                ", $shapesDataJSON);
        
        return '
        document.getElementById("content").innerHTML = "<div style=\"text-align:center;\"><p>' . $instruction . '</p><canvas id=\"captchaCanvas\" width=\"300\" height=\"200\" style=\"border:1px solid #ddd;\"></canvas></div>";

        (function() {
            const canvas = document.getElementById("captchaCanvas");
            const ctx = canvas.getContext("2d");
            const shapes = [
                ' . $shapesDataString . '
            ];
            
            function drawShapes() {
                ctx.clearRect(0, 0, canvas.width, canvas.height);
                
                shapes.forEach(shape => {
                    ctx.fillStyle = shape.color;
                    ctx.strokeStyle = "#000";
                    ctx.lineWidth = 1;

                    shape.x += shape.speedX;
                    shape.y += shape.speedY;

                    if (shape.x < shape.size || shape.x > canvas.width - shape.size) {
                        shape.speedX = -shape.speedX;
                    }
                    if (shape.y < shape.size || shape.y > canvas.height - shape.size) {
                        shape.speedY = -shape.speedY;
                    }

                    switch(shape.type) {
                        case "circle":
                            ctx.beginPath();
                            ctx.arc(shape.x, shape.y, shape.size, 0, Math.PI * 2);
                            ctx.fill();
                            ctx.stroke();
                            break;
                        case "square":
                            ctx.fillRect(shape.x - shape.size, shape.y - shape.size, shape.size * 2, shape.size * 2);
                            ctx.strokeRect(shape.x - shape.size, shape.y - shape.size, shape.size * 2, shape.size * 2);
                            break;
                        case "triangle":
                            ctx.beginPath();
                            ctx.moveTo(shape.x, shape.y - shape.size);
                            ctx.lineTo(shape.x + shape.size, shape.y + shape.size);
                            ctx.lineTo(shape.x - shape.size, shape.y + shape.size);
                            ctx.closePath();
                            ctx.fill();
                            ctx.stroke();
                            break;
                        case "star":
                            drawStar(ctx, shape.x, shape.y, 5, shape.size, shape.size/2);
                            break;
                        case "hexagon":
                            drawHexagon(ctx, shape.x, shape.y, shape.size);
                            break;
                    }
                });
                
                requestAnimationFrame(drawShapes);
            }
            
            function drawStar(ctx, cx, cy, spikes, outerRadius, innerRadius) {
                let rot = Math.PI / 2 * 3;
                let x = cx;
                let y = cy;
                let step = Math.PI / spikes;

                ctx.beginPath();
                ctx.moveTo(cx, cy - outerRadius);
                
                for (let i = 0; i < spikes; i++) {
                    x = cx + Math.cos(rot) * outerRadius;
                    y = cy + Math.sin(rot) * outerRadius;
                    ctx.lineTo(x, y);
                    rot += step;

                    x = cx + Math.cos(rot) * innerRadius;
                    y = cy + Math.sin(rot) * innerRadius;
                    ctx.lineTo(x, y);
                    rot += step;
                }
                
                ctx.lineTo(cx, cy - outerRadius);
                ctx.closePath();
                ctx.fill();
                ctx.stroke();
            }
            
            function drawHexagon(ctx, x, y, size) {
                ctx.beginPath();
                for (let i = 0; i < 6; i++) {
                    const angle = 2 * Math.PI / 6 * i;
                    const hx = x + size * Math.cos(angle);
                    const hy = y + size * Math.sin(angle);
                    if (i === 0) ctx.moveTo(hx, hy);
                    else ctx.lineTo(hx, hy);
                }
                ctx.closePath();
                ctx.fill();
                ctx.stroke();
            }

            canvas.addEventListener("click", function(e) {
                const rect = canvas.getBoundingClientRect();
                const x = e.clientX - rect.left;
                const y = e.clientY - rect.top;
                
                shapes.forEach(shape => {
                    if (isPointInShape(x, y, shape)) {
                        ' . $this->botblocker_check_function_name . '(\'post\', data, shape.hash);
                    }
                });
            });
            
            function isPointInShape(x, y, shape) {
                switch(shape.type) {
                    case "circle":
                        return Math.sqrt((x - shape.x) * (x - shape.x) + (y - shape.y) * (y - shape.y)) <= shape.size;
                    case "square":
                        return x >= shape.x - shape.size && x <= shape.x + shape.size && 
                               y >= shape.y - shape.size && y <= shape.y + shape.size;
                    case "triangle": {
                        const p1 = {x: shape.x, y: shape.y - shape.size};
                        const p2 = {x: shape.x + shape.size, y: shape.y + shape.size};
                        const p3 = {x: shape.x - shape.size, y: shape.y + shape.size};
                        return isPointInTriangle(x, y, p1, p2, p3);
                    }
                    case "star":
                    case "hexagon":
                        return Math.sqrt((x - shape.x) * (x - shape.x) + (y - shape.y) * (y - shape.y)) <= shape.size * 1.2;
                }
                return false;
            }
            
            function isPointInTriangle(px, py, p1, p2, p3) {
                const area = 0.5 * Math.abs(
                    (p1.x * (p2.y - p3.y) + p2.x * (p3.y - p1.y) + p3.x * (p1.y - p2.y))
                );
                
                const a = 0.5 * Math.abs(
                    (p1.x * (p2.y - py) + p2.x * (py - p1.y) + px * (p1.y - p2.y))
                );
                const b = 0.5 * Math.abs(
                    (px * (p2.y - p3.y) + p2.x * (p3.y - py) + p3.x * (py - p2.y))
                );
                const c = 0.5 * Math.abs(
                    (p1.x * (py - p3.y) + px * (p3.y - p1.y) + p3.x * (p1.y - py))
                );
                
                return Math.abs(area - (a + b + c)) < 0.01;
            }

            requestAnimationFrame(drawShapes);
        })();
        ';
    }

    /**
     * Mode 6: Animated math expression
     * User must solve the math problem that's animated to prevent OCR
     * 
     * @return string JavaScript code
     */
    private function renderAnimatedMathExpression() {

        $num1 = wp_rand(1, 20);
        $num2 = wp_rand(1, 10);
        $operations = ['+', '-']; 
        $operationIndex = wp_rand(0, 1);
        $operation = $operations[$operationIndex];

        switch ($operation) {
            case '+': $result = $num1 + $num2; break;
            case '-': $result = $num1 - $num2; break;
        }

        $nonce = $this->createChallenge((string)$result, 6);

        $wrongAnswers = [];
        $maxRetries = 50;
        $retries = 0;
        while (count($wrongAnswers) < 3 && $retries < $maxRetries) {
            $retries++;
            $offset = wp_rand(1, 5) * (wp_rand(0, 1) ? 1 : -1);
            $candidate = $result + $offset;
            if ($candidate > 0 && $candidate != $result && !in_array($candidate, $wrongAnswers)) {
                $wrongAnswers[] = $candidate;
            }
        }
        // Fallback: guarantee exactly 3 wrong answers
        $fallback = $result + 6;
        while (count($wrongAnswers) < 3) {
            if ($fallback > 0 && $fallback != $result && !in_array($fallback, $wrongAnswers)) {
                $wrongAnswers[] = $fallback;
            }
            $fallback++;
        }

        $allAnswers = array_merge([$result], $wrongAnswers);
        shuffle($allAnswers);

        $answerButtons = [];
        
        foreach ($allAnswers as $answer) {
            $answerButtons[] = "{
                    value: {$answer},
                    hash: \"".$this->answerHash($nonce, (string)$answer)."\"
                }";
        }
        
        $expression = "{$num1} {$operation} {$num2} = ?";
        $answersJSON = implode(",\n                ", $answerButtons);

        return '
        document.getElementById("content").innerHTML = "<div style=\"text-align:center;\"><p>' . "Solve the following:" . '</p><canvas id=\"mathCanvas\" width=\"300\" height=\"80\" style=\"border:1px solid #ddd;\"></canvas><div id=\"answerOptions\" style=\"margin-top:15px;\"></div></div>";

        (function() {
            const canvas = document.getElementById("mathCanvas");
            const ctx = canvas.getContext("2d");
            const expression = "' . $expression . '";
            const answerDiv = document.getElementById("answerOptions");

            const answers = [
                ' . $answersJSON . '
            ];

            const chars = [];
            for (let i = 0; i < expression.length; i++) {
                chars.push({
                    char: expression[i],
                    x: 50 + i * 20,
                    y: 40,
                    baseX: 50 + i * 20,
                    baseY: 40,
                    color: getRandomColor(),
                    amplitude: Math.random() * 5 + 2,
                    speed: Math.random() * 0.05 + 0.01,
                    phase: Math.random() * Math.PI * 2,
                    fontSize: Math.floor(Math.random() * 5) + 25
                });
            }
            
            function getRandomColor() {
                const colors = ["#D00", "#0D0", "#00D", "#DD0", "#D0D", "#0DD"];
                return colors[Math.floor(Math.random() * colors.length)];
            }

            answers.forEach(answer => {
                const button = document.createElement("button");
                button.textContent = answer.value;
                button.style.margin = "5px 10px";
                button.style.padding = "8px 15px";
                button.style.fontSize = "16px";
                button.style.cursor = "pointer";
                button.style.backgroundColor = "#f0f5f7";
                button.style.border = "1px solid #ddd";
                button.style.borderRadius = "4px";
                
                button.addEventListener("click", function() {
                    ' . $this->botblocker_check_function_name . '(\'post\', data, answer.hash);
                });
                
                answerDiv.appendChild(button);
            });

            function animate() {
                ctx.clearRect(0, 0, canvas.width, canvas.height);

                addNoise();

                for (let i = 0; i < chars.length; i++) {
                    const char = chars[i];

                    char.y = char.baseY + Math.sin(Date.now() * char.speed + char.phase) * char.amplitude;

                    if (Math.random() < 0.02) {
                        char.color = getRandomColor();
                    }

                    ctx.font = `bold ${char.fontSize}px Arial`;
                    ctx.fillStyle = char.color;
                    ctx.fillText(char.char, char.x, char.y);

                    ctx.strokeStyle = "#000";
                    ctx.lineWidth = 0.5;
                    ctx.strokeText(char.char, char.x, char.y);
                }
                
                requestAnimationFrame(animate);
            }
            
            function addNoise() {
                ctx.fillStyle = "#eee";
                for (let i = 0; i < 50; i++) {
                    const x = Math.random() * canvas.width;
                    const y = Math.random() * canvas.height;
                    ctx.fillRect(x, y, 2, 2);
                }

                ctx.strokeStyle = "#ddd";
                ctx.lineWidth = 0.5;
                for (let i = 0; i < 5; i++) {
                    ctx.beginPath();
                    ctx.moveTo(Math.random() * canvas.width, Math.random() * canvas.height);
                    ctx.bezierCurveTo(
                        Math.random() * canvas.width, Math.random() * canvas.height,
                        Math.random() * canvas.width, Math.random() * canvas.height,
                        Math.random() * canvas.width, Math.random() * canvas.height
                    );
                    ctx.stroke();
                }

                if (Math.random() < 0.1) {
                    ctx.filter = `blur(${Math.random() * 0.5 + 0.1}px)`;
                    setTimeout(() => { ctx.filter = "none"; }, 200);
                }
            }

            animate();
        })();
        ';
    }

    private function renderHoldButton() {
        $duration   = wp_rand( 2500, 3500 );
        $zone_width = wp_rand( 12, 18 );
        $zone_start = wp_rand( 40, 85 - $zone_width );
        $zone_end   = $zone_start + $zone_width;

        $nonce        = $this->createChallenge( 'hold_confirm', 7 );
        $correct_hash = $this->answerHash( $nonce, 'hold_confirm' );
        $fn           = $this->botblocker_check_function_name;

        return '
        document.getElementById("content").innerHTML = "";

        (function() {
            var duration = ' . (int) $duration . ';
            var zoneStart = ' . (int) $zone_start . ';
            var zoneEnd = ' . (int) $zone_end . ';
            var correctHash = "' . $correct_hash . '";
            var maxAttempts = 3;
            var attempts = 0;
            var holding = false;
            var startTime = 0;
            var animFrame = null;
            var submitted = false;

            var container = document.createElement("div");
            container.style.cssText = "text-align:center;max-width:340px;margin:0 auto;user-select:none;-webkit-user-select:none;";

            var instruction = document.createElement("p");
            instruction.textContent = "Hold the button and release in the green zone";
            instruction.style.cssText = "margin-bottom:20px;font-size:14px;color:#555;";
            container.appendChild(instruction);

            var track = document.createElement("div");
            track.style.cssText = "position:relative;width:100%;height:60px;background:#e8e8e8;border-radius:8px;overflow:hidden;cursor:pointer;border:2px solid #ccc;touch-action:none;-webkit-touch-callout:none;";

            var zone = document.createElement("div");
            zone.style.cssText = "position:absolute;top:0;bottom:0;background:rgba(76,175,80,0.3);border-left:2px dashed #4CAF50;border-right:2px dashed #4CAF50;z-index:1;left:" + zoneStart + "%;width:" + (zoneEnd - zoneStart) + "%;";
            track.appendChild(zone);

            var fill = document.createElement("div");
            fill.style.cssText = "position:absolute;top:0;left:0;bottom:0;width:0%;background:#7785ef;z-index:2;";
            track.appendChild(fill);

            var btnText = document.createElement("div");
            btnText.style.cssText = "position:absolute;top:0;left:0;right:0;bottom:0;display:flex;align-items:center;justify-content:center;font-size:18px;font-weight:700;color:#fff;z-index:3;text-shadow:0 1px 2px rgba(0,0,0,0.3);pointer-events:none;";
            btnText.textContent = "HOLD";
            track.appendChild(btnText);

            container.appendChild(track);

            var status = document.createElement("p");
            status.style.cssText = "margin-top:15px;font-size:13px;color:#888;min-height:20px;";
            status.textContent = "";
            container.appendChild(status);

            var attemptInfo = document.createElement("p");
            attemptInfo.style.cssText = "margin-top:5px;font-size:12px;color:#aaa;";
            attemptInfo.textContent = "";
            container.appendChild(attemptInfo);

            document.getElementById("content").appendChild(container);

            function updateFill() {
                if (!holding) return;
                var elapsed = Date.now() - startTime;
                var progress = Math.min((elapsed / duration) * 100, 100);
                fill.style.width = progress + "%";

                if (progress < zoneStart) {
                    fill.style.background = "#7785ef";
                } else if (progress <= zoneEnd) {
                    fill.style.background = "#4CAF50";
                } else {
                    fill.style.background = "#f44336";
                }

                if (progress >= 100) {
                    handleRelease();
                    return;
                }

                animFrame = requestAnimationFrame(updateFill);
            }

            function handlePress(e) {
                if (submitted || holding) return;
                e.preventDefault();
                holding = true;
                startTime = Date.now();
                fill.style.width = "0%";
                fill.style.background = "#7785ef";
                status.textContent = "";
                status.style.color = "#888";
                animFrame = requestAnimationFrame(updateFill);
            }

            function handleRelease(e) {
                if (submitted || !holding) return;
                if (e && e.preventDefault) e.preventDefault();
                holding = false;
                if (animFrame) {
                    cancelAnimationFrame(animFrame);
                    animFrame = null;
                }

                var elapsed = Date.now() - startTime;
                var progress = Math.min((elapsed / duration) * 100, 100);
                fill.style.width = progress + "%";

                attempts++;

                if (progress >= zoneStart && progress <= zoneEnd) {
                    submitted = true;
                    fill.style.background = "#4CAF50";
                    status.textContent = "Verifying...";
                    status.style.color = "#4CAF50";
                    track.style.cursor = "default";
                    setTimeout(function() {
                        ' . $fn . '("post", data, correctHash);
                    }, 300);
                } else if (attempts >= maxAttempts) {
                    submitted = true;
                    fill.style.background = "#f44336";
                    status.textContent = "Verification failed.";
                    status.style.color = "#f44336";
                    track.style.cursor = "default";
                    setTimeout(function() {
                        ' . $fn . '("post", data, "wrong");
                    }, 500);
                } else {
                    if (progress < zoneStart) {
                        status.textContent = "Too early! Try again.";
                    } else {
                        status.textContent = "Too late! Try again.";
                    }
                    status.style.color = "#f44336";
                    attemptInfo.textContent = attempts + "/" + maxAttempts;
                    setTimeout(function() {
                        fill.style.width = "0%";
                        fill.style.background = "#7785ef";
                    }, 800);
                }
            }

            function handleCancel(e) {
                if (holding) {
                    handleRelease(e);
                }
            }

            if (window.PointerEvent) {
                track.addEventListener("pointerdown", handlePress);
                track.addEventListener("pointerup", handleRelease);
                track.addEventListener("pointercancel", handleCancel);
                track.addEventListener("pointerleave", handleCancel);
            } else {
                track.addEventListener("mousedown", handlePress);
                track.addEventListener("mouseup", handleRelease);
                track.addEventListener("mouseleave", handleCancel);
                track.addEventListener("touchstart", handlePress, { passive: false });
                track.addEventListener("touchend", handleRelease, { passive: false });
                track.addEventListener("touchcancel", handleCancel, { passive: false });
            }

            track.addEventListener("contextmenu", function(e) { e.preventDefault(); });
        })();
        ';
    }
}
