<?php
use BotBlocker\Component\SystemInfo;

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
			<h2 class="card-title"><?php esc_html_e( 'System Status', 'botblocker-security' ); ?></h2>
		</header>
		<div class="card-body">
			<?php SystemInfo::make()->withInfo( $s->system_info )->render(); ?>
		</div>
	</section>
	<?php
};
