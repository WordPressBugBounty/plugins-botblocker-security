<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}
?>
	<div class="row">		
		<div class="col-xxl-3 col-xl-6 col-lg-6 col-sm-12 col-md-12  bbcs-info-column">			
			<div class="bbcs-info-inner">
				<?php
				// REVIEWER NOTE: This image is a static plugin asset, not a user-uploaded Media Library image.
                // phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage ?>
				<img src="<?php echo esc_url( BOTBLOCKER_URL . 'public/icons/status.svg' ); ?>" 
					alt="<?php esc_attr_e( 'System status', 'botblocker-security' ); ?>" 
					class="img-fluid bbcs-info-image mb-3">

				<p class="bbcs-info-text">
					<?php esc_html_e( 'Overview of your WordPress environment and BotBlocker core: plugin state, themes, plugins, and server parameters.', 'botblocker-security' ); ?>
				</p>
				<p class="bbcs-info-text">
					<?php esc_html_e( 'Identify configuration issues, check software versions, and verify your site runs smoothly.', 'botblocker-security' ); ?>
				</p>
				<hr class="bbcs-info-hr">
				<div class="bbcs-info-footer">
					<i class="fa-regular fa-circle-question"></i>
					<a href="<?php echo esc_url( BOTBLOCKER_DOCS_URL ); ?>/apache-web-server-and-its-use-with-wordpress-a-detailed-guide/" target="_blank" 
					class="bbcs-info-footer-a"><?php esc_html_e( 'Apache', 'botblocker-security' ); ?></a>

					<a href="<?php echo esc_url( BOTBLOCKER_DOCS_URL ); ?>/nginx-web-server-and-its-use-with-wordpress-a-detailed-guide/" target="_blank" 
					class="bbcs-info-footer-a"><?php esc_html_e( 'Nginx', 'botblocker-security' ); ?></a>

					<a href="<?php echo esc_url( BOTBLOCKER_DOCS_URL ); ?>/nginx-vs-apache-and-php-fpm-for-wordpress-concise-comparison/" target="_blank" 
					class="bbcs-info-footer-a"><?php esc_html_e( 'Nginx vs Apache', 'botblocker-security' ); ?></a>

					<a href="<?php echo esc_url( BOTBLOCKER_DOCS_URL ); ?>/server-operating-systems-types-features-and-their-role-in-web-hosting/" target="_blank" 
					class="bbcs-info-footer-a"><?php esc_html_e( 'Server OS', 'botblocker-security' ); ?></a>

					<a href="<?php echo esc_url( BOTBLOCKER_DOCS_URL ); ?>/php-and-its-modern-versions-why-it-matters-for-wordpress-and-botblocker/" target="_blank"
					class="bbcs-info-footer-a"><?php esc_html_e( 'PHP versions', 'botblocker-security' ); ?></a>

					<a href="<?php echo esc_url( BOTBLOCKER_DOCS_URL ); ?>/servers-clouds-and-hosting-for-wordpress-operating-systems-requirements-and-key-choices/" target="_blank" 
					class="bbcs-info-footer-a"><?php esc_html_e( 'Hosting and system requirements', 'botblocker-security' ); ?></a>
				</div>
			</div>
		</div>
		<div class="col-xxl-9 col-xl-6 col-lg-6 col-sm-12 col-md-12">
			<ul class="nav nav-tabs" role="tablist">
				<li class="nav-item">
					<a class="nav-link active" data-bs-toggle="tab" href="#bbcs-about-status" role="tab">
						<i class="fa-solid fa-gauge-high me-1"></i><?php esc_html_e( 'System status', 'botblocker-security' ); ?>
					</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" data-bs-toggle="tab" href="#bbcs-about-versions" role="tab">
						<i class="fa-solid fa-cubes me-1"></i><?php esc_html_e( 'Software versions', 'botblocker-security' ); ?>
					</a>
				</li>
				<li class="nav-item">
					<a class="nav-link" data-bs-toggle="tab" href="#bbcs-about-changelog" role="tab">
						<i class="fa-solid fa-clock-rotate-left me-1"></i><?php esc_html_e( 'Changelog', 'botblocker-security' ); ?>
					</a>
				</li>
			</ul>
			<div class="tab-content mt-3">
				<div class="tab-pane fade show active" id="bbcs-about-status" role="tabpanel">
					<?php echo do_shortcode( '[bbcs_system_status]' ); ?>
				</div>
				<div class="tab-pane fade" id="bbcs-about-versions" role="tabpanel">
					<?php echo do_shortcode( '[bbcs_plugins_themes]' ); ?>
				</div>
				<div class="tab-pane fade" id="bbcs-about-changelog" role="tabpanel">
					<?php
					require_once BOTBLOCKER_DIR . 'includes/data/botblocker-marketing-blocks.php';
					$bbcs_changelog_html = '';
					$bbcs_changelog_file = BOTBLOCKER_DIR . 'readme.md';
					if ( file_exists( $bbcs_changelog_file ) ) {
						$bbcs_readme = (string) file_get_contents( $bbcs_changelog_file );
						$bbcs_pairs  = bbcs_parse_changelog_section( $bbcs_readme );
						$bbcs_pairs  = array_slice( $bbcs_pairs, 0, 20, true );
						if ( ! empty( $bbcs_pairs ) ) {
							$bbcs_changelog_html .= '<div class="bbcs-changelog">';
							foreach ( $bbcs_pairs as $bbcs_ver => $bbcs_lines ) {
								$bbcs_changelog_html .= '<h4 class="bbcs-changelog-version">' . esc_html( $bbcs_ver ) . '</h4><ul class="bbcs-changelog-list">';
								foreach ( $bbcs_lines as $bbcs_line ) {
									$bbcs_changelog_html .= '<li>' . esc_html( $bbcs_line ) . '</li>';
								}
								$bbcs_changelog_html .= '</ul>';
							}
							$bbcs_changelog_html .= '</div>';
						}
					}
					if ( $bbcs_changelog_html === '' ) {
						echo '<p class="bbcs-text-muted">' . esc_html__( 'Changelog is not available.', 'botblocker-security' ) . '</p>';
					} else {
						echo wp_kses(
							$bbcs_changelog_html,
							array(
								'div' => array( 'class' => true ),
								'h4'  => array( 'class' => true ),
								'ul'  => array( 'class' => true ),
								'li'  => array( 'class' => true ),
							)
						);
					}
					?>
				</div>
			</div>
			<?php if ( defined( 'BBCS_DEBUG' ) && BBCS_DEBUG ) : ?>
			<h3 class="bbcs_settings_h3 mt-3"><?php esc_html_e( 'BotBlocker Hive Snapshot', 'botblocker-security' ); ?></h3>
				<?php BotBlocker::getInstance()->print_hive(); ?>
			<?php endif; ?>
		</div>
	</div>
