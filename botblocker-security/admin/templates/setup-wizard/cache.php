<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return static function ( Botblocker_SetupWizard_ViewModel $d ): void { ?>
	<div class="bbcs-wizstep" data-step="6">
		<h2 class="bbcs-wiztitle"><?php esc_html_e( 'Performance optimization', 'botblocker-security' ); ?></h2>
		<p class="bbcs-wizsub"><?php esc_html_e( 'Caching for faster security checks', 'botblocker-security' ); ?></p>

		<div class="bbcs-wizcards">
			<div class="bbcs-wizcard" data-cache="redis">
				<span class="bbcs-cache-status" style="position:absolute;top:8px;right:8px;font-size:var(--bbcs-fs-sm);color:var(--bbcs-tx3);display:flex;align-items:center;gap:4px"><svg class="bbcs-ico bbcs-ico--sm bbcs-ico--spinner"><use href="#bbcs-i-refresh"></use></svg></span>
				<div class="bbcs-wizcard-ic"><svg class="bbcs-ico"><use href="#bbcs-i-bolt"></use></svg></div>
				<div class="bbcs-wizcard-title">Redis</div>
				<div class="bbcs-wizcard-sub"><?php esc_html_e( 'Ultra-fast in-memory cache. Best performance.', 'botblocker-security' ); ?></div>
				<ul class="bbcs-wizcard-feats">
					<li><?php esc_html_e( 'Instant response', 'botblocker-security' ); ?></li>
					<li><?php esc_html_e( 'Persistent connections', 'botblocker-security' ); ?></li>
					<li><?php esc_html_e( 'Advanced data structures', 'botblocker-security' ); ?></li>
				</ul>
			</div>

			<div class="bbcs-wizcard" data-cache="memcached">
				<span class="bbcs-cache-status" style="position:absolute;top:8px;right:8px;font-size:var(--bbcs-fs-sm);color:var(--bbcs-tx3);display:flex;align-items:center;gap:4px"><svg class="bbcs-ico bbcs-ico--sm bbcs-ico--spinner"><use href="#bbcs-i-refresh"></use></svg></span>
				<div class="bbcs-wizcard-ic"><svg class="bbcs-ico"><use href="#bbcs-i-server"></use></svg></div>
				<div class="bbcs-wizcard-title">Memcached</div>
				<div class="bbcs-wizcard-sub"><?php esc_html_e( 'High-performance distributed caching system.', 'botblocker-security' ); ?></div>
				<ul class="bbcs-wizcard-feats">
					<li><?php esc_html_e( 'Simple and reliable', 'botblocker-security' ); ?></li>
					<li><?php esc_html_e( 'Low resource consumption', 'botblocker-security' ); ?></li>
					<li><?php esc_html_e( 'Easy scaling', 'botblocker-security' ); ?></li>
				</ul>
			</div>

			<div class="bbcs-wizcard is-sel" data-cache="none">
				<div class="bbcs-wizcard-ic"><svg class="bbcs-ico"><use href="#bbcs-i-ban"></use></svg></div>
				<div class="bbcs-wizcard-title"><?php esc_html_e( 'No cache', 'botblocker-security' ); ?></div>
				<div class="bbcs-wizcard-sub"><?php esc_html_e( 'WordPress transients. Works on any hosting.', 'botblocker-security' ); ?></div>
				<ul class="bbcs-wizcard-feats">
					<li><?php esc_html_e( 'No configuration needed', 'botblocker-security' ); ?></li>
					<li><?php esc_html_e( 'Works everywhere', 'botblocker-security' ); ?></li>
					<li><?php esc_html_e( 'Standard performance', 'botblocker-security' ); ?></li>
				</ul>
			</div>
		</div>

		<div class="bbcs-card bbcs-card-pad bbcs-mb-4h bbcs-blue-card">
			<div class="bbcs-fs-xs bbcs-dim"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-bolt"></use></svg> <b><?php esc_html_e( 'Tip:', 'botblocker-security' ); ?></b> <?php esc_html_e( 'Redis is faster for high-traffic sites. Memcached suits distributed systems. You can change this later in Tools.', 'botblocker-security' ); ?></div>
		</div>

		<div class="bbcs-wizcta">
			<button class="bbcs-btn bbcs-btn--lg" id="bbcs-wiz-back6"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-arrowL"></use></svg> <?php esc_html_e( 'Back', 'botblocker-security' ); ?></button>
			<button class="bbcs-btn bbcs-btn--pri bbcs-btn--lg" id="bbcs-wiz-next6"><?php esc_html_e( 'Continue', 'botblocker-security' ); ?> <svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-arrowR"></use></svg></button>
		</div>
	</div>
<?php };
