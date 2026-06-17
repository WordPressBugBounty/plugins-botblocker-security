<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require 'botblocker-section-header.php';

BotBlockerStats::getStatistics( $BBCS->settings->admin_report_period );
?><section role="main" class="content-body">
	<div class="row">
		<div class="col-xs-12 col-sm-12 col-md-5 col-lg-5 col-xl-5 col-xxl-5">
			<div class="row">
				<?php
					require_once BOTBLOCKER_DIR . 'includes/section/dashboard/botblocker-dash-health.php';
					require_once BOTBLOCKER_DIR . 'includes/section/dashboard/botblocker-dash-links.php';
				?>
			</div>
			<?php
				require_once BOTBLOCKER_DIR . 'includes/section/dashboard/botblocker-dash-visitors.php';
				require_once BOTBLOCKER_DIR . 'includes/section/dashboard/botblocker-dash-secret.php';
			?>
			
		</div>

		<div class="col-xs-12 col-sm-12 col-md-5 col-lg-5 col-xl-5 col-xxl-5 mb-1">
			<?php
				require_once BOTBLOCKER_DIR . 'includes/section/dashboard/botblocker-dash-today.php';
				require_once BOTBLOCKER_DIR . 'includes/section/dashboard/botblocker-dash-traffic.php';
				require_once BOTBLOCKER_DIR . 'includes/section/dashboard/botblocker-dash-geo.php';
			?>
		</div>

		<div class="col-xs-12 col-sm-12 col-md-2 col-lg-2 col-xl-2 col-xxl-2 mb-1">
			<?php require 'botblocker-section-right-sidebar.php'; ?>
		</div>
	</div>
</section>
<?php require_once BOTBLOCKER_DIR . 'includes/modal/modal-botblocker-rule-countries-list.php'; ?>
