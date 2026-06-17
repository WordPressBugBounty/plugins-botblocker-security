<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

class BotBlocker_SetupWizardRenderer {

	public function render_wizard_content(): void {
		// phpcs:disable WordPress.Security.EscapeOutput.OutputNotEscaped
		$inline_logo_image = BOTBLOCKER_URL . 'admin/img/logo-small-transparent.webp';
		$dashboard_url     = BotBlockerMultisite::getSiteAdminPageUrl( 'bbcs_dashboard' );
		$wizard_completed  = BotBlockerMultisite::getOption( 'bbcs_setup_wizard_completed', false );

		if ( $wizard_completed ) {
			?>
			<div class="bbcs-wizard-container">
				<div class="bbcs-wizard-header">
					<div class="bbcs-wizard-logo">
						<img src="<?php echo esc_attr( $inline_logo_image ); ?>" alt="<?php esc_attr_e( 'BotBlocker Security', 'botblocker-security' ); ?>">
					</div>
				</div>
				<div class="bbcs-wizard-body">
					<div class="bbcs-wizard-step-content text-center">
						<i class="fa-solid fa-check-circle fa-4x text-success mb-4"></i>
						<h2 class="bbcs-wizard-title"><?php esc_html_e( 'Setup Already Completed', 'botblocker-security' ); ?></h2>
						<p class="mb-4"><?php esc_html_e( 'Setup is complete. You can adjust settings anytime from the Dashboard.', 'botblocker-security' ); ?></p>
						<div class="bbcs-wizard-actions">
							<a href="<?php echo esc_url( $dashboard_url ); ?>" class="btn btn-primary">
								<?php esc_html_e( 'Go to Dashboard', 'botblocker-security' ); ?>
							</a>
							<a href="<?php echo esc_url( BotBlockerMultisite::getSiteAdminPageUrl( 'bbcs_settings' ) ); ?>" class="btn btn-secondary">
								<?php esc_html_e( 'Settings', 'botblocker-security' ); ?>
							</a>
						<button type="button" class="btn btn-outline-primary" id="bbcs-wizard-reset-btn">
							<i class="fa-solid fa-rotate"></i>
							<?php esc_html_e( 'Reset and Restart Wizard', 'botblocker-security' ); ?>
						</button>
					</div>
					<script>
					jQuery(document).ready(function($) {
						$('#bbcs-wizard-reset-btn').on('click', function() {
							if (!confirm('<?php esc_html_e( 'Are you sure you want to reset and restart the setup wizard?', 'botblocker-security' ); ?>')) return;
							$(this).prop('disabled', true).html('<i class="fa-solid fa-spinner fa-spin"></i> Resetting...');
							$.ajax({
								url: '<?php echo admin_url( 'admin-ajax.php' ); ?>',
								type: 'POST',
								data: {
									action: 'bbcs_wizard_reset',
									nonce: '<?php echo wp_create_nonce( 'bbcs-wizard-admin-nonce' ); ?>'
								},
								success: function() {
									try {
										localStorage.removeItem('bbcs_wizard_progress');
										localStorage.removeItem('bbcs_wizard_contact_email');
									} catch (error) {}
									window.location.reload();
								}
							});
						});
					});
					</script>
					</div>
				</div>
			</div>
			<?php
			return;
		}
		?>
		<div class="bbcs-wizard-container">
			<div class="bbcs-wizard-header">
				<div class="bbcs-wizard-logo">
					<img src="<?php echo esc_attr( $inline_logo_image ); ?>" alt="<?php esc_attr_e( 'BotBlocker Security', 'botblocker-security' ); ?>">
				</div>
				<div class="bbcs-wizard-exit">
					<a href="<?php echo esc_url( $dashboard_url ); ?>" class="bbcs-wizard-exit-link">
						<i class="fa-solid fa-xmark"></i>
						<?php esc_html_e( 'Exit Setup', 'botblocker-security' ); ?>
					</a>
				</div>
			</div>
			
			<div class="bbcs-wizard-progress">
				<div class="bbcs-wizard-progress-bar">
					<div class="bbcs-wizard-progress-fill" style="width: 0%"></div>
				</div>
				<div class="bbcs-wizard-progress-text">
					<span class="bbcs-wizard-current-step">1</span> / 
					<span class="bbcs-wizard-total-steps">8</span>
				</div>
			</div>
			
			<div class="bbcs-wizard-body">
				
				<!-- Step 0: Welcome -->
				<div class="bbcs-wizard-step" data-step="0">
					<div class="bbcs-wizard-step-content">
						<h2 class="bbcs-wizard-title"><?php esc_html_e( 'Welcome to BotBlocker Security', 'botblocker-security' ); ?></h2>
						<div class="bbcs-wizard-features">
							<div class="bbcs-wizard-feature">
								<i class="fa-solid fa-shield"></i>
								<span><?php esc_html_e( 'Blocks brute-force, scanners and scrapers before they hit WordPress', 'botblocker-security' ); ?></span>
							</div>
							<div class="bbcs-wizard-feature">
								<i class="fa-solid fa-network-wired"></i>
								<span><?php esc_html_e( 'No DNS/NS changes: works inside WordPress, no external services required', 'botblocker-security' ); ?></span>
							</div>
							<div class="bbcs-wizard-feature">
								<i class="fa-solid fa-user-shield"></i>
								<span><?php esc_html_e( 'Real-time visitor statistics (IP, PTR, ASN, geo, device, OS, etc.)', 'botblocker-security' ); ?></span>
							</div>
							<div class="bbcs-wizard-feature">
								<i class="fa-solid fa-wand-magic-sparkles"></i>
								<span><?php esc_html_e( 'Apply the recommended preset in one click (takes ~30 seconds)', 'botblocker-security' ); ?></span>
							</div>
							<div class="bbcs-wizard-feature">
								<i class="fa-solid fa-globe"></i>
								<span><?php esc_html_e( 'Stops hosting bots, proxies and Tor traffic', 'botblocker-security' ); ?></span>
							</div>
							<div class="bbcs-wizard-feature">
								<i class="fa-solid fa-bolt"></i>
								<span><?php esc_html_e( 'Cuts server load by blocking junk requests early', 'botblocker-security' ); ?></span>
							</div>
							<div class="bbcs-wizard-feature">
								<i class="fa-solid fa-robot"></i>
								<span><?php esc_html_e( 'Verifies search engines to protect SEO. Detects fake crawlers', 'botblocker-security' ); ?></span>
							</div>
							<div class="bbcs-wizard-feature">
								<i class="fa-solid fa-lock"></i>
								<span><?php esc_html_e( 'Early protection mode: blocks bad requests before WordPress loads', 'botblocker-security' ); ?></span>
							</div>
							<div class="bbcs-wizard-feature">
								<i class="fa-solid fa-eye-slash"></i>
								<span><?php esc_html_e( 'Hide wp-login and stop login abuse (optional add-on)', 'botblocker-security' ); ?></span>
							</div>
							<div class="bbcs-wizard-feature">
								<i class="fa-solid fa-chart-line"></i>
								<span><?php esc_html_e( 'Shows exactly what was blocked and why (reason + URL + IP)', 'botblocker-security' ); ?></span>
							</div>
							<div class="bbcs-wizard-feature">
								<i class="fa-solid fa-sliders"></i>
								<span><?php esc_html_e( 'Custom rules: IP, User-Agent, PTR, country, paths and more', 'botblocker-security' ); ?></span>
							</div>
							<div class="bbcs-wizard-feature">
								<i class="fa-solid fa-cloud-arrow-up"></i>
								<span><?php esc_html_e( 'Daily threat updates from Cloud (signatures + bad networks)', 'botblocker-security' ); ?></span>
							</div>
							<div class="bbcs-wizard-feature">
								<i class="fa-solid fa-heart"></i>
								<span><?php esc_html_e( 'Safe defaults: works with WooCommerce, Elementor and most caches', 'botblocker-security' ); ?></span>
							</div>
						</div>

						<?php if ( (int) BotBlockerMultisite::getOption( 'bbcs_contact_email_collected', 0 ) !== 1 ) : ?>
						<section class="bbcs-email-card">
							<h3 class="bbcs-email-card-title">
								<?php esc_html_e( 'Security Updates and Offers', 'botblocker-security' ); ?>
							</h3>
							<div class="bbcs-email-card-form">
								<input
									id="bbcs-wizard-contact-email"
									class="form-control"
									value="<?php echo esc_attr( BotBlockerInstall::getCloudAPIEmail() ); ?>"
									placeholder="you@example.com"
									autocomplete="email"
								>
								<p class="bbcs-email-card-hint">
									<?php esc_html_e( 'To receive important security news and special offers.', 'botblocker-security' ); ?>
								</p>
							</div>
						</section>
						<?php endif; ?>
						
						<div class="bbcs-wizard-actions">
							<button class="btn btn-primary bbcs-wizard-next" data-next-step="1">
								<?php esc_html_e( 'Start setup', 'botblocker-security' ); ?>
							</button>
							<button class="btn btn-secondary bbcs-wizard-skip">
								<?php esc_html_e( 'Skip (use default)', 'botblocker-security' ); ?>
							</button>
						</div>
					</div>
				</div>
				
				<!-- Step 1: Choose Protection Level -->
				<div class="bbcs-wizard-step" data-step="1">
					<div class="bbcs-wizard-step-content">
						<h2 class="bbcs-wizard-title"><?php esc_html_e( 'Choose Your Protection Level', 'botblocker-security' ); ?></h2>
						<div class="bbcs-wizard-presets">
							<div class="bbcs-wizard-preset" data-preset="light">
								<div class="bbcs-wizard-preset-icon">
									<i class="fa-solid fa-feather"></i>
								</div>
								<h3><?php esc_html_e( 'Light Protection', 'botblocker-security' ); ?></h3>
								<p class="bbcs-preset-tagline"><?php esc_html_e( 'Low impact, works on any site', 'botblocker-security' ); ?></p>
								<ul class="bbcs-preset-features">
									<li><?php esc_html_e( 'Basic bot blocking', 'botblocker-security' ); ?></li>
									<li><?php esc_html_e( 'Zero impact on visitors', 'botblocker-security' ); ?></li>
									<li><?php esc_html_e( 'Perfect for testing', 'botblocker-security' ); ?></li>
									<li><?php esc_html_e( 'Compatible with all plugins', 'botblocker-security' ); ?></li>
								</ul>
							</div>
							<div class="bbcs-wizard-preset" data-preset="strong">
								<div class="bbcs-wizard-preset-icon">
									<i class="fa-solid fa-shield-halved"></i>
								</div>
								<h3><?php esc_html_e( 'Strong Protection', 'botblocker-security' ); ?> 
								</h3>
								<p class="bbcs-preset-tagline"><?php esc_html_e( 'Optimal balance of security and usability', 'botblocker-security' ); ?></p>
								<ul class="bbcs-preset-features">
									<li><?php esc_html_e( 'Advanced threat detection', 'botblocker-security' ); ?></li>
									<li><?php esc_html_e( 'Blocks most known bad bots', 'botblocker-security' ); ?></li>
									<li><?php esc_html_e( 'Real visitors unaffected', 'botblocker-security' ); ?></li>
									<li><?php esc_html_e( 'Recommended for most sites', 'botblocker-security' ); ?></li>
								</ul>
							</div>
							<div class="bbcs-wizard-preset bbcs-preset-premium" data-preset="full">
								<div class="bbcs-wizard-preset-icon">
									<i class="fa-solid fa-shield"></i>
								</div>
								<h3><?php esc_html_e( 'Full Protection', 'botblocker-security' ); ?>
									<?php if ( ! BotBlockerPro::isActive() ) : ?>
									<i class="fa-solid fa-crown" style="color: #f59e0b; font-size: 14px; margin-left: 4px;"></i>
									<?php endif; ?>
								</h3>
								<p class="bbcs-preset-tagline"><?php esc_html_e( 'Maximum protection with PRO features', 'botblocker-security' ); ?></p>
								<ul class="bbcs-preset-features">
									<li><?php esc_html_e( 'Early init - blocks before WP loads', 'botblocker-security' ); ?></li>
									<li><?php esc_html_e( 'Zero-day botnet updates', 'botblocker-security' ); ?></li>
									<li><?php esc_html_e( 'WordPress acceleration and optimization', 'botblocker-security' ); ?></li>
									<li><?php esc_html_e( 'All add-ons included (tools, security, notifications, etc.)', 'botblocker-security' ); ?></li>
									<li><?php esc_html_e( '5 million+ bot signatures', 'botblocker-security' ); ?></li>
									<li><?php esc_html_e( 'Emergency support (24-hour resolution time)', 'botblocker-security' ); ?></li>
									<li><?php esc_html_e( 'AI Behavioral analysis engine', 'botblocker-security' ); ?></li>
									<li style="color: #f59e0b; font-weight: 600;"><?php esc_html_e( 'Requires PRO license', 'botblocker-security' ); ?></li>
								</ul>
								<?php if ( ! BotBlockerPro::isActive() ) : ?>
								<div class="bbcs-preset-pro-overlay">
									<div class="bbcs-pro-badge-top">
										<i class="fa-solid fa-lock"></i>
										<span><?php esc_html_e( 'Only for PRO', 'botblocker-security' ); ?></span>
									</div>
									<a href="https://botblocker.top/pricing/" target="_blank" class="bbcs-pro-badge-bottom">
										<?php esc_html_e( 'See plans', 'botblocker-security' ); ?>
									</a>
								</div>
								<?php endif; ?>
							</div>
						</div>
						<p class="bbcs-wizard-hint"><?php esc_html_e( 'You can change this anytime with one click.', 'botblocker-security' ); ?></p>
						<div class="bbcs-wizard-actions">
							<button class="btn btn-secondary bbcs-wizard-back">
								<i class="fa-solid fa-arrow-left"></i>
								<?php esc_html_e( 'Back', 'botblocker-security' ); ?>
							</button>
							<button class="btn btn-primary bbcs-wizard-apply-preset" disabled>
								<?php esc_html_e( 'Apply preset', 'botblocker-security' ); ?>
							</button>
						</div>
					</div>
				</div>
				
				<!-- Step 2: Compatibility Test -->
				<div class="bbcs-wizard-step" data-step="2">
					<div class="bbcs-wizard-step-content">
						<h2 class="bbcs-wizard-title"><?php esc_html_e( 'Compatibility Check', 'botblocker-security' ); ?></h2>
						<p class="text-center text-muted mb-4"><?php esc_html_e( 'Running compatibility tests for your site', 'botblocker-security' ); ?></p>
						
						<div class="bbcs-wizard-tests">
							<div class="bbcs-wizard-test" data-test="homepage">
								<span class="bbcs-wizard-test-name"><?php esc_html_e( 'Homepage Access', 'botblocker-security' ); ?></span>
								<span class="bbcs-wizard-test-status">
									<i class="fa-solid fa-spinner fa-spin"></i>
								</span>
							</div>
							<div class="bbcs-wizard-test" data-test="admin">
								<span class="bbcs-wizard-test-name"><?php esc_html_e( 'Admin Panel', 'botblocker-security' ); ?></span>
								<span class="bbcs-wizard-test-status">
									<i class="fa-solid fa-spinner fa-spin"></i>
								</span>
							</div>
							<div class="bbcs-wizard-test" data-test="login">
								<span class="bbcs-wizard-test-name"><?php esc_html_e( 'Login Page', 'botblocker-security' ); ?></span>
								<span class="bbcs-wizard-test-status">
									<i class="fa-solid fa-spinner fa-spin"></i>
								</span>
							</div>
							<div class="bbcs-wizard-test" data-test="rest">
								<span class="bbcs-wizard-test-name"><?php esc_html_e( 'REST API', 'botblocker-security' ); ?></span>
								<span class="bbcs-wizard-test-status">
									<i class="fa-solid fa-spinner fa-spin"></i>
								</span>
							</div>
						</div>
						
						<div class="bbcs-wizard-test-warnings" style="display:none;">
							<div class="alert alert-warning">
								<h4><i class="fa-solid fa-triangle-exclamation me-2"></i><?php esc_html_e( 'Minor compatibility issues detected', 'botblocker-security' ); ?></h4>
								<p class="small mb-3"><?php esc_html_e( 'These can be fixed automatically. Your site will stay functional.', 'botblocker-security' ); ?></p>
								<div class="bbcs-wizard-actions mt-3">
									<button class="btn btn-primary bbcs-wizard-fix-auto">
										<i class="fa-solid fa-wand-magic-sparkles me-1"></i>
										<?php esc_html_e( 'Auto-Fix (Recommended)', 'botblocker-security' ); ?>
									</button>
									<button class="btn btn-secondary bbcs-wizard-fix-manual">
										<?php esc_html_e( 'Fix Manually', 'botblocker-security' ); ?>
									</button>
								</div>
							</div>
						</div>
						
						<div class="bbcs-wizard-test-success" style="display:none;">
							<div class="alert alert-success">
								<i class="fa-solid fa-circle-check me-2"></i>
								<strong><?php esc_html_e( 'Perfect!', 'botblocker-security' ); ?></strong>
								<?php esc_html_e( 'All tests passed. Your site is ready.', 'botblocker-security' ); ?>
							</div>
							
							<?php if ( class_exists( 'WooCommerce' ) ) : ?>
							<div class="alert alert-info">
								<i class="fa-solid fa-shopping-cart me-2"></i>
								<strong><?php esc_html_e( 'WooCommerce Detected', 'botblocker-security' ); ?></strong>
								<p class="mb-2 small"><?php esc_html_e( 'Your store is compatible with BotBlocker.', 'botblocker-security' ); ?></p>
								<p class="mb-0 small">
									<i class="fa-solid fa-info-circle me-1"></i>
									<?php esc_html_e( 'For payment issues, see our', 'botblocker-security' ); ?>
									<a href="<?php echo esc_url( BOTBLOCKER_DOCS_URL ); ?>/how-to-properly-configure-botblocker-protection-for-your-woocommerce-store/" target="_blank" rel="noopener">
										<?php esc_html_e( 'WooCommerce configuration guide', 'botblocker-security' ); ?>
									</a>.
								</p>
							</div>
							<?php endif; ?>
							
							<div class="bbcs-wizard-actions">
								<button class="btn btn-secondary bbcs-wizard-back">
									<i class="fa-solid fa-arrow-left"></i>
									<?php esc_html_e( 'Back', 'botblocker-security' ); ?>
								</button>
								<button class="btn btn-primary bbcs-wizard-next" data-next-step="3">
									<?php esc_html_e( 'Continue', 'botblocker-security' ); ?>
									<i class="fa-solid fa-arrow-right ms-1"></i>
								</button>
							</div>
						</div>
					</div>
				</div>
				
				<!-- Step 3: Exclusions -->
				<div class="bbcs-wizard-step" data-step="3">
					<div class="bbcs-wizard-step-content">
						<h2 class="bbcs-wizard-title"><?php esc_html_e( 'Who Should Always Have Access?', 'botblocker-security' ); ?></h2>
						<p class="text-center text-muted mb-4"><?php esc_html_e( 'Configure trusted users and systems that bypass security checks', 'botblocker-security' ); ?></p>
						
						<div class="bbcs-wizard-exclusions">
							<div class="form-check">
								<input class="form-check-input" type="checkbox" id="exclude-admins" checked>
								<label class="form-check-label" for="exclude-admins">
									<strong><?php esc_html_e( 'Allow logged-in administrators', 'botblocker-security' ); ?></strong>
									<span class="d-block small text-muted"><?php esc_html_e( 'Admins will never be blocked while logged in', 'botblocker-security' ); ?></span>
								</label>
							</div>
							<div class="form-check">
								<input class="form-check-input" type="checkbox" id="exclude-current-ip" checked>
								<label class="form-check-label" for="exclude-current-ip">
									<strong><?php esc_html_e( 'Whitelist your current IP', 'botblocker-security' ); ?> 
									<span class="bbcs-wizard-current-ip"></span></strong>
									<span class="d-block small text-muted"><?php esc_html_e( 'Your IP address will be permanently trusted', 'botblocker-security' ); ?></span>
								</label>
							</div>
							<div class="form-check">
								<input class="form-check-input" type="checkbox" id="exclude-cron" checked>
								<label class="form-check-label" for="exclude-cron">
									<strong><?php esc_html_e( 'Allow WordPress Cron and server requests', 'botblocker-security' ); ?></strong>
									<span class="d-block small text-muted"><?php esc_html_e( 'Essential for scheduled tasks and backups', 'botblocker-security' ); ?></span>
								</label>
							</div>
						</div>
						
						<div class="alert alert-info mt-3" style="font-size: 13px;">
							<i class="fa-solid fa-lightbulb me-1"></i>
							<strong><?php esc_html_e( 'Pro Tip:', 'botblocker-security' ); ?></strong>
							<?php esc_html_e( 'Auto-save of admin IPs is available in Settings. 2FA per role can be configured in Integrations.', 'botblocker-security' ); ?>
						</div>
						
						<div class="bbcs-wizard-actions">
							<button class="btn btn-secondary bbcs-wizard-back">
								<i class="fa-solid fa-arrow-left"></i>
								<?php esc_html_e( 'Back', 'botblocker-security' ); ?>
							</button>
							<button class="btn btn-primary bbcs-wizard-next" data-next-step="4">
								<?php esc_html_e( 'Continue', 'botblocker-security' ); ?>
								<i class="fa-solid fa-arrow-right ms-1"></i>
							</button>
						</div>
					</div>
				</div>
				
<!-- Step 4: Verification Method -->
			<div class="bbcs-wizard-step" data-step="4">
				<div class="bbcs-wizard-step-content">
					<h2 class="bbcs-wizard-title"><?php esc_html_e( 'How Should We Verify Suspicious Visitors?', 'botblocker-security' ); ?></h2>
					<p class="text-center text-muted mb-4"><?php esc_html_e( 'Choose how suspicious visitors are verified.', 'botblocker-security' ); ?></p>
					
					<div class="bbcs-captcha-grid">
						<?php ?>

					<!-- Silent Auto-Verify (Default Selected) -->
					<div class="bbcs-captcha-card" data-captcha="8">
						<div class="bbcs-captcha-video-wrapper">
							<img src="https://botblocker.top/wp-content/plugins/bbcs-materials/image/silent-mode.webp"
								alt="<?php esc_attr_e( 'Silent Auto-Verify', 'botblocker-security' ); ?>"
								class="bbcs-captcha-video" style="object-fit: cover;">
						</div>
						<div class="bbcs-captcha-content">
							<h4><i class="fa-solid fa-user-shield me-2"></i><?php esc_html_e( 'Silent Auto-Verify', 'botblocker-security' ); ?>
								<span class="bbcs-wizard-recommended"><?php esc_html_e( 'Recommended', 'botblocker-security' ); ?></span>
							</h4>
							<p class="small text-muted mb-0"><?php esc_html_e( 'No manual verification. Access decisions are based entirely on IP databases, blacklists, and threat intelligence.', 'botblocker-security' ); ?></p>
						</div>
					</div>

						<!-- Color Circles -->
						<div class="bbcs-captcha-card" data-captcha="1">
							<div class="bbcs-captcha-video-wrapper">
								<video class="bbcs-captcha-video" loop muted playsinline preload="metadata">
									<source src="<?php echo esc_url( BOTBLOCKER_MATERIALS_URL . 'video/captcha/color_circles.mp4' ); ?>" type="video/mp4">
									<?php esc_html_e( 'Your browser does not support the video tag.', 'botblocker-security' ); ?>
								</video>
								<div class="bbcs-captcha-play-icon">
									<i class="fa-solid fa-circle-play"></i>
								</div>
							</div>
							<div class="bbcs-captcha-content">
								<h4><i class="fa-solid fa-palette me-2"></i><?php esc_html_e( 'Color Matching', 'botblocker-security' ); ?></h4>
								<p class="small text-muted mb-0"><?php esc_html_e( 'Click the matching color. Simple logic, hard to automate.', 'botblocker-security' ); ?></p>
							</div>
						</div>

						<!-- Image Recognition -->
						<div class="bbcs-captcha-card" data-captcha="2">
							<div class="bbcs-captcha-video-wrapper">
								<video class="bbcs-captcha-video" loop muted playsinline preload="metadata">
									<source src="<?php echo esc_url( BOTBLOCKER_MATERIALS_URL . 'video/captcha/images.mp4' ); ?>" type="video/mp4">
									<?php esc_html_e( 'Your browser does not support the video tag.', 'botblocker-security' ); ?>
								</video>
								<div class="bbcs-captcha-play-icon">
									<i class="fa-solid fa-circle-play"></i>
								</div>
							</div>
							<div class="bbcs-captcha-content">
								<h4><i class="fa-solid fa-images me-2"></i><?php esc_html_e( 'Image Recognition', 'botblocker-security' ); ?></h4>
								<p class="small text-muted mb-0"><?php esc_html_e( 'Select matching images. Best balance of security and UX.', 'botblocker-security' ); ?></p>
							</div>
						</div>

						<!-- Dynamic Shapes -->
						<div class="bbcs-captcha-card" data-captcha="5">
							<div class="bbcs-captcha-video-wrapper">
								<video class="bbcs-captcha-video" loop muted playsinline preload="metadata">
									<source src="<?php echo esc_url( BOTBLOCKER_MATERIALS_URL . 'video/captcha/shapes.mp4' ); ?>" type="video/mp4">
									<?php esc_html_e( 'Your browser does not support the video tag.', 'botblocker-security' ); ?>
								</video>
								<div class="bbcs-captcha-play-icon">
									<i class="fa-solid fa-circle-play"></i>
								</div>
							</div>
							<div class="bbcs-captcha-content">
								<h4><i class="fa-solid fa-shapes me-2"></i><?php esc_html_e( 'Dynamic Shapes', 'botblocker-security' ); ?></h4>
								<p class="small text-muted mb-0"><?php esc_html_e( 'Match rotating shapes. BotBlocker exclusive.', 'botblocker-security' ); ?></p>
							</div>
						</div>

						<!-- Dynamic Digits -->
						<div class="bbcs-captcha-card" data-captcha="6">
							<div class="bbcs-captcha-video-wrapper">
								<video class="bbcs-captcha-video" loop muted playsinline preload="metadata">
									<source src="<?php echo esc_url( BOTBLOCKER_MATERIALS_URL . 'video/captcha/digits.mp4' ); ?>" type="video/mp4">
									<?php esc_html_e( 'Your browser does not support the video tag.', 'botblocker-security' ); ?>
								</video>
								<div class="bbcs-captcha-play-icon">
									<i class="fa-solid fa-circle-play"></i>
								</div>
							</div>
							<div class="bbcs-captcha-content">
								<h4><i class="fa-solid fa-calculator me-2"></i><?php esc_html_e( 'Dynamic Digits', 'botblocker-security' ); ?></h4>
								<p class="small text-muted mb-0"><?php esc_html_e( 'Simple math with moving numbers. Easy and effective.', 'botblocker-security' ); ?></p>
							</div>
						</div>

						<!-- Hold Button -->
						<div class="bbcs-captcha-card" data-captcha="7">
							<div class="bbcs-captcha-video-wrapper">
								<video class="bbcs-captcha-video" loop muted playsinline preload="metadata">
									<source src="<?php echo esc_url( BOTBLOCKER_MATERIALS_URL . 'video/captcha/hold_button.mp4' ); ?>" type="video/mp4">
									<?php esc_html_e( 'Your browser does not support the video tag.', 'botblocker-security' ); ?>
								</video>
								<div class="bbcs-captcha-play-icon">
									<i class="fa-solid fa-circle-play"></i>
								</div>
							</div>
							<div class="bbcs-captcha-content">
								<h4><i class="fa-solid fa-hand me-2"></i><?php esc_html_e( 'Hold Button', 'botblocker-security' ); ?></h4>
								<p class="small text-muted mb-0"><?php esc_html_e( 'Press and hold to verify. No images or math required.', 'botblocker-security' ); ?></p>
							</div>
						</div>

					</div>

					<div class="alert alert-warning mt-3 mb-3" style="font-size: 13px;">
						<i class="fa-solid fa-shield-halved me-1"></i>
						<strong><?php esc_html_e( 'More CAPTCHA Options Available:', 'botblocker-security' ); ?></strong>
							<?php
							$integrations_url = BotBlockerMultisite::getSiteAdminPageUrl( 'bbcs_integrations' );
							printf(
								/* translators: %s: URL to Integrations settings page */
								esc_html__( 'Google reCAPTCHA v2/v3 is also supported. Configure keys in %s.', 'botblocker-security' ),
								'<a href="' . esc_url( $integrations_url ) . '">' . esc_html__( 'Integrations', 'botblocker-security' ) . '</a>'
							);
							?>
					</div>

					<div class="alert alert-info" style="font-size: 13px;">
						<i class="fa-solid fa-lightbulb me-1"></i>
						<strong><?php esc_html_e( 'Pro Tip:', 'botblocker-security' ); ?></strong>
						<?php esc_html_e( 'All methods can be combined with invisible background checks. Choose the one that fits your site.', 'botblocker-security' ); ?>
					</div>

					<div class="bbcs-wizard-actions">
						<button class="btn btn-secondary bbcs-wizard-back">
							<i class="fa-solid fa-arrow-left"></i>
							<?php esc_html_e( 'Back', 'botblocker-security' ); ?>
						</button>
						<button class="btn btn-primary bbcs-wizard-save-captcha">
							<?php esc_html_e( 'Continue', 'botblocker-security' ); ?>
							<i class="fa-solid fa-arrow-right ms-1"></i>
							</button>
						</div>
					</div>
				</div>
				
<!-- Step 5: Initialization Mode -->
			<div class="bbcs-wizard-step" data-step="5">
				<div class="bbcs-wizard-step-content">
					<h2 class="bbcs-wizard-title"><?php esc_html_e( 'Choose Your Initialization Mode', 'botblocker-security' ); ?></h2>
					<p class="text-center text-muted mb-4"><?php esc_html_e( 'Select when BotBlocker activates. Earlier = stronger protection.', 'botblocker-security' ); ?></p>
					
					<div class="bbcs-wizard-init-modes">
						<!-- Regular Plugin (Default Selected) -->
						<div class="bbcs-wizard-init-card" data-mode="regular">
							<div class="bbcs-init-icon">
								<i class="fa-solid fa-plug"></i>
							</div>
							<h4><?php esc_html_e( 'Regular Plugin', 'botblocker-security' ); ?> 
								<span class="bbcs-wizard-recommended"><?php esc_html_e( 'Default', 'botblocker-security' ); ?></span>
							</h4>
							<p class="small text-muted mb-2"><?php esc_html_e( 'Loads with standard plugins. Fits most sites.', 'botblocker-security' ); ?></p>
							<ul class="small mb-0">
								<li><?php esc_html_e( 'Standard plugin loading', 'botblocker-security' ); ?></li>
								<li><?php esc_html_e( 'Compatible with all setups', 'botblocker-security' ); ?></li>
								<li><?php esc_html_e( 'No special configuration needed', 'botblocker-security' ); ?></li>
							</ul>
						</div>

						<!-- MU Plugin -->
						<div class="bbcs-wizard-init-card" data-mode="mu">
							<div class="bbcs-init-icon">
								<i class="fa-solid fa-bolt"></i>
							</div>
							<h4><?php esc_html_e( 'MU Plugin Mode', 'botblocker-security' ); ?></h4>
							<p class="small text-muted mb-2"><?php esc_html_e( 'Loads before regular plugins. Faster threat response.', 'botblocker-security' ); ?></p>
							<ul class="small mb-0">
								<li><?php esc_html_e( 'Activates before other plugins', 'botblocker-security' ); ?></li>
								<li><?php esc_html_e( 'IP white/blacklists work earlier', 'botblocker-security' ); ?></li>
								<li><?php esc_html_e( 'Better protection for login page', 'botblocker-security' ); ?></li>
							</ul>
						</div>

						<!-- Early Init (PRO) -->
						<div class="bbcs-wizard-init-card" data-mode="early">
							<?php if ( ! BotBlockerPro::isActive() ) : ?>
							<div class="bbcs-init-pro-overlay">
								<div class="bbcs-pro-badge-top">
									<i class="fa-solid fa-lock"></i>
									<span><?php esc_html_e( 'Only for PRO', 'botblocker-security' ); ?></span>
								</div>
								<a href="https://botblocker.top/pricing/" target="_blank" class="bbcs-pro-badge-bottom">
									<?php esc_html_e( 'See plans', 'botblocker-security' ); ?>
								</a>
							</div>
							<?php endif; ?>
							
							<div class="bbcs-init-icon bbcs-init-icon-premium">
								<i class="fa-solid fa-rocket"></i>
							</div>
							<h4>
								<i class="fa-solid fa-crown text-warning me-1"></i>
								<?php esc_html_e( 'Early Initialization', 'botblocker-security' ); ?>
							</h4>
							<p class="small text-muted mb-2"><?php esc_html_e( 'Blocks threats before WordPress loads. Maximum security.', 'botblocker-security' ); ?></p>
							<ul class="small mb-0">
								<li><strong><?php esc_html_e( 'Instant IP ban capability', 'botblocker-security' ); ?></strong></li>
								<li><?php esc_html_e( 'Blocks attacks before WP starts', 'botblocker-security' ); ?></li>
								<li><?php esc_html_e( 'Lowest server resource usage', 'botblocker-security' ); ?></li>
								<li><?php esc_html_e( 'Maximum protection', 'botblocker-security' ); ?></li>
							</ul>
						</div>
					</div>

					<div class="alert alert-info mt-3" style="font-size: 13px;">
						<i class="fa-solid fa-lightbulb me-1"></i>
						<strong><?php esc_html_e( 'Pro Tip:', 'botblocker-security' ); ?></strong>
						<?php
						$rules_url = BotBlockerMultisite::getSiteAdminPageUrl( 'bbcs_rules' );
						printf(
							/* translators: %s: URL to Rules settings page */
							esc_html__( 'Early Init provides instant IP banning. Custom blacklists (IPv4/IPv6) can be uploaded in %s for any mode.', 'botblocker-security' ),
							'<a href="' . esc_url( $rules_url ) . '">' . esc_html__( 'Rules', 'botblocker-security' ) . '</a>'
						);
						?>
					</div>

					<div class="bbcs-wizard-actions">
						<button class="btn btn-secondary bbcs-wizard-back">
							<i class="fa-solid fa-arrow-left"></i>
							<?php esc_html_e( 'Back', 'botblocker-security' ); ?>
						</button>
						<button class="btn btn-primary bbcs-wizard-save-init-mode">
							<?php esc_html_e( 'Continue', 'botblocker-security' ); ?>
							<i class="fa-solid fa-arrow-right ms-1"></i>
							</button>
						</div>
					</div>
				</div>
				
				<!-- Step 6: Cache Selection -->
				<div class="bbcs-wizard-step" data-step="6">
					<div class="bbcs-wizard-step-content">
						<h2 class="bbcs-wizard-title">
							<i class="fa-solid fa-database me-2"></i>
							<?php esc_html_e( 'Performance Optimization', 'botblocker-security' ); ?>
						</h2>
						<p class="text-muted mb-4"><?php esc_html_e( 'Enable caching for faster security checks. BotBlocker uses the available cache system.', 'botblocker-security' ); ?></p>

						<div class="bbcs-cache-grid">
							<!-- Redis -->
							<div class="bbcs-cache-card" data-cache="redis">
								<div class="bbcs-cache-status bbcs-cache-status-redis">
									<i class="fa-solid fa-spinner fa-spin"></i>
								</div>
								<div class="bbcs-cache-icon">
									<i class="fa-solid fa-bolt"></i>
								</div>
								<h4>Redis</h4>
								<p class="small text-muted mb-2"><?php esc_html_e( 'Ultra-fast in-memory cache. Best performance.', 'botblocker-security' ); ?></p>
								<ul class="small mb-0">
									<li><?php esc_html_e( 'Lightning fast response', 'botblocker-security' ); ?></li>
									<li><?php esc_html_e( 'Persistent connections', 'botblocker-security' ); ?></li>
									<li><?php esc_html_e( 'Advanced data structures', 'botblocker-security' ); ?></li>
								</ul>
							</div>

							<!-- Memcached -->
							<div class="bbcs-cache-card" data-cache="memcached">
								<div class="bbcs-cache-status bbcs-cache-status-memcached">
									<i class="fa-solid fa-spinner fa-spin"></i>
								</div>
								<div class="bbcs-cache-icon">
									<i class="fa-solid fa-server"></i>
								</div>
								<h4>Memcached</h4>
								<p class="small text-muted mb-2"><?php esc_html_e( 'High-performance distributed cache system.', 'botblocker-security' ); ?></p>
								<ul class="small mb-0">
									<li><?php esc_html_e( 'Simple and reliable', 'botblocker-security' ); ?></li>
									<li><?php esc_html_e( 'Low resource usage', 'botblocker-security' ); ?></li>
									<li><?php esc_html_e( 'Easy to scale', 'botblocker-security' ); ?></li>
								</ul>
							</div>

							<!-- None -->
							<div class="bbcs-cache-card" data-cache="none">
								<div class="bbcs-cache-icon">
									<i class="fa-solid fa-ban"></i>
								</div>
								<h4><?php esc_html_e( 'No Cache', 'botblocker-security' ); ?></h4>
								<p class="small text-muted mb-2"><?php esc_html_e( 'Use WordPress transients. Works on any hosting.', 'botblocker-security' ); ?></p>
								<ul class="small mb-0">
									<li><?php esc_html_e( 'No configuration needed', 'botblocker-security' ); ?></li>
									<li><?php esc_html_e( 'Works everywhere', 'botblocker-security' ); ?></li>
									<li><?php esc_html_e( 'Standard performance', 'botblocker-security' ); ?></li>
								</ul>
							</div>
						</div>

						<div class="alert alert-info mt-3" style="font-size: 13px;">
							<i class="fa-solid fa-lightbulb me-1"></i>
							<strong><?php esc_html_e( 'Pro Tip:', 'botblocker-security' ); ?></strong>
							<?php esc_html_e( 'Redis is faster for high-traffic sites. Memcached fits distributed setups. This can be changed later in Settings.', 'botblocker-security' ); ?>
						</div>

						<div class="bbcs-wizard-actions">
							<button class="btn btn-secondary bbcs-wizard-back">
								<i class="fa-solid fa-arrow-left"></i>
								<?php esc_html_e( 'Back', 'botblocker-security' ); ?>
							</button>
							<button class="btn btn-primary bbcs-wizard-save-cache" disabled>
								<?php esc_html_e( 'Continue', 'botblocker-security' ); ?>
								<i class="fa-solid fa-arrow-right ms-1"></i>
							</button>
						</div>
					</div>
				</div>
				
				<!-- Step 7: Final Success -->
				<div class="bbcs-wizard-step" data-step="7">
					<div class="bbcs-wizard-step-content">
						<div class="text-center mb-4">
							<div class="bbcs-wizard-success-icon">
								<i class="fa-solid fa-circle-check"></i>
							</div>
							<h2 class="bbcs-wizard-title mb-2"><?php esc_html_e( 'Your Site is Now Protected', 'botblocker-security' ); ?></h2>
							<p class="text-muted"><?php esc_html_e( 'BotBlocker is now monitoring and protecting your site.', 'botblocker-security' ); ?></p>
						</div>
						
						<div class="bbcs-wizard-final-summary">
							<h3><i class="fa-solid fa-list-check me-2"></i><?php esc_html_e( 'Configuration Summary', 'botblocker-security' ); ?></h3>
							<div class="bbcs-summary-grid">
								<div class="bbcs-summary-item">
									<span class="bbcs-summary-label"><?php esc_html_e( 'Protection Level:', 'botblocker-security' ); ?></span>
									<strong class="bbcs-wizard-final-mode text-primary">Strong</strong>
								</div>
								<div class="bbcs-summary-item">
									<span class="bbcs-summary-label"><?php esc_html_e( 'CAPTCHA Type:', 'botblocker-security' ); ?></span>
									<strong class="bbcs-wizard-final-captcha text-primary"><?php esc_html_e( 'Image Recognition', 'botblocker-security' ); ?></strong>
								</div>
								<div class="bbcs-summary-item">
									<span class="bbcs-summary-label"><?php esc_html_e( 'Initialization:', 'botblocker-security' ); ?></span>
									<strong class="bbcs-wizard-final-init text-primary"><?php esc_html_e( 'Regular Plugin', 'botblocker-security' ); ?></strong>
								</div>
								<div class="bbcs-summary-item">
									<span class="bbcs-summary-label"><?php esc_html_e( 'Security Score:', 'botblocker-security' ); ?></span>
									<strong class="bbcs-wizard-final-score text-success">--</strong>
								</div>
							</div>
						</div>

						<div class="bbcs-wizard-next-steps">
							<h3><i class="fa-solid fa-rocket me-2"></i><?php esc_html_e( 'Quick Start Guide', 'botblocker-security' ); ?></h3>
							<div class="bbcs-steps-grid">
								<a href="<?php echo esc_url( BotBlockerMultisite::getSiteAdminPageUrl( 'bbcs_dashboard' ) ); ?>" class="bbcs-step-card">
									<div class="bbcs-step-icon">
										<i class="fa-solid fa-gauge-high"></i>
									</div>
									<h4><?php esc_html_e( 'View Dashboard', 'botblocker-security' ); ?></h4>
									<p class="small text-muted mb-0"><?php esc_html_e( 'Monitor real-time threats and statistics', 'botblocker-security' ); ?></p>
								</a>

								<a href="<?php echo esc_url( BotBlockerMultisite::getSiteAdminPageUrl( 'bbcs_reports' ) ); ?>" class="bbcs-step-card">
									<div class="bbcs-step-icon">
										<i class="fa-solid fa-chart-line"></i>
									</div>
									<h4><?php esc_html_e( 'Check Reports', 'botblocker-security' ); ?></h4>
									<p class="small text-muted mb-0"><?php esc_html_e( 'Detailed logs of all blocked threats', 'botblocker-security' ); ?></p>
								</a>

								<a href="<?php echo esc_url( BotBlockerMultisite::getSiteAdminPageUrl( 'bbcs_rules' ) ); ?>" class="bbcs-step-card">
									<div class="bbcs-step-icon">
										<i class="fa-solid fa-sliders"></i>
									</div>
									<h4><?php esc_html_e( 'Fine-tune Rules', 'botblocker-security' ); ?></h4>
									<p class="small text-muted mb-0"><?php esc_html_e( 'Create custom block/allow rules', 'botblocker-security' ); ?></p>
								</a>

								<a href="<?php echo esc_url( BotBlockerMultisite::getSiteAdminPageUrl( 'bbcs_settings' ) ); ?>" class="bbcs-step-card">
									<div class="bbcs-step-icon">
										<i class="fa-solid fa-gear"></i>
									</div>
									<h4><?php esc_html_e( 'Advanced Settings', 'botblocker-security' ); ?></h4>
									<p class="small text-muted mb-0"><?php esc_html_e( 'Customize detection algorithms', 'botblocker-security' ); ?></p>
								</a>
							</div>
						</div>

						<?php if ( ! BotBlockerPro::isActive() ) : ?>
						<div class="bbcs-wizard-pro-upgrade">
							<div class="bbcs-pro-badge">
								<i class="fa-solid fa-crown"></i> PRO
							</div>
							<h3><?php esc_html_e( 'Unlock PRO Protection', 'botblocker-security' ); ?></h3>
							<p class="mb-3"><?php esc_html_e( 'Advanced security features for production sites.', 'botblocker-security' ); ?></p>
							<div class="row mb-3">
								<div class="col-6">
									<ul class="bbcs-pro-features-compact">
										<li><i class="fa-solid fa-check"></i> <?php esc_html_e( 'Early init - blocks before WP loads', 'botblocker-security' ); ?></li>
										<li><i class="fa-solid fa-check"></i> <?php esc_html_e( 'WordPress Acceleration', 'botblocker-security' ); ?></li>
										<li><i class="fa-solid fa-check"></i> <?php esc_html_e( 'Hide Login URL + add-ons', 'botblocker-security' ); ?></li>
										<li><i class="fa-solid fa-check"></i> <?php esc_html_e( 'VPN and Tor Blocking', 'botblocker-security' ); ?></li>
										<li><i class="fa-solid fa-check"></i> <?php esc_html_e( 'AI Behavioral analysis', 'botblocker-security' ); ?></li>
										
									</ul>
								</div>
								<div class="col-6">
									<ul class="bbcs-pro-features-compact">
										<li><i class="fa-solid fa-check"></i> <?php esc_html_e( 'Zero-day botnet updates', 'botblocker-security' ); ?></li>
										<li><i class="fa-solid fa-check"></i> <?php esc_html_e( '5 million+ bot signatures', 'botblocker-security' ); ?></li>
										<li><i class="fa-solid fa-check"></i> <?php esc_html_e( 'All Premium Add-ons', 'botblocker-security' ); ?></li>
										<li><i class="fa-solid fa-check"></i> <?php esc_html_e( 'Priority Support', 'botblocker-security' ); ?></li>
										<li><i class="fa-solid fa-check"></i> <?php esc_html_e( 'Emergency help (24h)', 'botblocker-security' ); ?></li>
									</ul>
								</div>
							</div>
							<a href="<?php echo esc_url( BotBlockerMultisite::getSiteAdminPageUrl( 'bbcs_cloud_api' ) ); ?>" class="btn btn-warning btn-lg">
								<i class="fa-solid fa-crown me-2"></i><?php esc_html_e( 'Upgrade to PRO', 'botblocker-security' ); ?>
							</a>
						</div>
						<?php else : ?>
						<div class="alert alert-success">
							<i class="fa-solid fa-crown me-2"></i>
							<strong><?php esc_html_e( 'PRO Active', 'botblocker-security' ); ?></strong>
							<?php esc_html_e( 'All PRO features are active.', 'botblocker-security' ); ?>
						</div>
						<?php endif; ?>

						<div class="bbcs-wizard-final-actions">
							<a href="<?php echo esc_url( BotBlockerMultisite::getSiteAdminPageUrl( 'bbcs_dashboard' ) ); ?>" class="btn btn-primary btn-lg lh-lg">
								<i class="fa-solid fa-house me-2"></i><?php esc_html_e( 'Go to Dashboard', 'botblocker-security' ); ?>
							</a>
							<a href="https://botblocker.top/docs/" target="_blank" class="btn btn-outline-secondary">
								<i class="fa-solid fa-book me-2"></i><?php esc_html_e( 'Documentation', 'botblocker-security' ); ?>
							</a>
						</div>

						<p class="text-center mt-4 small text-muted">
							<i class="fa-solid fa-life-ring me-1"></i>
							<?php esc_html_e( 'Need help? Visit our', 'botblocker-security' ); ?>
							<a href="https://botblocker.top/docs/" target="_blank"><?php esc_html_e( 'support center', 'botblocker-security' ); ?></a>
							<?php esc_html_e( 'or contact us at', 'botblocker-security' ); ?>
							<a href="https://botblocker.top/contacts/" target="_blank"><?php esc_html_e( 'botblocker.top/contacts', 'botblocker-security' ); ?></a>
						</p>
					</div>
				</div>
				
			</div>
		</div>
			<?php
			// phpcs:enable WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}
