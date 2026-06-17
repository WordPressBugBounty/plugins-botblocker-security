<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

$bbcs_has_pro          = BotBlockerPro::isActive();
$bbcs_wizard_completed = BotBlockerMultisite::getOption( 'bbcs_setup_wizard_completed', false );
$bbcs_health_score     = bbcs_calculateSiteHealth();
?>

<div class="col-lg-6">
<section class="card bbcs-fill-height <?php echo $bbcs_has_pro ? 'bbcs-card-pro-active' : 'bbcs-card-free'; ?>">
	<header class="card-header">
		<div class="card-actions">
			<a href="<?php echo esc_url( $BBCSA->pages_cloud_api ); ?>" class="bbcs-icon-button" data-bs-toggle="tooltip"
				data-bs-html="true" data-bs-placement="top"
				data-bs-original-title="
				<?php
				echo $bbcs_has_pro ? esc_attr__( 'You have PRO activated. Check your plan.', 'botblocker-security' ) : esc_attr__( 'Improve your plan for excellent security protection.', 'botblocker-security' );
				?>
				">
				<i class="bbcs-card-action fa-solid fa-crown <?php echo $bbcs_has_pro ? 'bbcs-cloud-api-color' : ''; ?>"></i>
			</a>
			<a href="<?php echo esc_url( $BBCSA->pages_settings ); ?>" class="bbcs-icon-button"
				data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top"
				data-bs-original-title="<?php esc_attr_e( 'Settings', 'botblocker-security' ); ?>">
				<i class="bbcs-card-action fa-solid fa-gear"></i>
			</a>

			<a href="<?php echo esc_url( $BBCSA->pages_setup ); ?>" class="bbcs-icon-button"
				data-bs-toggle="tooltip" data-bs-html="true" data-bs-placement="top"
				data-bs-original-title="<?php esc_attr_e( 'View full Health Status', 'botblocker-security' ); ?>">
				<i class="bbcs-card-action fa-solid fa-heart"></i>
			</a>

		</div>
		<h2 class="card-title">
			<?php esc_html_e( 'Security score', 'botblocker-security' ); ?>
			<?php if ( $bbcs_has_pro ) : ?>
				<span class="bbcs-pro-badge-header">
					<i class="fa-solid fa-crown"></i> PRO
				</span>
			<?php endif; ?>
		</h2>
	</header>
	<div class="card-body">
		<?php if ( ! $bbcs_wizard_completed ) : ?>
		<div class="bbcs-setup-alert">
			<div class="bbcs-setup-alert-content">
				<div class="bbcs-setup-alert-icon">
					<i class="fa-solid fa-wand-magic-sparkles"></i>
				</div>
				<div class="bbcs-setup-alert-text">
					<strong><?php esc_html_e( 'Setup not completed', 'botblocker-security' ); ?></strong>
					<span><?php esc_html_e( 'Run the wizard to configure protection', 'botblocker-security' ); ?></span>
				</div>
			</div>
			<a href="<?php echo esc_url( BotBlockerMultisite::getSiteAdminPageUrl( 'bbcs_setup_wizard' ) ); ?>" class="bbcs-setup-alert-btn">
				<i class="fa-solid fa-arrow-right"></i>
			</a>
		</div>
		<?php endif; ?>
		
		<div class="bbcs-security-score-wrapper">
			<?php
				echo do_shortcode( '[bbcs_health_gauge id="health_gauge" value="' . $bbcs_health_score . '" max="100" ]' );
			?>
		</div>
		
		<div class="bbcs-status-message <?php echo $bbcs_has_pro ? 'bbcs-status-pro' : 'bbcs-status-free'; ?>">
			<?php if ( $bbcs_has_pro && $bbcs_health_score >= 85 ) : ?>
				<div class="bbcs-status-icon bbcs-status-icon-pro">
					<i class="fa-solid fa-shield"></i>
				</div>
				<div class="bbcs-status-text-wrapper">
					<h4 class="bbcs-status-title">
						<?php esc_html_e( 'Full Protection Active', 'botblocker-security' ); ?>
					</h4>
					<p class="bbcs-status-description">
						<?php esc_html_e( 'Your site is fully protected with BotBlocker PRO. Cloud-based security shields are actively monitoring and blocking advanced threats in real-time.', 'botblocker-security' ); ?>
					</p>
				</div>
			<?php elseif ( $bbcs_has_pro && $bbcs_health_score < 85 ) : ?>
				<div class="bbcs-status-icon bbcs-status-icon-warning">
					<i class="fa-solid fa-triangle-exclamation"></i>
				</div>
				<div class="bbcs-status-text-wrapper">
					<h4 class="bbcs-status-title">
						<?php esc_html_e( 'PRO Active, Security Incomplete', 'botblocker-security' ); ?>
					</h4>
					<p class="bbcs-status-description">
						<?php esc_html_e( 'BotBlocker PRO is active, but some protections are disabled. Enable them for full defense.', 'botblocker-security' ); ?>
					</p>
				</div>
				<a href="<?php echo esc_url( $BBCSA->pages_setup ); ?>" class="bbcs-status-action-btn">
					<?php esc_html_e( 'Protect', 'botblocker-security' ); ?>
				</a>
			<?php else : ?>
				<div class="bbcs-status-icon bbcs-status-icon-warning">
					<i class="fa-solid fa-triangle-exclamation"></i>
				</div>
				<div class="bbcs-status-text-wrapper">
					<h4 class="bbcs-status-title">
						<?php esc_html_e( 'Partial Protection', 'botblocker-security' ); ?>
					</h4>
					<p class="bbcs-status-description">
						<?php esc_html_e( 'Your site is only partially protected. BotBlocker is already blocking basic automated attacks, but several critical shields are still disabled.', 'botblocker-security' ); ?>
					</p>
				</div>
			<?php endif; ?>
		</div>

		<?php if ( ! $bbcs_has_pro ) : ?>
		<div class="bbcs-upgrade-callout">
			<div class="bbcs-upgrade-content">
				<div class="bbcs-upgrade-icon">
					<i class="fa-solid fa-rocket"></i>
				</div>
				<div class="bbcs-upgrade-text">
					<strong><?php esc_html_e( 'Unlock Advanced Protection', 'botblocker-security' ); ?></strong>
					<p><?php esc_html_e( 'Get cloud-powered threat detection, real-time blocking, and enterprise-grade security features.', 'botblocker-security' ); ?></p>
				</div>
				<a href="<?php echo esc_url( $BBCSA->pages_cloud_api ); ?>" class="bbcs-upgrade-callout-btn">
					<?php esc_html_e( 'Read More', 'botblocker-security' ); ?>
				</a>
			</div>
		</div>
		<?php endif; ?>

		<div class="bbcs-action-buttons-health"> <!-- bbcs-action-buttons-->
				<?php if ( $bbcs_wizard_completed ) : ?>
				<a href="<?php echo esc_url( BotBlockerMultisite::getSiteAdminPageUrl( 'bbcs_setup_wizard' ) ); ?>" class="btn btn-sm bbcs-btn-primary-cta">
					<i class="fa-solid fa-rotate"></i>
					<?php esc_html_e( 'Setup Wizard', 'botblocker-security' ); ?>
				</a>
				<?php endif; ?>
				<!--
				<a href="<?php //echo esc_url( $BBCSA->pages_setup ); ?>" class="btn btn-sm btn-info bbcs-btn-action">
					<i class="fa-solid fa-person-running"></i>
					<?php //esc_html_e( 'Setup Guide', 'botblocker-security' ); ?>
				</a>
				-->
				<?php if ( ! $bbcs_has_pro ) : ?>
				<a href="https://botblocker.top/pricing/" class="btn btn-sm bbcs-btn-upgrade" target="_blank" rel="noopener noreferrer">
					<i class="fa-solid fa-star"></i>
					<?php esc_html_e( 'Compare Plans', 'botblocker-security' ); ?>
				</a>
				<?php endif; ?>
				<?php if ( $bbcs_has_pro ) : ?>
				<a href="<?php echo esc_url( $BBCSA->pages_cloud_api ); ?>" class="btn btn-sm bbcs-btn-upgrade" target="_blank" rel="noopener noreferrer">
					<i class="fa-solid fa-star"></i>
					<?php esc_html_e( 'View my PRO', 'botblocker-security' ); ?>
				</a>
				<?php endif; ?>    
		</div>

	</div>
</section>
</div>
