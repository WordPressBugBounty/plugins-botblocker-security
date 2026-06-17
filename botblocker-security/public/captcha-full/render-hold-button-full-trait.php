<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
if ( ! defined( 'WPINC' ) || ! defined( 'BOTBLOCKER' ) ) {
	exit;
}

trait BotBlockerCaptchaFullRenderHoldButtonTrait {

	private function renderHoldButton(): string {
		$duration   = wp_rand( 2500, 3500 );
		$zone_width = wp_rand( 12, 18 );
		$zone_start = wp_rand( 40, 85 - $zone_width );
		$zone_end   = $zone_start + $zone_width;

		$nonce        = $this->createChallenge( 'hold_confirm', 7 );
		$correct_hash = $this->answerHash( $nonce, 'hold_confirm' );
		$fn           = $this->botblocker_check_function_name;

		$i18n_instruction = esc_js( __( 'Hold the button and release in the green zone', 'botblocker-security' ) );
		$i18n_hold        = esc_js( __( 'HOLD', 'botblocker-security' ) );
		$i18n_verifying   = esc_js( __( 'Verifying...', 'botblocker-security' ) );
		$i18n_failed      = esc_js( __( 'Verification failed.', 'botblocker-security' ) );
		$i18n_too_early   = esc_js( __( 'Too early. Try again.', 'botblocker-security' ) );
		$i18n_too_late    = esc_js( __( 'Too late. Try again.', 'botblocker-security' ) );

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
            instruction.textContent = "' . $i18n_instruction . '";
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
            btnText.textContent = "' . $i18n_hold . '";
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
                    status.textContent = "' . $i18n_verifying . '";
                    status.style.color = "#4CAF50";
                    track.style.cursor = "default";
                    setTimeout(function() {
                        ' . $fn . '("post", data, correctHash);
                    }, 300);
                } else if (attempts >= maxAttempts) {
                    submitted = true;
                    fill.style.background = "#f44336";
                    status.textContent = "' . $i18n_failed . '";
                    status.style.color = "#f44336";
                    track.style.cursor = "default";
                    setTimeout(function() {
                        ' . $fn . '("post", data, "wrong");
                    }, 500);
                } else {
                    if (progress < zoneStart) {
                        status.textContent = "' . $i18n_too_early . '";
                    } else {
                        status.textContent = "' . $i18n_too_late . '";
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
