<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

trait BBCS_RenderMovingShapesButtonTrait {

    private function getMovingShapesButtonData() {
        $shapes = ['circle', 'square', 'triangle', 'star', 'hexagon'];
        $colors = ['red', 'blue', 'green', 'purple', 'orange'];

        $shape_translations = [
            'circle' => __('Circle', 'botblocker-security'),
            'square' => __('Square', 'botblocker-security'),
            'triangle' => __('Triangle', 'botblocker-security'),
            'star' => __('Star', 'botblocker-security'),
            'hexagon' => __('Hexagon', 'botblocker-security'),
        ];
        
        $color_translations = [
            'red' => __('Red', 'botblocker-security'),
            'blue' => __('Blue', 'botblocker-security'),
            'green' => __('Green', 'botblocker-security'),
            'purple' => __('Purple', 'botblocker-security'),
            'orange' => __('Orange', 'botblocker-security'),
        ];
        
        shuffle($shapes);
        shuffle($colors);
  
        $correctShape = $shapes[0];
        $correctColor = $colors[0];

        $correctHash = hash('sha256', $this->BBCS->settings->salt . $correctShape . $this->BBCS->time . $this->BBCS->settings->cloud_api_pass . $this->BBCS->ip);

        $shapesData = [];
        $usedCombinations = [];

        $shapesData[] = [
            'type' => $correctShape,
            'color' => $correctColor,
            'isCorrect' => true,
            'hash' => "{$correctShape}|{$correctHash}" 
        ];
        $usedCombinations[] = "{$correctShape}_{$correctColor}";

        for ($i = 0; $i < 4; $i++) {
            $randomShape = $shapes[array_rand($shapes)];
            $randomColor = $colors[array_rand($colors)];
            
            $combination = "{$randomShape}_{$randomColor}";
            if (in_array($combination, $usedCombinations)) {
                continue; 
            }

            $shapesData[] = [
                'type' => $randomShape,
                'color' => $randomColor,
                'isCorrect' => false,
                'hash' => "wrong|" . md5($correctHash)
            ];
            
            $usedCombinations[] = $combination;
        }

        shuffle($shapesData);

        $findShapeText = __("Find the shape:", 'botblocker-security') . ' ';
        $shapeText = $shape_translations[$correctShape] . ', ';
        $withColorText = __("with color:", 'botblocker-security') . ' ';
        $colorText = $color_translations[$correctColor];
        
        $instruction = "{$findShapeText} {$shapeText} {$withColorText} {$colorText}";

        return [
            'mode' => 5,
            'params' => [
                'instruction' => $instruction,
                'shapesData' => $shapesData
            ]
        ];
    }
}
