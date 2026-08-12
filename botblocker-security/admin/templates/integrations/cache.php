<?php

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$bbcs_cache_md = '';
$bbcs_md_path  = BOTBLOCKER_DIR . 'docs/CACHE-COMPATIBILITY.md';
if ( file_exists( $bbcs_md_path ) ) {
	$bbcs_cache_md = (string) file_get_contents( $bbcs_md_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
}

return static function ( Botblocker_IntegrationsViewModel $data, bool $isActive ) use ( $bbcs_cache_md ): void {
	?>
	<div role="tabpanel" class="bbcs-tabpanel bbcs-protect-layout" data-tabpanel="cache"<?php echo $isActive ? '' : ' hidden' ?>>
		<div class="bbcs-infocol" data-anchor="cache_compat_guide">
			<div class="bbcs-infocol-ic"><img src="<?php echo esc_url( BOTBLOCKER_URL . 'public/icons/rocket.svg' ); ?>" alt="" class="bbcs-info-img" /></div>
			<div class="bbcs-infocol-body">
				<div class="bbcs-infocol-desc"><?php esc_html_e( 'Server-level caches that bypass PHP can serve cached copies of the BotBlocker verification page, breaking protection. Configure a cookie-based exception for every cache in front of your site.', 'botblocker-security' ); ?></div>
				<div class="bbcs-doclist">
					<div class="bbcs-doclist-head"><span class="bbcs-help-q">?</span><?php esc_html_e( 'Documentation', 'botblocker-security' ); ?></div>
					<a href="<?php echo esc_url( $data->docs_url ); ?>/caching/" target="_blank" class="bbcs-link bbcs-fs-xs"><?php esc_html_e( 'Caching', 'botblocker-security' ); ?></a>
				</div>
			</div>
		</div>
		<div>
			<div class="bbcs-setgroup">
				<div class="bbcs-setgroup-head"><?php esc_html_e( 'Cache Compatibility', 'botblocker-security' ); ?></div>
				<?php foreach ( $data->cache_systems as $bbcs_sys_key => $bbcs_sys ) : ?>
					<?php
					$bbcs_state  = $bbcs_sys['state'] ?? 'not_detected';
					$bbcs_badge  = 'bbcs-dim';
					$bbcs_state_label = __( 'Not detected', 'botblocker-security' );
					if ( $bbcs_state === 'needs_config' ) {
						$bbcs_badge  = 'bbcs-text-danger';
						$bbcs_state_label = __( 'Needs configuration', 'botblocker-security' );
					} elseif ( $bbcs_state === 'installed' ) {
						$bbcs_badge  = 'bbcs-text-success';
						$bbcs_state_label = __( 'Detected / auto-compatible', 'botblocker-security' );
					}
					?>
					<div class="bbcs-option bbcs-hoverbg">
						<span class="bbcs-option-label bbcs-fw-semibold"><?php echo esc_html( $bbcs_sys['label'] ); ?></span>
						<span class="bbcs-fs-xs <?php echo esc_attr( $bbcs_badge ); ?>"><?php echo esc_html( $bbcs_state_label ); ?></span>
						<?php if ( ! empty( $bbcs_sys['warning'] ) ) : ?>
							<span class="bbcs-help"><span class="bbcs-help-q">?</span><span class="bbcs-help-tip"><?php echo esc_html( $bbcs_sys['warning'] ); ?></span></span>
						<?php elseif ( ! empty( $bbcs_sys['note'] ) ) : ?>
							<span class="bbcs-fs-xs bbcs-dim"><?php echo esc_html( $bbcs_sys['note'] ); ?></span>
						<?php endif; ?>
					</div>
				<?php endforeach; ?>
				<div class="bbcs-option bbcs-hoverbg">
					<button type="button" class="bbcs-btn" id="bbcs-cache-compat-guide-trigger">
						<svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-doc"></use></svg>
						<?php esc_html_e( 'Open CACHE-COMPATIBILITY.md', 'botblocker-security' ); ?>
					</button>
					<span class="bbcs-option-label"><?php esc_html_e( 'Full guide: cache plugins, server-level caches and cookie exceptions.', 'botblocker-security' ); ?></span>
					<span class="bbcs-help"><span class="bbcs-help-q">?</span><span class="bbcs-help-tip"><?php esc_attr_e( 'Shows the raw contents of docs/CACHE-COMPATIBILITY.md shipped with the plugin.', 'botblocker-security' ); ?></span></span>
				</div>
			</div>
		</div>
	</div>

	<div class="bbcs-modal-overlay" id="bbcsCacheCompatModal" style="display:none;">
		<div class="bbcs-modal bbcs-modal--wide">
			<div class="bbcs-modal-header">
				<div class="bbcs-modal-title">
					<svg class="bbcs-ico bbcs-ico--sm" style="margin-right:var(--bbcs-sp-1);"><use href="#bbcs-i-doc"></use></svg>
					<?php esc_html_e( 'CACHE-COMPATIBILITY.md', 'botblocker-security' ); ?>
				</div>
				<button type="button" class="bbcs-modal-close" data-modal-close>
					<svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-x"></use></svg>
				</button>
			</div>
			<div class="bbcs-modal-body">
				<pre class="bbcs-md-view"><?php echo esc_html( $bbcs_cache_md ); ?></pre>
			</div>
			<div class="bbcs-modal-footer">
				<button type="button" class="bbcs-btn" data-modal-close><?php esc_html_e( 'Close', 'botblocker-security' ); ?></button>
			</div>
		</div>
	</div>

	<script>
	(function() {
		'use strict';
		var trigger = document.getElementById('bbcs-cache-compat-guide-trigger');
		var overlay = document.getElementById('bbcsCacheCompatModal');
		if (!trigger || !overlay) return;

		trigger.addEventListener('click', function(e) {
			e.preventDefault();
			overlay.style.display = 'flex';
		});

		overlay.addEventListener('click', function(e) {
			var btn = e.target.closest('[data-modal-close]');
			if (btn) {
				overlay.style.display = 'none';
				return;
			}
			if (e.target === overlay) {
				overlay.style.display = 'none';
			}
		});

		document.addEventListener('keydown', function(e) {
			if (e.key === 'Escape' && overlay.style.display === 'flex') {
				overlay.style.display = 'none';
			}
		});
	})();
	</script>
	<?php
};
