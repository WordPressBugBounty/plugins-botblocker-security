<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return static function ( Botblocker_ChainContextData $data ): void {
	if ( defined( 'BBCS_NEW_ADMIN_UI' ) && BBCS_NEW_ADMIN_UI ) {
		// ── New UI: vertical pipeline with JS-driven sequential animation ──
		$uid = 'bbcs-pipe-' . wp_rand( 1000, 9999 );
		?>
		<div class="bbcs-pipeline-wrap" id="<?php echo esc_attr( $uid ); ?>" data-block-at="<?php echo esc_attr( (string) $data->block_at ); ?>">

			<?php /* ── Entry node: "Incoming request" ── */ ?>
			<div class="bbcs-pipeline-entry">
				<div class="bbcs-pipeline-entry-track">
					<div class="bbcs-pipeline-entry-icon">
						<svg class="bbcs-ico bbcs-ico--xs" style="font-size:12px;"><use href="#bbcs-i-globe"></use></svg>
					</div>
					<div class="bbcs-pipeline-entry-connector"></div>
				</div>
				<span class="bbcs-pipeline-entry-label"><?php esc_html_e( 'Incoming request', 'botblocker-security' ); ?></span>
			</div>

			<?php /* ── Pipeline steps ── */ ?>
			<div class="bbcs-pipeline">
				<?php
				$steps = array(
					array(
						'active' => $data->early_init_active,
						'label'  => __( 'Early Init', 'botblocker-security' ),
						'desc'   => $data->early_desc,
					),
					array(
						'active' => $data->mu_active,
						'label'  => __( 'MU-plugin', 'botblocker-security' ),
						'desc'   => $data->mu_desc,
					),
					array(
						'active' => $data->botblocker_active,
						'label'  => __( 'BotBlocker', 'botblocker-security' ),
						'desc'   => $data->plugin_desc,
					),
				);

				foreach ( $steps as $i => $step ) :
					$is_last     = ( $i === count( $steps ) - 1 );
					$bubble_mod  = $step['active'] ? ' bbcs-pipeline-bubble--active' : '';
					$conn_mod    = $step['active'] ? ' bbcs-pipeline-connector--active' : '';
					$badge_mod   = $step['active'] ? ' bbcs-pipeline-badge--on' : ' bbcs-pipeline-badge--off';
					$badge_lbl   = $step['active'] ? __( 'Active', 'botblocker-security' ) : __( 'Inactive', 'botblocker-security' );
					$bypass      = ( $i === 1 && $data->early_init_active );
					$step_mod    = $bypass ? ' bbcs-pipeline-step--bypassed' : '';
					$badge_mod   = $bypass ? ' bbcs-pipeline-badge--off' : $badge_mod;
					$badge_lbl   = $bypass ? __( 'Not needed', 'botblocker-security' ) : $badge_lbl;
				?>
					<div class="bbcs-pipeline-step<?php echo esc_attr( $step_mod ); ?>" data-step="<?php echo esc_attr( (string) $i ); ?>">
						<div class="bbcs-pipeline-track">
							<div class="bbcs-pipeline-bubble<?php echo esc_attr( $bubble_mod ); ?>">
								<?php echo esc_html( (string) ( $i + 1 ) ); ?>
							</div>
							<?php if ( ! $is_last ) : ?>
								<div class="bbcs-pipeline-connector<?php echo esc_attr( $conn_mod ); ?>"></div>
							<?php endif; ?>
						</div>
						<div class="bbcs-pipeline-body">
							<div class="bbcs-pipeline-head">
								<span class="bbcs-pipeline-name"><?php echo esc_html( $step['label'] ); ?></span>
								<span class="bbcs-pipeline-badge<?php echo esc_attr( $badge_mod ); ?>"><?php echo esc_html( $badge_lbl ); ?></span>
							</div>
							<div class="bbcs-pipeline-desc"><?php echo esc_html( $step['desc'] ); ?>
								<?php if ( $i === 0 && ! $step['active'] && ! $data->early_available ) : ?>
									<br>
									<a href="<?php echo esc_url( $data->cloud_api_url ); ?>"><?php esc_html_e( 'Connect now!', 'botblocker-security' ); ?></a>
									<?php esc_html_e( 'or', 'botblocker-security' ); ?>
									<a href="<?php echo esc_url( $data->addons_url ); ?>"><?php esc_html_e( 'manage add-ons', 'botblocker-security' ); ?></a>
								<?php endif; ?>
							</div>
						</div>
					</div>
				<?php endforeach; ?>
			</div>

			<?php /* ── Animated packet dot + block icon overlay (positioned by JS) ── */ ?>
			<div class="bbcs-pipeline-dot bbcs-pipeline-dot--ok" id="<?php echo esc_attr( $uid . '-dot' ); ?>"></div>
			<div class="bbcs-pipeline-block-icon" id="<?php echo esc_attr( $uid . '-lbl' ); ?>" aria-hidden="true">
				<svg class="bbcs-ico" style="width:28px;height:28px;"><use href="#bbcs-i-ban"></use></svg>
			</div>
		</div>

		<div class="bbcs-guide-p bbcs-mt-3">
			<?php esc_html_e( 'Early Init and MU modes drop blacklisted IPs at the first gate - no WordPress load, no overhead. This saves CPU time by skipping heavier logic for junk traffic before it reaches the main shield.', 'botblocker-security' ); ?>
		</div>

		<script>
		(function () {
			'use strict';

			var uid     = <?php echo wp_json_encode( $uid ); ?>;
			var blockAt = <?php echo (int) $data->block_at; ?>;

			function bbcsPipeline(uid, blockAt) {
				var wrap   = document.getElementById(uid);
				if (!wrap) return;

				var dot    = document.getElementById(uid + '-dot');
				var lbl    = document.getElementById(uid + '-lbl');
				var steps  = wrap.querySelectorAll('.bbcs-pipeline-step');

				if (!dot || !lbl || !steps.length) return;

				/* ── measure: pixel Y positions of each bubble centre and connector bottom ── */
				function getY(el) {
					var r = el.getBoundingClientRect();
					var w = wrap.getBoundingClientRect();
					return (r.top + r.height / 2) - w.top;
				}

				function getConnBottom(step) {
					var conn = step.querySelector('.bbcs-pipeline-connector');
					if (!conn) return null;
					var r = conn.getBoundingClientRect();
					var w = wrap.getBoundingClientRect();
					return r.bottom - w.top;
				}

				/* ── helpers ── */
				function setDot(y, ok) {
					dot.style.top = y + 'px';
					dot.className = 'bbcs-pipeline-dot bbcs-pipeline-dot--' + (ok ? 'ok' : 'bad');
				}

				function hideDot(explode, cb) {
					if (explode) {
						dot.classList.add('bbcs-pipeline-dot--explode');
						setTimeout(function() {
							dot.classList.remove('bbcs-pipeline-dot--explode');
							dot.style.opacity = '0';
							cb && cb();
						}, 340);
					} else {
						dot.style.opacity = '0';
						setTimeout(cb || function(){}, 220);
					}
				}

				function flashBubble(step, bad) {
					var bub = step.querySelector('.bbcs-pipeline-bubble');
					if (!bub) return;
					var cls = bad ? 'bbcs-anim-hit-bad' : 'bbcs-anim-hit-ok';
					bub.classList.add(cls);
					setTimeout(function() { bub.classList.remove(cls); }, 500);
				}

				function showBlockedLabel(y) {
					lbl.style.top = y + 'px';
					lbl.classList.add('bbcs-anim-visible');
				}

				function hideBlockedLabel() {
					lbl.classList.remove('bbcs-anim-visible');
				}

				/* ── animation sequence ── */

				var TOTAL_CYCLE = 10000;
				var TRAVEL      = 340;
				var PAUSE_AT    = 320;

				function runEntry(yEntry, cb) {
					dot.style.transition = 'none';
					setDot(yEntry - 18, true);
					dot.style.opacity = '1';
					dot.getBoundingClientRect();
					dot.style.transition = '';
					setDot(yEntry, true);
					setTimeout(cb, TRAVEL + 80);
				}

				function runGreenPacket(done) {
					var entry = wrap.querySelector('.bbcs-pipeline-entry-icon');
					var yEntry = entry ? getY(entry) : 10;

					var skipMu = (blockAt === 0);
					var waypoints = [];
					steps.forEach(function(step) {
						var si = parseInt(step.dataset.step);
						if (skipMu && si === 1) return;
						var bub = step.querySelector('.bbcs-pipeline-bubble');
						if (bub) waypoints.push({ type: 'bubble', y: getY(bub), step: step });
						var bottom = getConnBottom(step);
						if (bottom !== null && !(skipMu && si === 0)) {
							waypoints.push({ type: 'conn', y: bottom });
						}
					});

					dot.style.transition = 'none';
					setDot(yEntry - 18, true);
					dot.style.opacity = '0';
					dot.getBoundingClientRect();
					dot.style.transition = '';

					var t = 0;

					setTimeout(function() {
						dot.style.opacity = '1';
						setDot(yEntry, true);
					}, t);
					t += TRAVEL + 100;

					waypoints.forEach(function(wp) {
						var isBubble = wp.type === 'bubble';
						setTimeout(function() {
							setDot(wp.y, true);
							if (isBubble) flashBubble(wp.step, false);
						}, t);
						t += TRAVEL + (isBubble ? PAUSE_AT : 80);
					});

					setTimeout(function() {
						hideDot(false, done);
					}, t);
				}

				function runRedPacket(done) {
					var entry = wrap.querySelector('.bbcs-pipeline-entry-icon');
					var yEntry = entry ? getY(entry) : 10;

					var skipSteps = (blockAt === 0) ? {1: true} : {};

					var waypoints = [];
					for (var si = 0; si <= blockAt; si++) {
						if (skipSteps[si]) continue;
						var step = steps[si];
						if (!step) continue;
						var bub = step.querySelector('.bbcs-pipeline-bubble');
						if (bub) waypoints.push({ type: 'bubble', y: getY(bub), step: step, isBlock: si === blockAt });
						if (si < blockAt) {
							var bottom = getConnBottom(step);
							if (bottom !== null) waypoints.push({ type: 'conn', y: bottom });
						}
					}

					if (!waypoints.length) { done && done(); return; }

					dot.style.transition = 'none';
					setDot(yEntry - 18, false);
					dot.style.opacity = '0';
					dot.getBoundingClientRect();
					dot.style.transition = '';

					var t = 0;

					setTimeout(function() {
						dot.style.opacity = '1';
						setDot(yEntry, false);
					}, t);
					t += TRAVEL + 100;

					waypoints.forEach(function(wp) {
						var isBubble = wp.type === 'bubble';
						var isBlock  = !!wp.isBlock;
						setTimeout(function() {
							setDot(wp.y, false);
							if (isBubble) {
								flashBubble(wp.step, isBlock);
								if (isBlock) {
									setTimeout(function() {
										showBlockedLabel(wp.y);
										hideDot(true, function() {
											setTimeout(function() {
												hideBlockedLabel();
												done && done();
											}, 900);
										});
									}, PAUSE_AT);
								}
							}
						}, t);
						t += TRAVEL + (isBubble ? PAUSE_AT : 80);
					});
				}

				/* ── main loop ── */
				function loop() {
					runGreenPacket(function() {
						setTimeout(function() {
							runRedPacket(function() {
								setTimeout(loop, 1400);
							});
						}, 600);
					});
				}

				setTimeout(loop, 800);
			}

			if (document.readyState === 'loading') {
				document.addEventListener('DOMContentLoaded', function() { bbcsPipeline(uid, blockAt); });
			} else {
				bbcsPipeline(uid, blockAt);
			}
		}());
		</script>
		<?php
	} else {
		// ── Legacy UI: chip row with tooltips ──────────────────────────────
		?>
		<div class="bbcs-chain">
			<span class="bbcs-chip<?php echo $data->early_init_active ? ' bbcs-chip--green' : ''; ?>" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php esc_attr_e( 'Loads black/white IP lists via wp-config before WordPress core starts.', 'botblocker-security' ); ?>" style="cursor: help;">
				<?php if ( $data->early_init_active ) : ?><i class="fa-solid fa-play bbcs-mr-1" style="margin-right: 4px;"></i><?php else : ?><i class="fa-solid fa-stop bbcs-mr-1" style="margin-right: 4px;"></i><?php endif; ?>
				<?php esc_html_e( 'Early Init', 'botblocker-security' ); ?>
				<i class="fa-solid fa-circle-info ms-1" style="margin-left: 4px; opacity: 0.7;"></i>
			</span>
			<svg class="bbcs-ico bbcs-ico--sm">
				<use href="#bbcs-i-arrowR"></use>
			</svg>
			<span class="bbcs-chip<?php echo $data->mu_active ? ' bbcs-chip--green' : ''; ?>" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php esc_attr_e( 'Loads black/white IP lists before regular plugins.', 'botblocker-security' ); ?>" style="cursor: help;">
				<?php if ( $data->mu_active ) : ?><i class="fa-solid fa-play bbcs-mr-1" style="margin-right: 4px;"></i><?php else : ?><i class="fa-solid fa-stop bbcs-mr-1" style="margin-right: 4px;"></i><?php endif; ?>
				<?php esc_html_e( 'MU-plugin', 'botblocker-security' ); ?>
				<i class="fa-solid fa-circle-info ms-1" style="margin-left: 4px; opacity: 0.7;"></i>
			</span>
			<svg class="bbcs-ico bbcs-ico--sm">
				<use href="#bbcs-i-arrowR"></use>
			</svg>
			<span class="bbcs-chip<?php echo $data->botblocker_active ? ' bbcs-chip--green' : ''; ?>" data-bs-toggle="tooltip" data-bs-placement="top" title="<?php esc_attr_e( 'Main protection layer and deep traffic analysis.', 'botblocker-security' ); ?>" style="cursor: help;">
				<?php if ( $data->botblocker_active ) : ?><i class="fa-solid fa-play bbcs-mr-1" style="margin-right: 4px;"></i><?php else : ?><i class="fa-solid fa-stop bbcs-mr-1" style="margin-right: 4px;"></i><?php endif; ?>
				<?php esc_html_e( 'BotBlocker', 'botblocker-security' ); ?>
				<i class="fa-solid fa-circle-info ms-1" style="margin-left: 4px; opacity: 0.7;"></i>
			</span>
		</div>
		<?php if ( $data->show_text ) : ?>
			<div class="bbcs-muted bbcs-mt-3 bbcs-fs-xs bbcs-lh-15">
				<?php
				if ( $data->botblocker_active ) {
					esc_html_e( 'Active', 'botblocker-security' );
				} else {
					esc_html_e( 'Protection paused', 'botblocker-security' );
				}
				echo ' &mdash; ';
				esc_html_e( 'Junk traffic is filtered as early as possible - only clean requests reach WordPress.', 'botblocker-security' );
				?>
			</div>
		<?php endif; ?>
		<?php
	}
};
