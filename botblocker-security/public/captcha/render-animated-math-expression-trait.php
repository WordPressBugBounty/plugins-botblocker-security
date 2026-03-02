<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

trait BBCS_RenderAnimatedMathExpressionTrait {

    private function getAnimatedMathExpressionData() {

        $num1 = wp_rand(1, 20);
        $num2 = wp_rand(1, 10);
        $operations = ['+', '-']; 
        $operationIndex = wp_rand(0, 1);
        $operation = $operations[$operationIndex];

        switch ($operation) {
            case '+': $result = $num1 + $num2; break;
            case '-': $result = $num1 - $num2; break;
        }

        $nonce = $this->createChallenge((string) $result, 6);

        $wrongAnswers = [];
        for ($i = 0; $i < 3; $i++) {
            $offset = wp_rand(1, 5) * (wp_rand(0, 1) ? 1 : -1);
            $wrongAnswer = $result + $offset;
            if ($wrongAnswer > 0 && $wrongAnswer != $result) {
                $wrongAnswers[] = $wrongAnswer;
            } else {
                $wrongAnswer = $result + wp_rand(1, 5); 
                if ($wrongAnswer == $result) $wrongAnswer++;
                $wrongAnswers[] = $wrongAnswer;
            }
        }

        $allAnswers = array_merge([$result], $wrongAnswers);
        shuffle($allAnswers);

        $answerButtons = [];
        
        foreach ($allAnswers as $answer) {
            $answerButtons[] = [
                'value' => $answer,
                'hash' => $this->answerHash($nonce, (string) $answer)
            ];
        }
        
        $expressionChars = str_split("{$num1} {$operation} {$num2} = ?");
        $expressionData = [];
        foreach ($expressionChars as $ch) {
            $expressionData[] = [
                'c' => $ch,
                'o' => wp_rand(-3, 3),
            ];
        }

        return [
            'mode' => 6,
            'params' => [
                'instructionText' => __("Solve the following:", 'botblocker-security'),
                'expressionData' => $expressionData,
                'answers' => $answerButtons
            ]
        ];
    }
}
