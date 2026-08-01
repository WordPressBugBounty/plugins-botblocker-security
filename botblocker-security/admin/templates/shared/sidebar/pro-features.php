<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
return static function ( Botblocker_SidebarViewModel $sidebar ): void {
	$s = $sidebar;
	?>
	<section class="card bbcs-card-border-left ">
		<header class="card-header bbcs_small_header bbcs-cloud-api-color-bg">
			<div class="card-actions bbcs_header_controls">
				<span class="bbcs-help" style="display:inline-flex">
				<a href="<?php echo esc_url( BOTBLOCKER_DOCS_URL ); ?>/cloud-verification-in-botblocker-database-types-used-for-advanced-threat-detection/"
				target="_blank" class="me-2">
				<i class="fa-solid fa-globe bbcs-color-white"></i>
				</a>
				<span class="bbcs-help-tip"><?php esc_html_e( 'About Cloud Verification', 'botblocker-security' ); ?></span>
				</span>

				<span class="bbcs-help" style="display:inline-flex">
				<a href="<?php echo esc_url( BOTBLOCKER_DOCS_URL ); ?>/how-botblocker-pros-cloud-verification-defeats-bots/"
				target="_blank">
				<i class="fa-solid fa-globe bbcs-color-white"></i>
				</a>
				<span class="bbcs-help-tip"><?php esc_html_e( 'BotBlocker PRO', 'botblocker-security' ); ?></span>
				</span>

			</div>
			<h2 class="card-title bbcs-color-white"><?php esc_html_e( 'BotBlocker PRO', 'botblocker-security' ); ?></h2>

		</header>
		<div class="card-body">
			<?php if ( ! empty( $s->pro_features ) ) : ?>
				<ul class="bbcs-cloud-api-features">
				<?php foreach ( $s->pro_features as $f ) : ?>
					<li><i class="fa-solid fa-check me-1"></i><?php echo esc_html( $f ); ?></li>
				<?php endforeach; ?>
				</ul>
			<?php endif; ?>
			<a href="<?php echo esc_url( $s->cloud_api_url ); ?>" class="mt-2 btn btn-sm bbcs-btn-primary-cta w-100"><i
					class="fa-solid fa-crown"></i>&nbsp;<?php esc_html_e( 'Get BotBlocker PRO', 'botblocker-security' ); ?>
			</a>
			<a href="https://botblocker.top/pricing/" target="_blank" rel="noopener noreferrer" class="mt-2 btn btn-sm btn-default w-100">
				<i class="fa-solid fa-table-list me-1"></i><?php esc_html_e( 'Compare Free vs PRO', 'botblocker-security' ); ?>
			</a>
		</div>
	</section>
	<?php
};
