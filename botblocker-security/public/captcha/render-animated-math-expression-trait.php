<?php
declare(strict_types=1);


if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

trait BBCS_RenderAnimatedMathExpressionTrait {

	private function getAnimatedMathExpressionData() {

		$num1           = wp_rand( 1, 20 );
		$num2           = wp_rand( 1, 10 );
		$operations     = array( '+', '-' );
		$operationIndex = wp_rand( 0, 1 );
		$operation      = $operations[ $operationIndex ];

		if ( $operation === '-' && $num2 > $num1 ) {
			$temp = $num1;
			$num1 = $num2;
			$num2 = $temp;
		}

		switch ( $operation ) {
			case '+':
				$result = $num1 + $num2;
				break;
			case '-':
				$result = $num1 - $num2;
				break;
		}

		$nonce = $this->createChallenge( (string) $result, 6 );

		$wrongAnswers = array();
		$maxRetries   = 50;
		$retries      = 0;
		while ( count( $wrongAnswers ) < 3 && $retries < $maxRetries ) {
			++$retries;
			$offset    = wp_rand( 1, 5 ) * ( wp_rand( 0, 1 ) ? 1 : -1 );
			$candidate = $result + $offset;
			if ( $candidate > 0 && $candidate != $result && ! in_array( $candidate, $wrongAnswers ) ) {
				$wrongAnswers[] = $candidate;
			}
		}
		// Fallback: guarantee exactly 3 wrong answers
		$fallback = $result + 6;
		while ( count( $wrongAnswers ) < 3 ) {
			if ( $fallback > 0 && $fallback != $result && ! in_array( $fallback, $wrongAnswers ) ) {
				$wrongAnswers[] = $fallback;
			}
			++$fallback;
		}

		$allAnswers = array_merge( array( $result ), $wrongAnswers );
		shuffle( $allAnswers );

		$answerButtons = array();

		foreach ( $allAnswers as $answer ) {
			$answerButtons[] = array(
				'value' => $answer,
				'hash'  => $this->answerHash( $nonce, (string) $answer ),
			);
		}

		$expressionChars = str_split( "{$num1} {$operation} {$num2} = ?" );
		$expressionData  = array();
		foreach ( $expressionChars as $ch ) {
			$expressionData[] = array(
				'c' => $ch,
			);
		}

		return array(
			'mode'   => 6,
			'params' => array(
				'instructionText' => self::t( 'Solve the following:' ),
				'expressionData'  => $expressionData,
				'answers'         => $answerButtons,
			),
		);
	}
}
