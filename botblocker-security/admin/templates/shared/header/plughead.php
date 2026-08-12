<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return static function ( Botblocker_HeaderViewModel $h ): void {
	?><div class="bbcs-plughead">
		<div class="bbcs-plughead-left">
			<a class="bbcs-logo" href="<?php echo esc_url( $h->dashboard_url ); ?>">
				<?php
				// Static plugin asset, not a user-uploaded Media Library image.
				// phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
				<img class="bbcs-logo-img" src="<?php echo esc_url( $h->logo_url ); ?>" alt="<?php echo esc_attr( $h->site_name ); ?>" />
			</a>
			<span class="bbcs-chip bbcs-chip--kbd">v<?php echo esc_html( $h->version ); ?></span>

			<?php if ( $h->has_pro ) : ?>
			<a class="bbcs-pro-cta bbcs-pro-cta--active" href="<?php echo esc_url( $h->cloud_api_url ); ?>">
				<svg class="bbcs-ico"><use href="#bbcs-i-crown"></use></svg>
				<?php esc_html_e( 'PRO is active', 'botblocker-security' ); ?>
			</a>
			<?php else : ?>
			<a class="bbcs-pro-cta" href="<?php echo esc_url( $h->cloud_api_url ); ?>">
				<svg class="bbcs-ico"><use href="#bbcs-i-crown"></use></svg>
				<b><?php esc_html_e( 'Upgrade to PRO', 'botblocker-security' ); ?></b>
			</a>
			<?php endif; ?>
		</div>

		<div class="bbcs-search" id="bbcs-search">
			<svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-search"></use></svg>
			<span class="bbcs-search-label"><?php esc_html_e( 'Find any setting or action… (or just start typing)', 'botblocker-security' ); ?></span>
		</div>

		<div class="bbcs-plughead-right">
			<?php if ( ! empty( $h->lang_options ) ) : ?>
			<div class="bbcs-drop bbcs-lang">
				<button class="bbcs-btn bbcs-btn--ghost bbcs-drop-trigger" aria-expanded="false" type="button">
					<svg class="bbcs-ico bbcs-ico--md"><use href="#bbcs-i-globe"></use></svg>
					<span class="bbcs-fw-medium"><?php echo esc_html( strtoupper( substr( $h->current_locale, 0, 2 ) ) ); ?></span>
					<svg class="bbcs-ico bbcs-ico--xs"><use href="#bbcs-i-chevron"></use></svg>
				</button>
				<div class="bbcs-drop-menu" role="menu" hidden>
				<?php foreach ( $h->lang_options as $lang ) : ?>
				<?php $is_active = ( $lang->lang === $h->current_locale ); ?>
				<div class="bbcs-drop-item<?php echo $is_active ? ' active' : ''; ?>" role="menuitem" data-lang="<?php echo esc_attr( $lang->lang ); ?>">
					<div class="flag flag-<?php echo esc_attr( $lang->flag ); ?>"></div>
					<?php if ( $is_active ) : ?>
					<b><?php echo esc_html( $lang->name ); ?></b>
					<svg class="bbcs-ico bbcs-ico--xs bbcs-tx-green"><use href="#bbcs-i-check"></use></svg>
					<?php else : ?>
					<?php echo esc_html( $lang->name ); ?>
					<?php endif; ?>
				</div>
				<?php endforeach; ?>
				</div>
			</div>
			<?php endif; ?>

			<div class="bbcs-drop">
				<button class="bbcs-btn bbcs-btn--ghost bbcs-btn--icon bbcs-drop-trigger" aria-expanded="false" type="button">
					<svg class="bbcs-ico bbcs-ico--md"><use href="#bbcs-i-bell"></use></svg>
					<?php if ( $h->alerts_count > 0 ) : ?>
					<span class="bbcs-badge"><?php echo esc_html( $h->alerts_count ); ?></span>
					<?php endif; ?>
				</button>
				<div class="bbcs-drop-menu" role="menu" hidden>
					<?php if ( ! empty( $h->alerts ) ) : ?>
					<?php foreach ( $h->alerts as $alert ) : ?>
					<?php if ( ! empty( $alert->link ) ) : ?>
					<a class="bbcs-drop-item" role="menuitem" href="<?php echo esc_url( $alert->link ); ?>">
						<div class="bbcs-fill">
							<div class="bbcs-fw-semibold bbcs-fs-xs"><?php echo esc_html( $alert->title ); ?></div>
							<div class="bbcs-dim bbcs-fs-2xs"><?php echo esc_html( $alert->message ); ?></div>
							<?php if ( ! empty( $alert->link_text ) ) : ?>
							<div class="bbcs-tx-green bbcs-fs-2xs"><?php echo esc_html( $alert->link_text ); ?> →</div>
							<?php endif; ?>
						</div>
					</a>
					<?php else : ?>
					<div class="bbcs-drop-item" role="menuitem">
						<div class="bbcs-fill">
							<div class="bbcs-fw-semibold bbcs-fs-xs"><?php echo esc_html( $alert->title ); ?></div>
							<div class="bbcs-dim bbcs-fs-2xs"><?php echo esc_html( $alert->message ); ?></div>
						</div>
					</div>
					<?php endif; ?>
					<?php endforeach; ?>
					<?php else : ?>
					<div class="bbcs-drop-item" role="menuitem">
						<div class="bbcs-dim bbcs-fs-xs"><?php esc_html_e( 'No alerts', 'botblocker-security' ); ?></div>
					</div>
					<?php endif; ?>
				</div>
			</div>

			<a class="bbcs-btn bbcs-btn--ghost bbcs-btn--icon" href="<?php echo esc_url( $h->about_url ); ?>" aria-label="<?php esc_attr_e( 'Support', 'botblocker-security' ); ?>">
				<svg class="bbcs-ico bbcs-ico--md"><use href="#bbcs-i-headset"></use></svg>
			</a>
			<button class="bbcs-btn bbcs-btn--ghost bbcs-btn--icon bbcs-search-mob" id="bbcs-search-mob" type="button" aria-label="<?php esc_attr_e( 'Search', 'botblocker-security' ); ?>">
				<svg class="bbcs-ico bbcs-ico--md"><use href="#bbcs-i-search"></use></svg>
			</button>
		</div>
	</div>
	<?php
};
