<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
return static function ( Botblocker_SidebarViewModel $sidebar ): void {
	$s = $sidebar;
	?>
	<section class="card bbcs-card-border-left ">
		<header class="card-header bbcs_small_header">
			<div class="card-actions bbcs_header_controls">
				<span class="bbcs-help" style="display:inline-flex">
					<a href="<?php echo esc_url( $s->settings_url ); ?>"><i class="fa-solid fa-gear bbcs-h-btn-gray"></i></a>
					<span class="bbcs-help-tip"><?php esc_html_e( 'BotBlocker Settings', 'botblocker-security' ); ?></span>
				</span>
			</div>
			<h2 class="card-title"><?php esc_html_e( 'Security Updates and Offers', 'botblocker-security' ); ?></h2>
			<p class="card-subtitle"><?php esc_html_e( 'Get security updates and offers by email', 'botblocker-security' ); ?></p>
		</header>
		<div class="card-body">
			<input value="<?php echo esc_attr( $s->contact_email ); ?>" type="email" id="bbcs_contact_email" class="form-control mb-2" placeholder="<?php esc_attr_e( 'Your email', 'botblocker-security' ); ?>">
			<button type="button" id="bbcs_send_activation_btn" class="mt-2 btn btn-sm bbcs-btn-primary-cta">
				<?php esc_html_e( 'Subscribe', 'botblocker-security' ); ?>
			</button>
			<div id="bbcs_activation_response" class="mt-2" style="display: none;"></div>
		</div>
	</section>
	<?php
};
