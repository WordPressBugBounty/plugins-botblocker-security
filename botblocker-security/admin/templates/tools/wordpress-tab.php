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
	<div role="tabpanel" class="bbcs-tabpanel bbcs-protect-layout" data-tabpanel="WordPress"<?php echo $isActive ? '' : ' hidden' ?>>
		<?php
		InfoColumn::make()
			->withIconImage( BOTBLOCKER_URL . 'public/icons/wordpress.svg', __( 'WordPress core tuning', 'botblocker-security' ) )
			->withDescription( __( 'Security and performance tools for your WordPress site: block unwanted traffic, filter suspicious requests, and optimize resource usage.', 'botblocker-security' ) )
			->withDescription( __( 'Configure security settings and automate bot detection to reduce server load and improve loading speed.', 'botblocker-security' ) )
			->withDocLink( 'https://developer.wordpress.org/advanced-administration/debug/debug-wordpress/', __( 'Debug WordPress', 'botblocker-security' ) )
			->render();
		?>
		<div>
			<?php
			SettingsGroup::make()
				->withTitle( __( 'WordPress Service', 'botblocker-security' ) )
				->withItems( static function (): void {
					ActionButton::make()
						->withId( 'bbcs-site-health' )
						->withIcon( 'heart' )
						->withLabel( __( 'Site Health', 'botblocker-security' ) )
						->withTooltip( __( 'Open WordPress Site Health diagnostic tool', 'botblocker-security' ) )
						->render();
					ActionButton::make()
						->withId( 'bbcs-clear-wp-log' )
						->withIcon( 'trash' )
						->withLabel( __( 'Clear Debug Log', 'botblocker-security' ) )
						->withTooltip( __( 'Clear WordPress debug log', 'botblocker-security' ) )
						->render();
					ActionButton::make()
						->withId( 'bbcs-download-wp-log' )
						->withIcon( 'import' )
						->withLabel( __( 'Download Debug Log', 'botblocker-security' ) )
						->withTooltip( __( 'Download WordPress debug log file', 'botblocker-security' ) )
						->render();
				} )
				->render();
			?>
		</div>
	</div>
	<?php
};
