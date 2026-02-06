<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

include('botblocker-section-header.php');

bbcs_get_statistics($BBCS->settings->admin_report_period); 
?><section role="main" class="content-body">
	<div class="row">
		<div class="col-xs-12 col-sm-12 col-md-5 сol-lg-5 col-xl-5 col-xxl-5">
			<div class="row">
				<?php 
					include_once BOTBLOCKER_DIR . 'includes/section/dashboard/botblocker-dash-health.php'; 
					include_once BOTBLOCKER_DIR . 'includes/section/dashboard/botblocker-dash-links.php';
				?>
			</div>
			<?php 
				include_once BOTBLOCKER_DIR . 'includes/section/dashboard/botblocker-dash-visitors.php';
				include_once BOTBLOCKER_DIR . 'includes/section/dashboard/botblocker-dash-secret.php';
			?>
			
		</div>

		<div class="col-xs-12 col-sm-12 col-md-5 сol-lg-5 col-xl-5 col-xxl-5 mb-1">
			<?php 
				include_once BOTBLOCKER_DIR . 'includes/section/dashboard/botblocker-dash-today.php';
				include_once BOTBLOCKER_DIR . 'includes/section/dashboard/botblocker-dash-traffic.php';
				include_once BOTBLOCKER_DIR . 'includes/section/dashboard/botblocker-dash-geo.php';
			?>
		</div>

		<div class="col-xs-12 col-sm-12 col-md-2 сol-lg-2 col-xl-2 col-xxl-2 mb-1">
			<?php include('botblocker-section-right-sidebar.php'); ?>
		</div>
	</div>
</section>
<?php include_once BOTBLOCKER_DIR . 'includes/modal/modal-botblocker-rule-countries-list.php';?>
