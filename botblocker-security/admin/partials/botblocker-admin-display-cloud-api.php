<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require 'botblocker-section-header.php';

?><section role="main" class="content-body">
<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
	<div class="row">
		<div class="col-xs-12 col-sm-12 col-md-9 col-lg-9 col-xl-9 col-xxl-10">
			<section class="card">
				<header class="card-header">
					<div class="card-actions">
						<button type="submit" name="save_settings" value="Save Settings" class="bbcs-icon-button">
							<i class="bbcs-card-action fa-regular fa-xl fa-floppy-disk"></i>
						</button>
					</div>
					<h2 class="card-title"><?php esc_html_e( 'BotBlocker PRO', 'botblocker-security' ); ?></h2>
				</header>
				<div class="card-body">

					<ul class="nav nav-tabs">
						<!-- <li class="nav-item">
							<a class="nav-link active" data-bs-toggle="tab" href="#cloud-plans"><?php esc_html_e( 'Plans', 'botblocker-security' ); ?></a>
						</li>  -->                       
						<li class="nav-item">
							<a class="nav-link active" data-bs-toggle="tab" href="#cloud-status"><?php esc_html_e( 'BotBlocker Cloud Protection and PRO Status', 'botblocker-security' ); ?></a>
						</li>
						<!--                  
						<li class="nav-item">
							<a class="nav-link" data-bs-toggle="tab" href="#cloud-support"><?php //esc_html_e('Support', 'botblocker-security'); ?></a>
						</li>
						<li class="nav-item">
							<a class="nav-link" data-bs-toggle="tab" href="#cloud-services"><?php //esc_html_e('Services for cloud', 'botblocker-security'); ?></a>
						</li>                        
						<li class="nav-item">
							<a class="nav-link" data-bs-toggle="tab" href="#cloud-about"><?php //esc_html_e('About', 'botblocker-security'); ?></a>
						</li>   
						-->                     
					</ul>
					<div class="tab-content">
						<?php
							// include_once BOTBLOCKER_DIR . 'includes/section/cloud/botblocker-cloud-plans.php';
							require_once BOTBLOCKER_DIR . 'includes/section/cloud/botblocker-cloud-status.php';
							// include_once BOTBLOCKER_DIR . 'includes/section/cloud/botblocker-cloud-support.php';
							// include_once BOTBLOCKER_DIR . 'includes/section/cloud/botblocker-cloud-services.php';
							// include_once BOTBLOCKER_DIR . 'includes/section/cloud/botblocker-cloud-about.php';
						?>
					</div>
				</div>

			</section>
		</div>
		<div class="col-xs-12 col-sm-12 col-md-3 col-lg-3 col-xl-3 col-xxl-2">
			<?php require 'botblocker-section-right-sidebar.php'; ?>
		</div>
	</div>
</form>
</section>
