<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
	exit;
}

use BotBlocker\Component\FieldPair;
use BotBlocker\Component\InfoColumn;
use BotBlocker\Component\SettingsGroup;
use BotBlocker\Component\ToggleOption;

return static function (Botblocker_SettingsViewModel $data, bool $isActive): void {
	$t = $data->sidebar->toggles;
?>
	<div role="tabpanel" class="bbcs-tabpanel bbcs-protect-layout" data-tabpanel="performance"<?php echo $isActive ? '' : ' hidden' ?>>
		<?php
		InfoColumn::make()
			->withIconImage( BOTBLOCKER_URL . 'public/icons/status.svg', __( 'Early Phase', 'botblocker-security' ) )
			->withDescription( __( 'Early Phase settings control how BotBlocker loads before WordPress fully initializes. Toggle Early Init and MU-plugin for earlier bot filtering at the earliest possible stage.', 'botblocker-security' ) )
			->withDescription( __( 'These options require no form save - they apply immediately and are safe to experiment with.', 'botblocker-security' ) )
			->withDocLink( $data->docs_url . '/early-init/', __( 'Early Init', 'botblocker-security' ) )
			->withDocLink( $data->docs_url . '/mu-plugin/', __( 'MU-plugin', 'botblocker-security' ) )
			->render();
		?>
		<div>
			<?php
			SettingsGroup::make()
				->withTitle( __( 'Early Phase toggles', 'botblocker-security' ) )
				->withItems( static function () use ( $data, $t ): void {
					FieldPair::make()
						->withItems( static function () use ( $data, $t ): void {
							ToggleOption::make()
								->withName( 'early_init_enable' )
								->withChecked( (bool) $t->early_init_checked )
								->withDisabled( (bool) $t->early_init_disabled )
								->withColor( ToggleOption::COLOR_AMBER )
								->withLabel( __( 'Early Init', 'botblocker-security' ) )
								->withBadge( __( 'PRO', 'botblocker-security' ), ToggleOption::BADGE_PRO )
								->withBadge( __( 'Add-on', 'botblocker-security' ), ToggleOption::BADGE_ADDON )
								->withTooltip( __( 'Loads black/white IP lists via wp-config before WordPress core starts', 'botblocker-security' ) )
								->withAjax( 'bbcs_toggle_early_phase_in_db', 'early_init_enable' )
								->render();

							ToggleOption::make()
								->withName( 'mu_enable' )
								->withChecked( (bool) $t->mu_checked )
								->withLabel( __( 'MU-plugin', 'botblocker-security' ) )
								->withTooltip( __( 'MU mode loads black/white IP lists before regular plugins and WordPress core', 'botblocker-security' ) )
								->withAjax( 'bbcs_toggle_early_phase_in_db', 'mu_enable' )
								->render();
						} )
						->render();
				} )
				->render();
			?>
		</div>
	</div>
<?php };
