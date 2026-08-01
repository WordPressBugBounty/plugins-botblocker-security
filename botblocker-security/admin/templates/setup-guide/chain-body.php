<?php

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
return static function ( Botblocker_SetupGuideViewModel $data ): void {
	$bbcs_earlyBoxClass  = $data->chain_context->isEarlyActive() ? 'bbcs-bg-lightgreen' : 'bbcs-bg-lightgray';
	$bbcs_muBoxClass     = $data->chain_context->isMuActive() ? 'bbcs-bg-lightgreen' : 'bbcs-bg-lightgray';
	$bbcs_pluginBoxClass = 'bbcs-bg-lightgreen';
	?><div class="guide-chain">
	<div class="bbcs-vertical-stack-box">
		<div class="guide-chain-box <?php echo esc_attr( $bbcs_earlyBoxClass ); ?>">
			<div class="guide-chain-box-icon"><i class="fa-solid fa-arrows-rotate<?php echo esc_attr( $data->chain_context->getEarlySpin() ); ?>"></i></div>
			<span class="guide-chain-span"><?php esc_html_e( 'Early init', 'botblocker-security' ); ?></span>
		</div>
		<div class="guide-chain-box-text">
			<p class="guide-text">
				<?php echo esc_html( $data->chain_context->getEarlyText() ); ?>
				<?php if ( ! $data->early_available ) : ?>
						<a href="<?php echo esc_url( $data->cloud_api_url ); ?>"><?php esc_html_e( 'Connect now!', 'botblocker-security' ); ?></a>
						<?php esc_html_e( 'or', 'botblocker-security' ); ?>
						<a href="<?php echo esc_url( $data->addons_url ); ?>"><?php esc_html_e( 'manage add-ons', 'botblocker-security' ); ?></a>
				<?php endif; ?>
			</p>
		</div>
	</div>
	<div class="guide-chain-arrow"><i class="fa-solid fa-arrow-right-long"></i></div>
	<div class="bbcs-vertical-stack-box">
		<div class="guide-chain-box <?php echo esc_attr( $bbcs_muBoxClass ); ?>">
			<div class="guide-chain-box-icon"><i class="fa-solid fa-arrows-rotate<?php echo esc_attr( $data->chain_context->getMuSpin() ); ?>"></i></div>
			<span class="guide-chain-span"><?php esc_html_e( 'MU plugin', 'botblocker-security' ); ?></span>
		</div>
		<div class="guide-chain-box-text">
			<p class="guide-text"><?php echo esc_html( $data->chain_context->getMuText() ); ?></p>
		</div>
	</div>
	<div class="guide-chain-arrow"><i class="fa-solid fa-arrow-right-long"></i></div>
	<div class="bbcs-vertical-stack-box">
		<div class="guide-chain-box <?php echo esc_attr( $bbcs_pluginBoxClass ); ?>">
			<div class="guide-chain-box-icon"><i class="fa-solid fa-arrows-rotate<?php echo esc_attr( $data->chain_context->getPluginSpin() ); ?>"></i></div>
			<span class="guide-chain-span"><?php esc_html_e( 'BotBlocker', 'botblocker-security' ); ?></span>
		</div>
		<div class="guide-chain-box-text">
			<p class="guide-text"><?php echo esc_html( $data->chain_context->getPluginText() ); ?></p>
		</div>
	</div>
	<div class="bbcs-vertical-stack-box">
		<p class="bbcs-guide-p">
			<?php esc_html_e( 'Early Init and MU plugin modes reject requests at the first gate: IPs on the blacklist (bots, malicious networks, previously blocked) are dropped before heavier logic runs. With Early Init, WordPress does not load at all - saving memory and CPU. The MU plugin filters before regular plugins and the theme. Only clean traffic proceeds; junk is dropped as early as possible.', 'botblocker-security' ); ?>
		</p>
	</div>
	<div class="bbcs-vertical-stack-box">
		<div class="bbcs-chain-video-wrapper">
			<video autoplay muted loop playsinline preload="metadata" class="bbcs-chain-video" controlslist="nodownload noplaybackrate nofullscreen" aria-hidden="true" tabindex="-1">
				<source src="<?php echo esc_url( BOTBLOCKER_URL . 'public/video/early-mu.mp4' ); ?>" type="video/mp4">
				<?php esc_html_e( 'Your browser does not support the video tag.', 'botblocker-security' ); ?>
			</video>
		</div>
	</div>
	</div><?php
};
