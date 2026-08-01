<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
return static function (): void {
	?>
	<li>
		<a href="#" class="dropdown-toggle notification-icon" data-bs-toggle="dropdown">
			<i class="fa fa-list"></i>
			<span class="badge task-count">0</span>
		</a>
		<div class="dropdown-menu notification-menu large">
			<div class="notification-title">
				<span class="float-end badge badge-default task-count">0</span>
				<?php esc_html_e( 'Tasks', 'botblocker-security' ); ?>
			</div>
			<div class="content">
				<ul class="bbcs-task-list">

				</ul>
			</div>
		</div>
	</li>
	<?php
};
