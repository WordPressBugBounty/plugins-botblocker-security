<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return static function (): void {
	?><div id="bbcs-palette" class="bbcs-pal-overlay" hidden>
	<div class="bbcs-card bbcs-pal">
		<div class="bbcs-pal-top">
			<svg class="bbcs-ico"><use href="#bbcs-i-search"></use></svg>
			<input id="bbcs-pal-input" class="bbcs-pal-input" placeholder="<?php esc_attr_e( 'Find any setting or action…', 'botblocker-security' ); ?>" autocomplete="off" aria-label="<?php esc_attr_e( 'Search settings and actions', 'botblocker-security' ); ?>" />
			<button type="button" id="bbcs-pal-close" class="bbcs-pal-close" aria-label="<?php esc_attr_e( 'Close', 'botblocker-security' ); ?>"><svg class="bbcs-ico"><use href="#bbcs-i-x"></use></svg></button>
		</div>
		<div class="bbcs-pal-list" id="bbcs-pal-list"></div>
		<div class="bbcs-pal-foot">
			<span><b class="bbcs-mono">↑↓</b> <?php esc_html_e( 'navigate', 'botblocker-security' ); ?></span>
			<span><b class="bbcs-mono">↵</b> <?php esc_html_e( 'open', 'botblocker-security' ); ?></span>
			<span><b class="bbcs-mono">esc</b> <?php esc_html_e( 'close', 'botblocker-security' ); ?></span>
		</div>
	</div>
</div>
	<?php
};
