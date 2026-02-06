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

        $resultHash = hash('sha256', $this->BBCS->settings->salt . $result . $this->BBCS->time . $this->BBCS->settings->cloud_api_pass . $this->BBCS->ip);

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
            if ($answer === $result) {
                $answerButtons[] = [
                    'value' => $answer,
                    'hash' => "{$answer}|math|{$resultHash}"
                ];
            } else {
                $answerButtons[] = [
                    'value' => $answer,
                    'hash' => "{$answer}|wrong|".md5($resultHash)
                ];
            }
        }
        
        $expression = "{$num1} {$operation} {$num2} = ?";

        return [
            'mode' => 6,
            'params' => [
                'instructionText' => __("Solve the following:", 'botblocker-security'),
                'expression' => $expression,
                'answers' => $answerButtons
            ]
        ];
    }
}
