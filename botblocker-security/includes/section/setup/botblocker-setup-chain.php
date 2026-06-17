<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
$bbcs_ctx            = BotBlockerUI::get_setup_chain_context();
$bbcs_earlySpin      = $bbcs_ctx['earlySpin'];
$bbcs_muSpin         = $bbcs_ctx['muSpin'];
$bbcs_pluginSpin     = $bbcs_ctx['pluginSpin'];
$bbcs_earlyText      = $bbcs_ctx['earlyText'];
$bbcs_muText         = $bbcs_ctx['muText'];
$bbcs_pluginText     = $bbcs_ctx['pluginText'];
$bbcs_earlyActive    = ! empty( $bbcs_earlySpin );
$bbcs_muActive       = ! empty( $bbcs_muSpin );
$bbcs_pluginActive   = true;
$bbcs_earlyBoxClass  = $bbcs_earlyActive ? 'bbcs-bg-lightgreen' : 'bbcs-bg-lightgray';
$bbcs_muBoxClass     = $bbcs_muActive ? 'bbcs-bg-lightgreen' : 'bbcs-bg-lightgray';
$bbcs_pluginBoxClass = $bbcs_pluginActive ? 'bbcs-bg-lightgreen' : 'bbcs-bg-lightgray';

$BBCSA                    = Botblocker_Admin::getInstance();
$bbcs_is_cloud_api_active = ( class_exists( 'BotBlockerPro' ) && BotBlockerPro::isActive() );
$bbcs_early_addon_active  = class_exists( 'BotBlockerAddons' ) ? BotBlockerAddons::hasActiveProvider( 'early_init_provider', 'bbcs_early_init_provider_active' ) : false;
$bbcs_early_available     = $bbcs_is_cloud_api_active && $bbcs_early_addon_active;
?>
<h3 class="bbcs_guide_h3"><?php esc_html_e( 'Request Handling Chain', 'botblocker-security' ); ?></h3>
<hr class="bbcs-guide-hr">
<div class="bbcs-guide-row mb-4">
	<div class="guide-chain">
		<div class="bbcs-vertical-stack-box">
			<div class="guide-chain-box <?php echo esc_attr( $bbcs_earlyBoxClass ); ?>">
				<div class="guide-chain-box-icon"><i class="fa-solid fa-arrows-rotate<?php echo esc_attr( $bbcs_earlySpin ); ?>"></i></div>
				<span class="guide-chain-span"><?php esc_html_e( 'Early init', 'botblocker-security' ); ?></span>
			</div>
			<div class="guide-chain-box-text">
				<p class="guide-text">
					<?php echo esc_html( $bbcs_earlyText ); ?>
					<?php if ( ! $bbcs_early_available ) : ?>
							<a href="<?php echo esc_url( $BBCSA->pages_cloud_api ); ?>"><?php esc_html_e( 'Connect now!', 'botblocker-security' ); ?></a>
							<?php esc_html_e( 'or', 'botblocker-security' ); ?>
							<a href="<?php echo esc_url( $BBCSA->pages_addons ); ?>"><?php esc_html_e( 'manage add-ons', 'botblocker-security' ); ?></a>
					<?php endif; ?>                    
				</p>
			</div>
		</div>
		<div class="guide-chain-arrow"><i class="fa-solid fa-arrow-right-long"></i></div>
		<div class="bbcs-vertical-stack-box">
			<div class="guide-chain-box <?php echo esc_attr( $bbcs_muBoxClass ); ?>">
				<div class="guide-chain-box-icon"><i class="fa-solid fa-arrows-rotate<?php echo esc_attr( $bbcs_muSpin ); ?>"></i></div>
				<span class="guide-chain-span"><?php esc_html_e( 'MU plugin', 'botblocker-security' ); ?></span>
			</div>
			<div class="guide-chain-box-text">
				<p class="guide-text"><?php echo esc_html( $bbcs_muText ); ?></p>
			</div>
		</div>
		<div class="guide-chain-arrow"><i class="fa-solid fa-arrow-right-long"></i></div>
		<div class="bbcs-vertical-stack-box">
			<div class="guide-chain-box <?php echo esc_attr( $bbcs_pluginBoxClass ); ?>">
				<div class="guide-chain-box-icon"><i class="fa-solid fa-arrows-rotate<?php echo esc_attr( $bbcs_pluginSpin ); ?>"></i></div>
				<span class="guide-chain-span"><?php esc_html_e( 'BotBlocker', 'botblocker-security' ); ?></span>
			</div>
			<div class="guide-chain-box-text">
				<p class="guide-text"><?php echo esc_html( $bbcs_pluginText ); ?></p>
			</div>
		</div>
		<div class="bbcs-vertical-stack-box">
			<p class="bbcs-guide-p">
				<?php esc_html_e( 'Early Init and MU plugin modes reject requests at the first gate: IPs on the blacklist (bots, malicious networks, previously blocked) are dropped before heavier logic runs. With Early Init, WordPress does not load at all - saving memory and CPU. The MU plugin filters before regular plugins and the theme. Only clean traffic proceeds; junk is dropped as early as possible.', 'botblocker-security' ); ?>
			</p>
		</div>
		<div class="bbcs-vertical-stack-box">
			<div class="bbcs-chain-video-wrapper">
				<!-- <video controls preload="metadata" class="bbcs-chain-video"> -->
				<video autoplay muted loop playsinline preload="metadata" class="bbcs-chain-video" controlslist="nodownload noplaybackrate nofullscreen" aria-hidden="true" tabindex="-1">
					<!--<source src="
					<?php
					//echo esc_url( BOTBLOCKER_MATERIALS_URL . 'video/early-mu.mp4' );
					?>
					" type="video/mp4">-->
					<source src="<?php echo esc_url( BOTBLOCKER_URL . 'public/video/early-mu.mp4' ); ?>" type="video/mp4">
					<?php esc_html_e( 'Your browser does not support the video tag.', 'botblocker-security' ); ?>
				</video>
			</div>
		</div>
	</div>
</div>
