<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! defined( 'WPINC' ) || ! defined( 'BOTBLOCKER' ) ) {
	exit;
}

trait BotBlockerCaptchaFullRenderAnimatedMathTrait {

	/**
	 * Mode 6: Animated math expression
	 * User must solve the math problem that's animated to prevent OCR
	 *
	 * @return string JavaScript code
	 */
	private function renderAnimatedMathExpression(): string {

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
			$answerButtons[] = "{
                    value: {$answer},
                    hash: \"" . $this->answerHash( $nonce, (string) $answer ) . '"
                }';
		}

		$expression  = "{$num1} {$operation} {$num2} = ?";
		$answersJSON = implode( ",\n                ", $answerButtons );

		return '
        document.getElementById("content").innerHTML = "<div style=\"text-align:center;\"><p>' . esc_js( __( 'Solve the following:', 'botblocker-security' ) ) . '</p><canvas id=\"mathCanvas\" width=\"300\" height=\"80\" style=\"border:1px solid #ddd;\"></canvas><div id=\"answerOptions\" style=\"margin-top:15px;\"></div></div>";

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
}
