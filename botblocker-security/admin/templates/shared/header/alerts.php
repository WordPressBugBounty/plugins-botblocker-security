<?php
use BotBlocker\Component\AlertNotificationItem;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
return static function ( Botblocker_HeaderViewModel $header ): void {
	$h = $header;
	?>
	<li>
		<a href="#" class="dropdown-toggle notification-icon" data-bs-toggle="dropdown">
			<i class="fa fa-bullhorn"></i>
			<?php if ( ! empty( $h->alerts ) ) : ?>
				<span class="badge"><?php echo (int) $h->alerts_count; ?></span>
			<?php endif; ?>
		</a>
		<div class="dropdown-menu notification-menu">
			<div class="notification-title">
				<?php esc_html_e( 'Alerts', 'botblocker-security' ); ?>
				<?php if ( ! empty( $h->alerts ) ) : ?>
					<span class="float-end badge badge-default"><?php echo (int) $h->alerts_count; ?></span>
				<?php endif; ?>
			</div>
			<div class="content">
				<ul>
					<?php if ( empty( $h->alerts ) ) : ?>
					<li>
						<a href="#" class="clearfix">
							<div class="image">
								<i class="fas fa-thumbs-up bg-primary text-light"></i>
							</div>
							<span class="title"><?php esc_html_e( 'No active alerts', 'botblocker-security' ); ?></span>
							<span class="message"><?php esc_html_e( 'No issues found', 'botblocker-security' ); ?></span>
						</a>
					</li>
					<?php endif; ?>

					<?php foreach ( $h->alerts as $bbcs_alert ) : ?>
						<?php AlertNotificationItem::make()->withAlert( $bbcs_alert )->render(); ?>
					<?php endforeach; ?>
				</ul>
			</div>
		</div>
	</li>
	<?php
};
