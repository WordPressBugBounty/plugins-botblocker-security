<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BotBlocker\Component\InfoColumn;
use BotBlocker\Component\SettingsGroup;
use BotBlocker\Component\ActionButton;

return static function ( Botblocker_ToolsViewModel $data, bool $isActive ): void {
	?>
	<div role="tabpanel" class="bbcs-tabpanel bbcs-protect-layout" data-tabpanel="BotBlocker"<?php echo $isActive ? '' : ' hidden' ?>>
		<?php
		InfoColumn::make()
			->withDocPage( BotBlockerDocs::page( 'tools', 'botblocker-settings-save-and-move-your-setup' ) )
			->withIconImage( BOTBLOCKER_URL . 'public/icons/rocket.svg', __( 'BotBlocker Tools', 'botblocker-security' ) )
			->withDescription( __( 'Tools for blocking bots, filtering suspicious traffic, and optimizing server resources.', 'botblocker-security' ) )
			->withDescription( __( 'Configure protection rules and automate threat detection. Reducing junk traffic also improves load times.', 'botblocker-security' ) )
			->render();
		?>
		<div>
			<?php
			SettingsGroup::make()
				->withTitle( __( 'BotBlocker Settings', 'botblocker-security' ) )
				->withItems( static function (): void {
					ActionButton::make()
						->withId( 'bbcs-backup-data-settings' )
						->withIcon( 'import' )
						->withLabel( __( 'Export data and settings', 'botblocker-security' ) )
						->withTooltip( __( 'Export current settings and data.', 'botblocker-security' ) )
						->withDocLink( BotBlockerDocs::page( 'tools', 'export-data-and-settings' ) )
						->render();
					ActionButton::make()
						->withId( 'bbcs-import-data-settings' )
						->withIcon( 'upload' )
						->withLabel( __( 'Import data and settings', 'botblocker-security' ) )
						->withTooltip( __( 'Import settings and data from a backup.', 'botblocker-security' ) )
						->withDocLink( BotBlockerDocs::page( 'tools', 'import-data-and-settings' ) )
						->render();
				} )
				->render();
			?>
		</div>
	</div>
	<?php
};
