<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
$bbcs_can_full    = ( class_exists( 'BotBlockerPro' ) && BotBlockerPro::isActive() );
$bbcs_connect_url = isset( $BBCSA ) && ! empty( $BBCSA->pages_cloud_api ?? '' ) ? esc_url( $BBCSA->pages_cloud_api ) : 'https://botblocker.top/pricing/';
?>
<div class="modal fade" id="bbcsOneClickSetupModal" tabindex="-1" aria-labelledby="bbcsOneClickSetupLabel" aria-hidden="true" data-pro="<?php echo class_exists( 'BotBlockerPro' ) && BotBlockerPro::isActive() ? '1' : '0'; ?>">
	<div class="modal-dialog modal-lg modal-dialog-centered">
	<div class="modal-content">
		<div class="modal-header" style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); border: none;">
		<h5 class="modal-title text-white fw-bold" id="bbcsOneClickSetupLabel">
			<i class="fa-solid fa-wand-magic-sparkles me-2"></i><?php esc_html_e( 'One‑Click Security Setup', 'botblocker-security' ); ?>
		</h5>
		<button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="<?php esc_attr_e( 'Close', 'botblocker-security' ); ?>"></button>
		</div>
		<div class="modal-body" style="padding: 24px;">
		<p class="mb-3 fw-semibold text-uppercase small text-muted text-center" style="letter-spacing: 0.5px;"><?php esc_html_e( 'Choose your setup method', 'botblocker-security' ); ?></p>
		
		<div class="row g-3 mb-3">
			<!-- Light Protection -->
			<div class="col-md-3">
			<div class="bbcs-profile-choice card border-0 shadow-sm h-100" data-mode="light" style="background: linear-gradient(135deg, #f3f4f6 0%, #e5e7eb 100%); border-radius: 12px; transition: all 0.3s ease; cursor: pointer;">
				<div class="card-body p-3 d-flex flex-column" style="background-color: #fff9c4;">
				<div class="text-center mb-3">
					<div class="mx-auto mb-2" style="width: 50px; height: 50px; background: white; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; color: #6b7280;">
					<i class="fa-solid fa-feather"></i>
					</div>
					<h6 class="m-0 fw-bold mb-1" style="color: #374151; font-size: 15px;"><?php esc_html_e( 'Light Protection', 'botblocker-security' ); ?></h6>
					<span class="badge" style="background: #6b7280; font-size: 10px; padding: 2px 8px;"><?php esc_html_e( 'Basic', 'botblocker-security' ); ?></span>
				</div>
				<ul class="mb-3 ps-3 small flex-grow-1" style="line-height: 1.3; color: #4b5563; list-style: disc;">
					<li style="margin-bottom: 3px;"><?php esc_html_e( 'Minimal protection', 'botblocker-security' ); ?></li>
					<li style="margin-bottom: 3px;"><?php esc_html_e( 'Maximum compatibility', 'botblocker-security' ); ?></li>
					<li style="margin-bottom: 3px;"><?php esc_html_e( 'Testing and debug mode', 'botblocker-security' ); ?></li>
					<li style="margin-bottom: 3px;"><?php esc_html_e( 'Low resource usage', 'botblocker-security' ); ?></li>
				</ul>
				<button type="button" class="btn btn-sm w-100 bbcs-apply-profile mt-auto" data-mode="light" style="background: #6b7280; color: white; border: none; font-weight: 500; height: 40px; padding: 0 10px; border-radius: 8px; transition: all 0.2s ease; display: inline-flex; align-items: center; justify-content: center;">
					<span class="bbcs-btn-text"><?php esc_html_e( 'Apply Now', 'botblocker-security' ); ?></span>
					<span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
				</button>
				</div>
			</div>
			</div>

			<!-- Strong Protection -->
			<div class="col-md-3">
			<div class="bbcs-profile-choice card border-0 shadow-sm h-100" data-mode="strong" style="background: linear-gradient(135deg, #e0f2fe 0%, #bfdbfe 100%); border-radius: 12px; transition: all 0.3s ease; cursor: pointer;">
				<div class="card-body p-3 d-flex flex-column" style="background-color: #fff9c4;">
				<div class="text-center mb-3">
					<div class="mx-auto mb-2" style="width: 50px; height: 50px; background: white; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; color: #0ea5e9;">
					<i class="fa-solid fa-shield-halved"></i>
					</div>
					<h6 class="m-0 fw-bold mb-1" style="color: #0c4a6e; font-size: 15px;"><?php esc_html_e( 'Strong Protection', 'botblocker-security' ); ?></h6>
					<span class="badge" style="background: #0ea5e9; font-size: 10px; padding: 2px 8px;"><?php esc_html_e( 'Balanced', 'botblocker-security' ); ?></span>
				</div>
				<ul class="mb-3 ps-3 small flex-grow-1" style="line-height: 1.3; color: #075985; list-style: disc;">
					<li style="margin-bottom: 3px;"><?php esc_html_e( 'Balanced security and compatibility', 'botblocker-security' ); ?></li>
					<li style="margin-bottom: 3px;"><?php esc_html_e( 'Blocks common threats', 'botblocker-security' ); ?></li>
					<li style="margin-bottom: 3px;"><?php esc_html_e( 'Safe defaults', 'botblocker-security' ); ?></li>
					<li style="margin-bottom: 3px;"><?php esc_html_e( 'One-click activation', 'botblocker-security' ); ?></li>
				</ul>
				<button type="button" class="btn btn-sm w-100 bbcs-apply-profile mt-auto" id="bbcsApplyStrongSetup" data-mode="strong" style="background: #0ea5e9; color: white; border: none; font-weight: 500; height: 40px; padding: 0 10px; border-radius: 8px; transition: all 0.2s ease; display: inline-flex; align-items: center; justify-content: center;">
					<span class="bbcs-btn-text"><?php esc_html_e( 'Apply Now', 'botblocker-security' ); ?></span>
					<span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
				</button>
				</div>
			</div>
			</div>

			<!-- Full Protection -->
			<div class="col-md-3">
			<div class="bbcs-profile-choice card border-0 shadow-sm position-relative h-100" data-mode="full" style="background: linear-gradient(135deg, #d1fae5 0%, #a7f3d0 100%); border-radius: 12px; transition: all 0.3s ease; cursor: pointer;<?php echo ! $bbcs_can_full ? ' pointer-events: none;' : ''; ?>">
				<?php if ( ! $bbcs_can_full ) : ?>
				<div class="position-absolute top-0 start-0 w-100 h-100 d-flex flex-column justify-content-center align-items-center text-center p-3" style="z-index: 10; background: rgba(255, 255, 255, 0.95); border-radius: 12px; backdrop-filter: blur(4px);">
					<div style="font-size: 32px; color: #f59e0b; margin-bottom: 8px;">
					<i class="fa-solid fa-lock"></i>
					</div>
					<p class="fw-bold mb-1" style="color: #1f2937; font-size: 14px;"><?php esc_html_e( 'Strongest Protection Mode', 'botblocker-security' ); ?></p>
					<p class="mb-2 small" style="color: #6b7280; font-size: 12px;"><?php esc_html_e( 'Requires PRO', 'botblocker-security' ); ?></p>
					<a href="<?php echo esc_url( $bbcs_connect_url ); ?>" class="btn btn-sm bbcs-btn-upgrade"><i class="fa-solid fa-rocket me-1"></i><?php esc_html_e( 'Get PRO', 'botblocker-security' ); ?></a>
				</div>
				<?php endif; ?>
				<div class="card-body p-3 d-flex flex-column" style="background-color: #fff9c4;">
				<div class="text-center mb-3">
					<div class="mx-auto mb-2" style="width: 50px; height: 50px; background: white; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; color: #10b981;">
					<i class="fa-solid fa-shield"></i>
					</div>
					<h6 class="m-0 fw-bold mb-1" style="color: #065f46; font-size: 15px;"><?php esc_html_e( 'Full Protection', 'botblocker-security' ); ?></h6>
					<span class="badge" style="background: #10b981; font-size: 10px; padding: 2px 8px;"><?php esc_html_e( 'PRO', 'botblocker-security' ); ?></span>
				</div>
				<ul class="mb-3 ps-3 small flex-grow-1" style="line-height: 1.3; color: #047857; list-style: disc;">
					<li style="margin-bottom: 3px;"><?php esc_html_e( 'Strongest protection mode', 'botblocker-security' ); ?></li>
					<li style="margin-bottom: 3px;"><?php esc_html_e( 'Maximum security hardening', 'botblocker-security' ); ?></li>
					<li style="margin-bottom: 3px;"><?php esc_html_e( 'Cloud threat intelligence', 'botblocker-security' ); ?></li>
					<li style="margin-bottom: 3px;"><?php esc_html_e( 'Advanced bot detection', 'botblocker-security' ); ?></li>
				</ul>
				<button type="button" class="btn btn-sm w-100 bbcs-apply-profile mt-auto" id="bbcsApplyFullSetup" data-mode="full" <?php echo ! $bbcs_can_full ? 'disabled' : ''; ?> style="background: #10b981; color: white; border: none; font-weight: 500; height: 40px; padding: 0 10px; border-radius: 8px; transition: all 0.2s ease; display: inline-flex; align-items: center; justify-content: center;<?php echo ! $bbcs_can_full ? ' opacity: 0.5; cursor: not-allowed;' : ''; ?>">
					<span class="bbcs-btn-text"><?php esc_html_e( 'Apply Now', 'botblocker-security' ); ?></span>
					<span class="spinner-border spinner-border-sm d-none" role="status" aria-hidden="true"></span>
				</button>
				</div>
			</div>
			</div>

			<!-- Full Setup Wizard -->
			<div class="col-md-3">
			<div class="card border-0 shadow-sm h-100" style="background: linear-gradient(135deg, #fef3c7 0%, #fde68a 100%); border-radius: 12px; transition: all 0.3s ease; cursor: pointer;">
				<div class="card-body p-3 d-flex flex-column" style="background-color: #fff;">
				<div class="text-center mb-3" style="min-height: 105px; display: flex; flex-direction: column; align-items: center; justify-content: center;">
					<div class="mx-auto mb-2" style="width: 50px; height: 50px; background: white; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 24px; color: #f59e0b;">
					<i class="fa-solid fa-list-check"></i>
					</div>
					<h6 class="m-0 fw-bold mb-1" style="color: #92400e; font-size: 15px;"><?php esc_html_e( 'Full Setup Wizard', 'botblocker-security' ); ?></h6>
				</div>
				<ul class="mb-3 ps-3 small flex-grow-1" style="line-height: 1.3; color: #b45309; list-style: disc;">
					<li style="margin-bottom: 3px;"><?php esc_html_e( '7-step guided setup', 'botblocker-security' ); ?></li>
					<li style="margin-bottom: 3px;"><?php esc_html_e( 'Compatibility tests', 'botblocker-security' ); ?></li>
					<li style="margin-bottom: 3px;"><?php esc_html_e( 'Configure exclusions', 'botblocker-security' ); ?></li>
					<li style="margin-bottom: 3px;"><?php esc_html_e( 'Review security score', 'botblocker-security' ); ?></li>
				</ul>
				<a href="<?php echo esc_url( BotBlockerMultisite::getSiteAdminPageUrl( 'bbcs_setup_wizard' ) ); ?>" class="btn btn-sm w-100 mt-auto" style="background: #f59e0b; color: white; border: none; font-weight: 500; height: 40px; padding: 0 10px; border-radius: 8px; transition: all 0.2s ease; text-decoration: none; display: inline-flex; align-items: center; justify-content: center;">
					<i class="fa-solid fa-wand-magic-sparkles me-1"></i><?php esc_html_e( 'Start Wizard', 'botblocker-security' ); ?>
				</a>
				</div>
			</div>
			</div>
		</div>
		</div>
	</div>
	</div>
</div>
