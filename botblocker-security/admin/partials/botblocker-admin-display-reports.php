<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

require 'botblocker-section-header.php';

$bbcs_reportTableHeader = '
<thead>
<tr>
<th style="min-width: 85px;">' . esc_html__( 'Date', 'botblocker-security' ) . '/' . esc_html__( 'Time', 'botblocker-security' ) . '</th>
<th style="min-width: 100px;">' . esc_html__( 'IP', 'botblocker-security' ) . '/' . esc_html__( 'PTR', 'botblocker-security' ) . '</th>
<th style="min-width: 100px;">' . esc_html__( 'AS Info', 'botblocker-security' ) . '</th>
<th style="min-width: 110px;">' . esc_html__( 'Lang', 'botblocker-security' ) . '</th>
<th style="min-width: 200px;">' . esc_html__( 'User Agent', 'botblocker-security' ) . '</th>
<th style="min-width: 300px;">' . esc_html__( 'Page', 'botblocker-security' ) . '/' . esc_html__( 'Referer', 'botblocker-security' ) . '</th>
<th style="min-width: 200px;">' . esc_html__( 'JS Info', 'botblocker-security' ) . '</th>
<th style="min-width: 100px;"><i class="fa-solid fa-ban"></i></th>
</tr>
</thead>';

?><section role="main" class="content-body">
	<div class="row">

		<div class="col-xs-12 col-sm-12 col-md-9 col-lg-9 col-xl-9 col-xxl-10">
			<section class="card mb-2">
				<header class="card-header">
					<div class="card-actions">
						<a href="#" class="card-action card-action-toggle" data-card-toggle=""></a>
						<a href="#" class="card-action card-action-dismiss" data-card-dismiss=""></a>
					</div>
					<h2 class="card-title"><?php esc_html_e( 'Statistics', 'botblocker-security' ); ?></h2>
					<!--<p class="card-subtitle">Score of your website health status.</p>-->
				</header>
				<div class="card-body">
					<ul class="nav nav-tabs">
						<li class="nav-item">
							<a class="nav-link active" data-bs-toggle="tab"
								href="#report_dashboard"><b><?php esc_html_e( 'Reports dashboard', 'botblocker-security' ); ?></b></a>
						</li>
						<li class="nav-item">
							<a class="nav-link" data-bs-toggle="tab"
								href="#frontend"><?php esc_html_e( 'Site visitors', 'botblocker-security' ); ?></a>
						</li>
						<li class="nav-item">
							<a class="nav-link" data-bs-toggle="tab"
								href="#admin"><?php esc_html_e( 'WordPress Admin Area Log', 'botblocker-security' ); ?></a>
						</li>
						<li class="nav-item">
							<a class="nav-link" data-bs-toggle="tab"
								href="#wordpress"><?php esc_html_e( 'WordPress Actions', 'botblocker-security' ); ?></a>
						</li>
						<li class="nav-item">
							<a class="nav-link" data-bs-toggle="tab"
								href="#full"><?php esc_html_e( 'Full log', 'botblocker-security' ); ?></a>
						</li>
					</ul>
					<div class="tab-content">

						<?php require_once BOTBLOCKER_DIR . 'includes/section/report/botblocker-report-dashboard.php'; ?>

						<div class="tab-pane container fade" id="frontend">
							<table class="table table-bordered table-striped compact mb-0" id="botblocker-hits"
								style="width:100%; font-size: 11px;">
								<?php echo wp_kses_post( $bbcs_reportTableHeader ); ?>
							</table>
						</div>
						<div class="tab-pane container fade" id="admin">
							<table class="table table-bordered table-striped compact mb-0" id="botblocker-hits-admin"
								style="width:100%; font-size: 11px;">
								<?php echo wp_kses_post( $bbcs_reportTableHeader ); ?>
							</table>
						</div>
						<div class="tab-pane container fade" id="wordpress">
							<table class="table table-bordered table-striped compact mb-0" id="botblocker-other-admin"
								style="width:100%; font-size: 11px;">
								<?php echo wp_kses_post( $bbcs_reportTableHeader ); ?>
							</table>
						</div>
						<div class="tab-pane container fade" id="full">
							<table class="table table-bordered table-striped compact mb-0" id="botblocker-hits-full"
								style="width:100%; font-size: 11px;">
								<?php echo wp_kses_post( $bbcs_reportTableHeader ); ?>
							</table>
						</div>
					</div>
				</div>
			</section>
		</div>
		<div class="col-xs-12 col-sm-12 col-md-3 col-lg-3 col-xl-3 col-xxl-2">
			<?php require 'botblocker-section-right-sidebar.php'; ?>
		</div>
	</div>
</section>
<?php
	require_once BOTBLOCKER_DIR . 'includes/modal/modal-botblocker-hits-add-rule.php';
?>
