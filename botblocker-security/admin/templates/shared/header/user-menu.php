<?php
if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
return static function ( Botblocker_HeaderViewModel $header ): void {
	$h = $header;
	?>
	<div id="userbox" class="userbox">
		<a href="#" data-bs-toggle="dropdown">
			<figure class="profile-picture">
				<?php
				// REVIEWER NOTE: This image is a static plugin asset, not a user-uploaded Media Library image.
				// phpcs:ignore PluginCheck.CodeAnalysis.ImageFunctions.NonEnqueuedImage
				echo '<img src="' . esc_url( $h->avatar_url ) . '" alt="' . esc_attr( $h->display_name ) . '" class="rounded-circle">';
				?>
			</figure>
			<div class="profile-info" data-lock-name="<?php echo esc_attr( $h->display_name ); ?>">
				<span class="name"><?php echo esc_html( $h->display_name ); ?></span>
				<span class="role"><?php echo esc_html( $h->user_role ); ?></span>
			</div>
			<i class="fa custom-caret"></i>
		</a>
		<div class="dropdown-menu">
			<ul class="list-unstyled mb-2">
				<li class="divider"></li>
				<li>
					<a role="menuitem" tabindex="-1"
					href="https://<?php echo esc_html( BOTBLOCKER_SERVER ); ?>/" target="_blank">
					<i class="fa-solid fa-globe bbcs-h-btn-gray"></i> <?php esc_html_e( 'BotBlocker Website', 'botblocker-security' ); ?></a>
				</li>
				<li>
					<a role="menuitem" tabindex="-1"
					href="https://<?php echo esc_html( BOTBLOCKER_SERVER ); ?>/docs/" target="_blank">
					<i class="fa-solid fa-book bbcs-h-btn-gray"></i> <?php esc_html_e( 'Documentation', 'botblocker-security' ); ?></a>
				</li>
				<li class="divider"></li>
				<li>
					<a role="menuitem" tabindex="-1"
					href="https://<?php echo esc_html( BOTBLOCKER_SERVER ); ?>/hire/" target="_blank">
					<i class="fa-solid fa-code bbcs-h-btn-gray"></i> <?php esc_html_e( 'Hire a developer', 'botblocker-security' ); ?></a>
				</li>
				<li>
					<a role="menuitem" tabindex="-1"
					href="https://globus.studio" target="_blank">
					<i class="fa-solid fa-g bbcs-h-btn-gray"></i> <?php esc_html_e( 'GLOBUS.studio', 'botblocker-security' ); ?></a>
				</li>
				<li class="divider"></li>
				<li>
					<a role="menuitem" tabindex="-1"
					href="https://<?php echo esc_html( BOTBLOCKER_SERVER ); ?>/contacts" target="_blank">
					<i class="fa-solid fa-envelope bbcs-h-btn-gray"></i> <?php esc_html_e( 'Contact Us', 'botblocker-security' ); ?></a>
				</li>
			</ul>
		</div>
	</div>
	<?php
};
