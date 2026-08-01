<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// Shared shell rail - status card, social proof card, system info card.
return static function ( Botblocker_SidebarViewModel $s ): void {
	$t = $s->toggles;
	?>
	<aside class="bbcs-rail">
		<div class="bbcs-card bbcs-rail-card">
			<div class="bbcs-row bbcs-row--between bbcs-mb-3h">
				<span class="bbcs-rail-title"><?php esc_html_e( 'Status', 'botblocker-security' ); ?></span>
				<a class="bbcs-dim bbcs-pointer" href="<?php echo esc_url( $s->tools_url ); ?>" aria-label="<?php esc_attr_e( 'Tools', 'botblocker-security' ); ?>">
					<svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-gear"></use></svg>
				</a>
			</div>
			<div class="bbcs-row bbcs-g-2h bbcs-mb-2h">
				<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
				<span class="<?php echo $s->is_active ? 'bbcs-tx-green' : 'bbcs-tx-amber'; ?>"><svg class="bbcs-ico bbcs-ico--lg"><use href="#bbcs-i-<?php echo $s->is_active ? 'shieldCheck' : 'shield'; ?>"></use></svg></span>
				<span class="bbcs-fw-bold bbcs-fs-md"><?php echo esc_html( $s->status_label ); ?></span>
			</div>
			<?php
			$render_toggles = require BOTBLOCKER_DIR . 'admin/templates/shared/system-toggles.php';
			$render_toggles( $t );
			?>
			<div class="bbcs-divider bbcs-col bbcs-g-1">
				<div class="bbcs-kv"><span class="bbcs-muted"><?php esc_html_e( 'Blocked today', 'botblocker-security' ); ?></span><b class="bbcs-mono"><?php echo esc_html( (string) $s->today_blocked ); ?></b></div>
				<div class="bbcs-kv"><span class="bbcs-muted"><?php esc_html_e( 'Total blocked', 'botblocker-security' ); ?></span><b class="bbcs-mono"><?php echo esc_html( (string) $s->total_blocked ); ?></b></div>
			</div>
		</div>

		<?php if ( ! empty( $s->social_proof ) ) : ?>
		<?php $p = $s->social_proof; ?>
		<div class="bbcs-card bbcs-rail-card">
			<div class="bbcs-rail-title bbcs-mb-2h"><?php esc_html_e( 'Trusted by users', 'botblocker-security' ); ?></div>
			<?php if ( $p->hasRatings() ) : ?>
				<div class="bbcs-row bbcs-g-2">
					<span class="bbcs-stars">
						<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
							<?php if ( $i <= $p->getFullStars() ) : ?>
								<svg class="bbcs-ico bbcs-ico--fill"><use href="#bbcs-i-star"></use></svg>
							<?php elseif ( $i === $p->getFullStars() + 1 && $p->hasHalfStar() ) : ?>
								<span style="position:relative;display:inline-flex;">
									<svg class="bbcs-ico bbcs-ico--fill" aria-hidden="true" style="color:var(--bbcs-tx3);"><use href="#bbcs-i-star"></use></svg>
									<svg class="bbcs-ico bbcs-ico--fill" aria-hidden="true" style="color:var(--bbcs-amber);position:absolute;top:0;left:0;clip-path:inset(0 50% 0 0);"><use href="#bbcs-i-star"></use></svg>
								</span>
							<?php else : ?>
								<svg class="bbcs-ico" style="color:var(--bbcs-tx3);"><use href="#bbcs-i-star"></use></svg>
							<?php endif; ?>
						<?php endfor; ?>
					</span>
					<b class="bbcs-fs-lg"><?php echo esc_html( $p->getRatingValue() ); ?></b>
					<span class="bbcs-dim bbcs-fs-xs"><?php echo esc_html( $p->getRatingsLabel() ); ?></span>
				</div>
			<?php endif; ?>
			<?php if ( $p->hasInstalls() ) : ?>
				<div class="bbcs-row bbcs-g-1h bbcs-mt-1h">
					<svg class="bbcs-ico bbcs-ico--fill bbcs-tx-green" style="width:1em;height:1em;"><use href="#bbcs-i-shieldCheck"></use></svg>
					<small class="bbcs-fs-xs"><?php echo esc_html( $p->getInstallsLabel() ); ?></small>
				</div>
			<?php endif; ?>
			<div class="bbcs-mt-2h">
				<a class="bbcs-link bbcs-fs-xs" href="https://wordpress.org/plugins/botblocker-security/" target="_blank" rel="noopener">
					<?php esc_html_e( 'View on WordPress.org', 'botblocker-security' ); ?>
					<svg class="bbcs-ico bbcs-ico--xs"><use href="#bbcs-i-external"></use></svg>
				</a>
			</div>
		</div>
		<?php endif; ?>

		<?php if ( ! $s->contact_collected ) : ?>
		<div class="bbcs-card bbcs-rail-card">
			<div class="bbcs-row bbcs-row--between bbcs-mb-3h">
				<span class="bbcs-rail-title"><?php esc_html_e( 'Security Updates & Offers', 'botblocker-security' ); ?></span>
				<a class="bbcs-dim bbcs-pointer" href="<?php echo esc_url( $s->settings_url ); ?>" aria-label="<?php esc_attr_e( 'Settings', 'botblocker-security' ); ?>">
					<svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-gear"></use></svg>
				</a>
			</div>
			<p class="bbcs-dim bbcs-fs-sm bbcs-mb-3h"><?php esc_html_e( 'Get security updates and offers by email', 'botblocker-security' ); ?></p>
			<div class="bbcs-row bbcs-g-2h">
				<input value="<?php echo esc_attr( $s->contact_email ); ?>" type="email" id="bbcs_contact_email" class="bbcs-input" placeholder="<?php esc_attr_e( 'Your email', 'botblocker-security' ); ?>" style="flex:1;min-width:0;">
				<button type="button" id="bbcs_send_activation_btn" class="bbcs-btn" style="padding-block:var(--bbcs-sp-2h);">
					<svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-mail"></use></svg>
					<?php esc_html_e( 'Subscribe', 'botblocker-security' ); ?>
				</button>
			</div>
			<div id="bbcs_activation_response" class="bbcs-mt-2h" style="display: none;"></div>
		</div>
		<script>
		(function() {
			var btn = document.getElementById('bbcs_send_activation_btn');
			var inp = document.getElementById('bbcs_contact_email');
			var resp = document.getElementById('bbcs_activation_response');
			if (!btn || !inp) return;
			btn.addEventListener('click', function(e) {
				e.preventDefault();
				var data = (inp.value || '').trim();
				var orig = btn.innerHTML;
				btn.disabled = true;
				btn.textContent = '<?php echo esc_js( __( 'Sending…', 'botblocker-security' ) ); ?>';
				var xhr = new XMLHttpRequest();
				xhr.open('POST', (typeof botblockerData !== 'undefined' ? botblockerData.ajaxurl : ajaxurl));
				xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
				xhr.onload = function() {
					if (xhr.status === 200) {
						try {
							var r = JSON.parse(xhr.responseText);
							if (r.success) {
								btn.innerHTML = '<svg class="bbcs-ico bbcs-ico--sm bbcs-tx-green"><use href="#bbcs-i-check"></use></svg>';
								btn.disabled = true;
								setTimeout(function() {
									var card = btn.closest('.bbcs-rail-card');
									if (card) card.style.transition = 'opacity .4s', card.style.opacity = '0', setTimeout(function() { card.remove(); }, 450);
								}, 1000);
								return;
							}
						} catch(e) {}
					}
					btn.disabled = false;
					btn.innerHTML = orig;
					if (resp) {
						resp.style.display = 'block';
						resp.className = 'bbcs-mt-2h bbcs-tx-red bbcs-fs-xs';
						resp.textContent = '<?php echo esc_js( __( 'Subscription failed. Please try again.', 'botblocker-security' ) ); ?>';
					}
				};
				xhr.onerror = function() {
					btn.disabled = false;
					btn.innerHTML = orig;
				};
				xhr.send('action=bbcs_contact_email&nonce=' + encodeURIComponent(typeof botblockerData !== 'undefined' ? botblockerData.nonce : '') + '&data=' + encodeURIComponent(data));
			});
		})();
		</script>
		<?php endif; ?>

		<?php if ( ! $s->cloud_api_active ) : ?>
		<?php
		$tips = array(
			esc_html__( 'Enable Two-Factor Authentication (2FA) for all administrator accounts. It adds an essential layer of security that stops 99% of automated credential stuffing and brute-force attacks from accessing your dashboard.', 'botblocker-security' ),
			esc_html__( 'BotBlocker PRO validates visitors in the cloud before they reach your WordPress installation. This preserves your server CPU and memory while stopping aggressive DDoS attacks in their tracks.', 'botblocker-security' ),
			esc_html__( 'Are you getting spam from specific regions? You can block entire high-risk countries in the Rules & IPs section. Country blocking is a highly effective way to reduce unwanted bot traffic by up to 80%.', 'botblocker-security' ),
			esc_html__( 'PRO users automatically share real-time threat intelligence. When one site on our network is attacked by a new botnet, the IP addresses are flagged and all PRO sites are protected instantly.', 'botblocker-security' ),
			esc_html__( 'The Early Init add-on allows BotBlocker to execute at the very beginning of the WordPress load cycle. This stops bad bots early, saving massive amounts of database queries and server resources.', 'botblocker-security' )
		);
		?>
		<div class="bbcs-card bbcs-rail-card bbcs-tips-card" id="bbcs-tips-card">
			<div class="bbcs-row bbcs-row--between bbcs-mb-3">
				<span class="bbcs-rail-title"><?php esc_html_e( 'Security Tips', 'botblocker-security' ); ?></span>
				<a class="bbcs-link bbcs-fs-xs" href="<?php echo esc_url( $s->cloud_api_url ); ?>"><?php esc_html_e( 'Explore PRO', 'botblocker-security' ); ?></a>
			</div>
			<div class="bbcs-dim bbcs-fs-sm bbcs-tips-body" id="bbcs-rotating-tip">
				<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
				<?php echo $tips[0]; ?>
			</div>
			<div class="bbcs-tips-progress-wrap">
				<div class="bbcs-tips-progress-fill"></div>
			</div>
		</div>
		<script>
		document.addEventListener('DOMContentLoaded', function() {
			<?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
			var tips = <?php echo wp_json_encode( $tips ); ?>;
			var idx = 0;
			var el = document.getElementById('bbcs-rotating-tip');
			var card = document.getElementById('bbcs-tips-card');
			if(el && card && tips.length > 1) {
				var isHovered = false;
				var ticks = 0;
				
				card.addEventListener('mouseenter', function() { isHovered = true; });
				card.addEventListener('mouseleave', function() { isHovered = false; });

				setInterval(function() {
					if (isHovered) return;
					
					ticks++;
					
					if (ticks === 592) { // 59.2 seconds -> start fading out
						el.classList.add('bbcs-tips-body--hidden');
					}
					
					if (ticks >= 600) { // 60.0 seconds -> swap text and fade in
						ticks = 0;
						idx = (idx + 1) % tips.length;
						el.innerText = tips[idx];
						el.classList.remove('bbcs-tips-body--hidden');
					}
				}, 100);
			}
		});
		</script>
		<?php endif; ?>
	</aside>
	<?php
};
