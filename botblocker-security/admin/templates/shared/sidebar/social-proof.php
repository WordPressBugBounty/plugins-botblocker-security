<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
return static function ( Botblocker_SidebarViewModel $sidebar ): void {
	if ( empty( $sidebar->social_proof ) ) {
		return;
	}
	$p = $sidebar->social_proof;
	?>
	<section class="card bbcs-card-border-left">
		<header class="card-header bbcs_small_header">
			<h2 class="card-title"><?php esc_html_e( 'Trusted by users', 'botblocker-security' ); ?></h2>
		</header>
		<div class="card-body">
			<?php if ( $p->hasRatings() ) : ?>
				<div class="bbcs-social-proof-rating">
					<?php for ( $i = 1; $i <= 5; $i++ ) : ?>
						<?php if ( $i <= $p->getFullStars() ) : ?>
							<i class="fa-solid fa-star bbcs-cloud-api-color"></i>
						<?php elseif ( $i === $p->getFullStars() + 1 && $p->hasHalfStar() ) : ?>
							<i class="fa-solid fa-star-half-stroke bbcs-cloud-api-color"></i>
						<?php else : ?>
							<i class="fa-regular fa-star bbcs-text-muted"></i>
						<?php endif; ?>
					<?php endfor; ?>
					<span class="ms-1"><b><?php echo esc_html( $p->getRatingValue() ); ?></b>
						<small class="bbcs-text-muted"><?php echo esc_html( $p->getRatingsLabel() ); ?></small>
					</span>
				</div>
			<?php endif; ?>
			<?php if ( $p->hasInstalls() ) : ?>
				<div class="mt-2">
					<i class="fa-solid fa-shield-halved bbcs_color_green me-1"></i>
					<small><?php echo esc_html( $p->getInstallsLabel() ); ?></small>
				</div>
			<?php endif; ?>
			<a href="https://wordpress.org/plugins/botblocker-security/" target="_blank" rel="noopener noreferrer" class="bbcs-info-footer-a mt-2 d-inline-block">
				<?php esc_html_e( 'View on WordPress.org', 'botblocker-security' ); ?>
			</a>
		</div>
	</section>
	<?php
};
