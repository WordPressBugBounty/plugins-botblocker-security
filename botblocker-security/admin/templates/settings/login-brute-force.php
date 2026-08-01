<?php
declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use BotBlocker\Component\InfoColumn;
use BotBlocker\Component\SettingsGroup;
use BotBlocker\Component\ToggleOption;
use BotBlocker\Component\TextInput;
use BotBlocker\Component\FieldPair;

return static function ( Botblocker_SettingsViewModel $data, bool $isActive ): void {
	$attempts = (string) $data->get( 'login_brutforce_attempts', '5' );
	$period   = (string) $data->get( 'login_brutforce_period', '900' );
	$primary  = (string) $data->get( 'login_brutforce_primary_block_time', '900' );
	$secondary = (string) $data->get( 'login_brutforce_secondary_block_time', '1800' );
	?>
	<div role="tabpanel" class="bbcs-tabpanel bbcs-protect-layout" data-tabpanel="brute-force"<?php echo $isActive ? '' : ' hidden' ?>>
		<?php
		InfoColumn::make()
			->withIconImage( BOTBLOCKER_URL . 'public/icons/security.svg', __( 'Login Brute-Force Protection', 'botblocker-security' ) )
			->withDescription( __( 'Prevent password guessing attacks with temporary IP lockouts after failed login attempts.', 'botblocker-security' ) )
			->withDescription( __( 'Adjust attempt limits and block times to balance security and usability.', 'botblocker-security' ) )
			->withDocLink( 'https://en.wikipedia.org/wiki/Brute-force_attack', __( 'Brute-force attack', 'botblocker-security' ) )
			->render();
		?>
		<div>
			<?php
			SettingsGroup::make()
				->withTitle( __( 'Login Brute-Force Protection', 'botblocker-security' ) )
				->withItems( static function () use ( $data, $attempts, $period, $primary, $secondary ): void {
					ToggleOption::make()->withName( 'login_brutforce_enabled' )->withChecked( $data->is_checked( 'login_brutforce_enabled' ) )->withLabel( __( 'Enable Login Brute-Force Protection', 'botblocker-security' ) )->withTooltip( __( 'Activate brute-force login protection.', 'botblocker-security' ) )->render();

					FieldPair::make()
						->withItems( static function () use ( $attempts, $period ): void {
							TextInput::make()
								->withName( 'login_brutforce_attempts' )
								->withValue( $attempts )
								->withType( 'number' )
								->withLabel( __( 'Failed Attempts Before Blocking:', 'botblocker-security' ) )
								->withTooltip( __( 'Failed attempts allowed before blocking the IP.', 'botblocker-security' ) )
								->withMin( '1' )
								->withEditable()
								->render();
							TextInput::make()
								->withName( 'login_brutforce_period' )
								->withValue( $period )
								->withType( 'number' )
								->withLabel( __( 'Failed Attempt Time Window (seconds):', 'botblocker-security' ) )
								->withTooltip( __( 'Time period for counting failed login attempts.', 'botblocker-security' ) )
								->withMin( '1' )
								->withEditable()
								->render();
						} )
						->render();

					FieldPair::make()
						->withItems( static function () use ( $primary, $secondary ): void {
							TextInput::make()
								->withName( 'login_brutforce_primary_block_time' )
								->withValue( $primary )
								->withType( 'number' )
								->withLabel( __( 'Block Time (seconds):', 'botblocker-security' ) )
								->withTooltip( __( 'Block duration for first-time offenders.', 'botblocker-security' ) )
								->withMin( '1' )
								->withEditable()
								->render();
							TextInput::make()
								->withName( 'login_brutforce_secondary_block_time' )
								->withValue( $secondary )
								->withType( 'number' )
								->withLabel( __( 'Repeat Block Time (seconds):', 'botblocker-security' ) )
								->withTooltip( __( 'Block duration for repeat offenders.', 'botblocker-security' ) )
								->withMin( '1' )
								->withEditable()
								->render();
						} )
						->render();
				} )
				->render();
			?>
		</div>
	</div>
	<?php
};
