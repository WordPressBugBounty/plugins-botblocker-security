<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

$bbcs_has_cloud_api = function_exists('bbcs_isCloudAPIActive') && bbcs_isCloudAPIActive();
$bbcs_cloud_api_url = isset($BBCSA) && !empty($BBCSA->pages_cloud_api ?? '') ? esc_url($BBCSA->pages_cloud_api) : 'https://botblocker.top/pricing/';

?>
<h3 class="bbcs_guide_h3"><?php esc_html_e('PRO Features','botblocker-security'); ?></h3>
<hr class="bbcs-guide-hr">
<div class="bbcs-guide-row row g-3 align-items-stretch mb-4">
  <div class="col-md-6 "> <!--mx-auto -->
    <div class="bbcs-cloud-api-card d-flex flex-column h-100 p-3 rounded-2 position-relative shadow-sm bbcs-color-linen">
      <div class="d-flex justify-content-between align-items-start mb-2">
        <h5 class="m-0 d-flex align-items-center fw-medium">
        <?php
        // REVIEWER NOTE: This image is a static plugin asset, not a user-uploaded Media Library image.
        // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
            <img src="<?php echo esc_url( BOTBLOCKER_URL . 'public/icons/cloud-api.svg' ); ?>" 
            alt="<?php esc_attr_e('BotBlocker PRO', 'botblocker-security'); ?>" 
            class="bbcs-guide-image me-2" />
            <?php esc_html_e('BotBlocker PRO','botblocker-security'); ?></h5>
        <span class="badge <?php echo $bbcs_has_cloud_api ? 'bg-success':'bg-secondary'; ?>">
          <?php echo $bbcs_has_cloud_api ? esc_html__('Active','botblocker-security') : esc_html__('Not Active','botblocker-security'); ?>
        </span>
      </div>
      <p class="small mb-3"><?php esc_html_e('Monthly or annual subscription with cloud intelligence and premium features.','botblocker-security'); ?></p>
        <ul class="bbcs-guide-ul small ps-3 mb-3 bbcs-feature-list">
          <li class="bbcs-guide-li"><?php esc_html_e('Real-time cloud threat checks (bots, bad ASN, dynamic proxies)','botblocker-security'); ?></li>
          <li class="bbcs-guide-li"><?php esc_html_e('Proxy, VPN, and Tor detection','botblocker-security'); ?></li>
          <li class="bbcs-guide-li"><?php esc_html_e('Extended heuristic and behavioral rules','botblocker-security'); ?></li>
          <li class="bbcs-guide-li"><?php esc_html_e('Hide admin login URL','botblocker-security'); ?></li>
          <li class="bbcs-guide-li"><?php esc_html_e('Early init: filtering before WordPress loads','botblocker-security'); ?></li>
          <li class="bbcs-guide-li"><?php esc_html_e('WordPress speed optimization','botblocker-security'); ?></li>
          <li class="bbcs-guide-li"><?php esc_html_e('Automatic signature and AI model updates','botblocker-security'); ?></li>
          <li class="bbcs-guide-li"><?php esc_html_e('Priority support SLA','botblocker-security'); ?></li>
          <li class="bbcs-guide-li"><?php esc_html_e('All official BotBlocker add-ons','botblocker-security'); ?></li>
          <li class="bbcs-guide-li"><?php esc_html_e('And more','botblocker-security'); ?></li>
        </ul>
      <div class="mt-auto d-flex flex-column gap-2">
        <?php if ( ! $bbcs_has_cloud_api ) : ?>
          <div class="bbcs-compare-link-wrapper">
                <a href="<?php echo esc_url($bbcs_cloud_api_url); ?>" class="btn btn-sm bbcs-btn-upgrade me-2" target="_blank" rel="noopener noreferrer">
                    <?php esc_html_e( 'My PRO status', 'botblocker-security' ); ?>
                </a>

				        <a href="https://botblocker.top/pricing/" class="btn btn-sm bbcs-btn-upgrade" target="_blank" rel="noopener noreferrer">
                    <i class="fa-solid fa-star"></i>
                    <?php esc_html_e( 'Compare Plans', 'botblocker-security' ); ?>
                </a>
          </div>
        <?php else : ?>
          <div class="alert alert-success py-2 px-3 small mb-0"><i class="fa-solid fa-circle-check me-1"></i><?php esc_html_e('Cloud API is active. Full protection enabled.','botblocker-security'); ?></div>
        <?php endif; ?>
      </div>
    </div>
  </div>
  <div class="col-12 mt-2">
    <div class="small text-muted fst-italic px-1">
      <i class="fa-regular fa-circle-question me-1"></i><?php esc_html_e('Cloud API provides extended protection with continuous threat intelligence and automation.','botblocker-security'); ?>
    </div>
  </div>
</div>