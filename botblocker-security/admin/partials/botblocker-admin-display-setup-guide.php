<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require 'botblocker-section-header.php';

?><section role="main" class="content-body">
	<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<input type="hidden" name="action" value="save_botblocker_setup">
	<?php wp_nonce_field( 'save_botblocker_setup', 'botblocker_setup_nonce' ); ?>
	<input type="hidden" name="bbcs_anchor" id="bbcs_anchor" value="">
		<div class="row">
			<div class="col-xs-12 col-sm-12 col-md-9 col-lg-9 col-xl-9 col-xxl-10">
	
				<section class="card">
					<header class="card-header">
						<h2 class="card-title"><?php esc_html_e( 'BotBlocker Health Panel', 'botblocker-security' ); ?></h2>
					</header>
					<div class="card-body">						
					<?php
						require_once BOTBLOCKER_DIR . 'includes/section/setup/botblocker-setup-health.php';
						require_once BOTBLOCKER_DIR . 'includes/section/setup/botblocker-setup-pro.php';
						require_once BOTBLOCKER_DIR . 'includes/section/setup/botblocker-setup-chain.php';
						// include_once BOTBLOCKER_DIR . 'includes/section/setup/botblocker-setup-tools-panel.php';
					?>
					</div>
				</section>

			</div>
			<div class="col-xs-12 col-sm-12 col-md-3 col-lg-3 col-xl-3 col-xxl-2">
				<?php require 'botblocker-section-right-sidebar.php'; ?>
			</div>
		</div>
	</form>
</section>   

<?php
	require_once BOTBLOCKER_DIR . 'includes/modal/modal-botblocker-setup-wizard.php';
?>
