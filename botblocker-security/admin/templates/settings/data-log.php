<?php

declare(strict_types=1);
if (!defined('ABSPATH')) exit;

use BotBlocker\Component\InfoColumn;
use BotBlocker\Component\SettingsGroup;
use BotBlocker\Component\FieldPair;
use BotBlocker\Component\ToggleOption;
use BotBlocker\Component\RetentionDaysSelect;

return static function ( Botblocker_SettingsViewModel $data, bool $isActive ): void {
	$store_period = $data->get_store_period();

	$audit_retention_days = $data->get_audit_retention_days();

	$roles_map = $data->audit_roles_map();
	$wp_roles  = $data->wp_roles_list();
	?>
	<div role="tabpanel" class="bbcs-tabpanel bbcs-protect-layout" data-tabpanel="data-log"<?php echo $isActive ? '' : ' hidden' ?>>
		<?php
		InfoColumn::make()
			->withIconImage( BOTBLOCKER_URL . 'public/icons/data-log-processing.svg', __( 'Data Log and Processing', 'botblocker-security' ) )
			->withDescription( __( 'Configure what visitor data BotBlocker records for threat detection and analytics.', 'botblocker-security' ) )
			->withDescription( __( 'Set the log retention period and enable daylight saving time adjustment if needed.', 'botblocker-security' ) )
			->withDocLink( $data->docs_url . '/how-botblocker-detects-browser-version-os-and-device-type-pc-mobile-or-tablet/', __( 'How BotBlocker Detects Browser Version, OS, and Device Type', 'botblocker-security' ) )
			->withDocLink( $data->docs_url . '/log-retention-in-botblocker-how-to-manage-storage-period-time-zone-and-analytics/', __( 'Store logs', 'botblocker-security' ) )
			->render();
		?>
		<div>
			<?php
			SettingsGroup::make()
				->withTitle( __( 'Data Log and Processing', 'botblocker-security' ) )
				->withItems( static function () use ( $data, $store_period ): void {
					FieldPair::make()
						->withItems( static function () use ( $data ): void {
							ToggleOption::make()->withName( 'get_browser_type' )->withChecked( $data->is_checked( 'get_browser_type' ) )->withLabel( __( 'Record Browser Type', 'botblocker-security' ) )->withTooltip( __( 'Extract and store visitor browser name and version from User-Agent header.', 'botblocker-security' ) )->render();
							ToggleOption::make()->withName( 'get_os_type' )->withChecked( $data->is_checked( 'get_os_type' ) )->withLabel( __( 'Record OS Type', 'botblocker-security' ) )->withTooltip( __( 'Extract and store visitor operating system from User-Agent header.', 'botblocker-security' ) )->render();
						} )
						->render();

					FieldPair::make()
						->withItems( static function () use ( $data ): void {
							ToggleOption::make()->withName( 'get_device_type' )->withChecked( $data->is_checked( 'get_device_type' ) )->withLabel( __( 'Record Device Type', 'botblocker-security' ) )->withTooltip( __( 'Extract and store visitor device category (PC, Phone, Tablet, TV, Box) from User-Agent header.', 'botblocker-security' ) )->render();
							ToggleOption::make()->withName( 'daylight_saving_time' )->withChecked( $data->is_checked( 'daylight_saving_time' ) )->withLabel( __( 'Adjust for Daylight Saving Time', 'botblocker-security' ) )->withTooltip( __( 'Adjust recorded timestamps for your timezone\'s daylight saving time. Ensures visit times display correctly in logs and reports year-round.', 'botblocker-security' ) )->render();
						} )
						->render();

					FieldPair::make()
						->withItems( static function () use ( $store_period ): void {
							RetentionDaysSelect::make()
								->withName( 'admin_store_period' )
								->withValue( $store_period )
								->withLabel( __( 'Log Retention Period:', 'botblocker-security' ) )
								->withTooltip( __( 'How many days to keep raw visitor log data before automatic cleanup.', 'botblocker-security' ) )
								->render();
						} )
						->render();
				} )
				->render();

			SettingsGroup::make()
				->withTitle( __( 'Audit Log', 'botblocker-security' ) )
				->withItems( static function () use ( $data, $audit_retention_days, $wp_roles, $roles_map ): void {
					FieldPair::make()
						->withItems( static function () use ( $data, $audit_retention_days ): void {
							ToggleOption::make()->withName( 'audit_log_enable' )->withChecked( $data->is_audit_log_enabled() )->withLabel( __( 'Enable Audit Log', 'botblocker-security' ) )->withTooltip( __( 'Record WordPress admin actions in a dedicated audit log table.', 'botblocker-security' ) )->render();

							RetentionDaysSelect::make()
								->withName( 'audit_log_retention_days' )
								->withValue( $audit_retention_days )
								->withLabel( __( 'Audit Log Retention:', 'botblocker-security' ) )
								->withTooltip( __( 'How long to keep audit log entries before automatic cleanup.', 'botblocker-security' ) )
								->render();
						} )
						->render();

					?>
					<div class="bbcs-setgroup-head bbcs-mt-3"><?php esc_html_e( 'Roles to Audit', 'botblocker-security' ); ?></div>
					<div data-anchor="audit_log_roles" class="bbcs-field-pair">
						<?php foreach ( $wp_roles as $role_key => $role ) :
							$enabled   = ! isset( $roles_map[ $role_key ] ) || (string) $roles_map[ $role_key ] === '1';
							$role_name = translate_user_role( $role['name'] );
							?>
							<div class="bbcs-option bbcs-hoverbg">
								<button class="bbcs-toggle<?php echo $enabled ? ' is-on' : ''; ?>" role="switch" type="button" aria-checked="<?php echo $enabled ? 'true' : 'false'; ?>" data-field="audit_log_roles_<?php echo esc_attr( $role_key ); ?>"><span class="bbcs-toggle-knob"></span></button>
								<input type="hidden" name="audit_log_roles[<?php echo esc_attr( $role_key ); ?>]" value="<?php echo $enabled ? '1' : '0'; ?>">
								<span class="bbcs-option-label"><?php echo esc_html( $role_name ); ?></span>
								<span class="bbcs-help"><span class="bbcs-help-q">?</span><span class="bbcs-help-tip"><?php echo esc_attr( sprintf( /* translators: %s: WordPress role name (e.g., Administrator, Editor) */ __( 'Record audit log entries for actions by %s', 'botblocker-security' ), $role_name ) ); ?></span></span>
							</div>
						<?php endforeach; ?>
					</div>
					<?php
				} )
				->render();
			?>
		</div>
	</div>
	<?php
};
