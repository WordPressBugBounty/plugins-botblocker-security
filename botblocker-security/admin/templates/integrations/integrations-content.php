<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

use BotBlocker\Component\SidebarNav;

return static function (Botblocker_IntegrationsViewModel $data): void {
	$active_tab = $data->active_tab_id;

?>
	<div class="bbcs-settings-layout">
		<?php
		SidebarNav::make()
			->withGroups( $data->nav_groups )
			->withAriaLabel( __( 'Integrations sections', 'botblocker-security' ) )
			->withSearchPlaceholder( __( 'Find integration…', 'botblocker-security' ) )
			->withDefaultIcon( 'search' )
			->withDefaultLabel( __( 'reCaptcha v2', 'botblocker-security' ) )
			->render();
		?>

		<div class="bbcs-settings-content">
			<div class="bbcs-card bbcs-card-pad bbcs-mb-5h">
				<?php
				foreach ($data->tabpanels as $tab_id => $tab_file) {
					(require $tab_file)($data, $tab_id === $active_tab);
				}
				?>
			</div>
		</div>
	</div>
<?php
};
