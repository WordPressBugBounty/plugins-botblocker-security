<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

return static function ( Botblocker_Rules_View $view, Botblocker_Layout_View $layout ): void {
	$layout->icons_sprite();
	$data = $view->getData();

	?>
<div class="bbcs-app">

	<?php $layout->header(); ?>

	<div class="bbcs-content">
			<div class="bbcs-wrap">

				<section class="bbcs-page" data-page="rules">
					<div class="bbcs-pagehead">
						<div><div class="bbcs-pagehead-title"><?php esc_html_e( 'Rules and IP Lists', 'botblocker-security' ); ?></div><div class="bbcs-pagehead-sub"><?php esc_html_e( 'Granular rules for IPs, ASNs, countries, paths, and headers', 'botblocker-security' ); ?></div></div>
						<div class="bbcs-pagehead-actions">
					<button id="bbcs_pagehead_import" class="bbcs-btn"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-upload"></use></svg><?php esc_html_e( 'Import', 'botblocker-security' ); ?></button>
						<button id="bbcs_pagehead_export" class="bbcs-btn"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-doc"></use></svg><?php esc_html_e( 'Export', 'botblocker-security' ); ?></button>
						<button id="bbcs_pagehead_add" class="bbcs-btn bbcs-btn--pri"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-plus"></use></svg><?php esc_html_e( 'Add Rule', 'botblocker-security' ); ?></button>
						<button id="bbcs_pagehead_llm_sync" class="bbcs-btn" style="display:none;"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-cloud-download"></use></svg><?php esc_html_e( 'Sync from Cloud', 'botblocker-security' ); ?></button>
						<button id="bbcs_pagehead_llm_download" class="bbcs-btn" style="display:none;"><svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-doc"></use></svg><?php esc_html_e( 'Download as JSON', 'botblocker-security' ); ?></button>
						</div>
					</div>
					<div class="bbcs-rules-layout">
						<div class="bbcs-card bbcs-card-pad">
							<?php $view->body(); ?>
						</div>
						<div class="bbcs-card bbcs-card-pad">
							<div class="bbcs-section-title bbcs-mb-3"><?php esc_html_e( 'Quick Guide', 'botblocker-security' ); ?></div>
							<div class="bbcs-col bbcs-g-3">
									<div class="bbcs-guide-row"><b><?php esc_html_e( 'Paths:', 'botblocker-security' ); ?></b> <?php esc_html_e( 'exclude payment callbacks to avoid blocking them -', 'botblocker-security' ); ?> <span class="bbcs-link" data-tab-link="Paths"><?php esc_html_e( 'Paths', 'botblocker-security' ); ?></span>.</div>
								<div class="bbcs-guide-row"><b><?php esc_html_e( 'IP Lists:', 'botblocker-security' ); ?></b> <?php esc_html_e( 'import lists into', 'botblocker-security' ); ?> <span class="bbcs-link" data-tab-link="IPv4 List"><?php esc_html_e( 'IPv4', 'botblocker-security' ); ?></span> / <span class="bbcs-link" data-tab-link="IPv6 List"><?php esc_html_e( 'IPv6', 'botblocker-security' ); ?></span>.</div>
								<div class="bbcs-guide-row"><b><?php esc_html_e( 'Trusted Bots:', 'botblocker-security' ); ?></b> <?php esc_html_e( 'verified crawlers -', 'botblocker-security' ); ?> <span class="bbcs-link" data-tab-link="Trusted Bots"><?php esc_html_e( 'Trusted Bots', 'botblocker-security' ); ?></span>.</div>
								<div class="bbcs-guide-row"><b><?php esc_html_e( 'GEO / ASN:', 'botblocker-security' ); ?></b> <?php esc_html_e( 'block by country or network - GEO, ASN.', 'botblocker-security' ); ?></div>
							</div>
							<div class="bbcs-divider"></div>
							<div class="bbcs-section-title bbcs-mb-3"><?php esc_html_e( 'Table overview', 'botblocker-security' ); ?></div>
							<div id="bbcs-table-overview" class="bbcs-table-overview">
							<?php foreach ( $data->table_counts as $tc ) : ?>
								<div class="bbcs-table-overview__item">
									<div class="bbcs-table-overview__label"><?php echo esc_html( $tc['label'] ); ?></div>
									<div class="bbcs-table-overview__stats">
										<div class="status-stat">
											<div class="bbcs-stat bbcs-stat--sm bbcs-tx-green"><?php echo esc_html( (string) $tc['active'] ); ?></div>
											<div class="bbcs-dim bbcs-fs-xs bbcs-mt-1"><?php esc_html_e( 'Active', 'botblocker-security' ); ?></div>
										</div>
										<?php if ( $tc['name'] !== 'Proxy' && $tc['name'] !== 'GEO' ) : ?>
										<div class="status-stat">
											<div class="bbcs-stat bbcs-stat--sm"><?php echo esc_html( (string) $tc['disabled'] ); ?></div>
											<div class="bbcs-dim bbcs-fs-xs bbcs-mt-1"><?php esc_html_e( 'Disabled', 'botblocker-security' ); ?></div>
										</div>
										<?php endif; ?>
										<?php if ( $tc['attention'] > 0 ) : ?>
										<div class="status-stat">
											<div class="bbcs-stat bbcs-stat--sm bbcs-tx-amber"><?php echo esc_html( (string) $tc['attention'] ); ?></div>
											<div class="bbcs-dim bbcs-fs-xs bbcs-mt-1"><?php esc_html_e( 'Attention', 'botblocker-security' ); ?></div>
										</div>
										<?php endif; ?>
									</div>
								</div>
							<?php endforeach; ?>
							</div>
						</div>
					</div>
				</section>

			</div>
	</div>
	<?php $layout->command_palette(); ?>
</div>
	<?php
	$view->modals();
};
