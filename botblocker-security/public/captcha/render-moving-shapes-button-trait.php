<?php
declare(strict_types=1);


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

trait BBCS_RenderMovingShapesButtonTrait {

	private function getMovingShapesButtonData() {
		$shapes = array( 'circle', 'square', 'triangle', 'star', 'hexagon' );
		$colors = array( 'red', 'blue', 'green', 'purple', 'orange' );

		$shape_labels = array(
			'circle'   => self::t( 'Circle' ),
			'square'   => self::t( 'Square' ),
			'triangle' => self::t( 'Triangle' ),
			'star'     => self::t( 'Star' ),
			'hexagon'  => self::t( 'Hexagon' ),
		);

		$color_labels = array(
			'red'    => self::t( 'Red' ),
			'blue'   => self::t( 'Blue' ),
			'green'  => self::t( 'Green' ),
			'purple' => self::t( 'Purple' ),
			'orange' => self::t( 'Orange' ),
		);

		shuffle( $shapes );
		shuffle( $colors );

		$correctShape = $shapes[0];
		$correctColor = $colors[0];

		$nonce = $this->createChallenge( $correctShape . '_' . $correctColor, 5 );

		$shapesData       = array();
		$usedCombinations = array();

		$shapesData[]       = array(
			'type'  => $correctShape,
			'color' => $correctColor,
			'hash'  => $this->answerHash( $nonce, $correctShape . '_' . $correctColor ),
		);
		$usedCombinations[] = "{$correctShape}_{$correctColor}";

		$maxRetries = 50;
		$retries    = 0;
		while ( count( $shapesData ) < 5 && $retries < $maxRetries ) {
			++$retries;
			$randomShape = $shapes[ array_rand( $shapes ) ];
			$randomColor = $colors[ array_rand( $colors ) ];

			$combination = "{$randomShape}_{$randomColor}";
			if ( in_array( $combination, $usedCombinations ) ) {
				continue;
			}

			$shapesData[] = array(
				'type'  => $randomShape,
				'color' => $randomColor,
				'hash'  => $this->answerHash( $nonce, $randomShape . '_' . $randomColor ),
			);

			$usedCombinations[] = $combination;
		}

		shuffle( $shapesData );

		$findShapeText = self::t( 'Find the shape: ' );
		$shapeText     = $shape_labels[ $correctShape ] . ', ';
		$withColorText = self::t( 'with color: ' );
		$colorText     = $color_labels[ $correctColor ];

		$instruction = "{$findShapeText} {$shapeText} {$withColorText} {$colorText}";

		return array(
			'mode'   => 5,
			'params' => array(
				'instruction' => $instruction,
				'shapesData'  => $shapesData,
			),
		);
	}
}
