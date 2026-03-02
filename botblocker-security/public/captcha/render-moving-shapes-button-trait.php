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

        $nonce = $this->createChallenge($correctShape . '_' . $correctColor, 5);

        $shapesData = [];
        $usedCombinations = [];

        $shapesData[] = [
            'type' => $correctShape,
            'color' => $correctColor,
            'hash' => $this->answerHash($nonce, $correctShape . '_' . $correctColor)
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
                'hash' => $this->answerHash($nonce, $randomShape . '_' . $randomColor)
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
