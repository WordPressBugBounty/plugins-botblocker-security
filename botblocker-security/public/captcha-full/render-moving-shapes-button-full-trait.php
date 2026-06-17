<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! defined( 'WPINC' ) || ! defined( 'BOTBLOCKER' ) ) {
	exit;
}

trait BotBlockerCaptchaFullRenderMovingShapesButtonTrait {

	private function renderMovingShapesButton(): string {

		$shapes = array( 'circle', 'square', 'triangle', 'star', 'hexagon' );
		$colors = array( 'red', 'blue', 'green', 'purple', 'orange' );

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

		$shapeLabels = array(
			'circle'   => __( 'Circle', 'botblocker-security' ),
			'square'   => __( 'Square', 'botblocker-security' ),
			'triangle' => __( 'Triangle', 'botblocker-security' ),
			'star'     => __( 'Star', 'botblocker-security' ),
			'hexagon'  => __( 'Hexagon', 'botblocker-security' ),
		);

		$colorLabels = array(
			'red'    => __( 'Red', 'botblocker-security' ),
			'blue'   => __( 'Blue', 'botblocker-security' ),
			'green'  => __( 'Green', 'botblocker-security' ),
			'purple' => __( 'Purple', 'botblocker-security' ),
			'orange' => __( 'Orange', 'botblocker-security' ),
		);

		$findShapeText = __( 'Find the shape:', 'botblocker-security' ) . ' ';
		$shapeText     = $shapeLabels[ $correctShape ] . ', ';
		$withColorText = __( 'with color:', 'botblocker-security' ) . ' ';
		$colorText     = $colorLabels[ $correctColor ];

		$instruction = esc_html( "{$findShapeText}{$shapeText}{$withColorText}{$colorText}" );

		$shapesDataJSON = array();
		foreach ( $shapesData as $shape ) {
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

		$shapesDataString = implode( ",\n                ", $shapesDataJSON );

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
}
