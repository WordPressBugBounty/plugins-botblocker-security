<?php

declare(strict_types=1);
if (!defined('ABSPATH')) exit;

use BotBlocker\Component\InfoColumn;
use BotBlocker\Component\SettingsGroup;
use BotBlocker\Component\FieldPair;
use BotBlocker\Component\ToggleOption;
use BotBlocker\Component\CustomSelect;

return static function (Botblocker_SettingsViewModel $data, bool $isActive): void {
    $freq = (string)($data->get('regular_notifications_frequency', ''));
    $freq_opts = [
        "disabled" => __("Disabled", "botblocker-security"),
        "daily" => __("Every day", "botblocker-security"),
        "twice_week" => __("Twice a week", "botblocker-security"),
        "monthly" => __("Once a month", "botblocker-security")
    ];
?>
    <div role="tabpanel" class="bbcs-tabpanel bbcs-protect-layout" data-tabpanel="notifications"<?php echo $isActive ? '' : ' hidden' ?>>
        <?php
        InfoColumn::make()
            ->withIconImage( BOTBLOCKER_URL . 'public/icons/notification.svg', __( 'Notifications', 'botblocker-security' ) )
            ->withDescription( __( 'Configure how and when you receive alerts about bot activity.', 'botblocker-security' ) )
            ->withDescription( __( 'Choose notification channels, set up load alerts, and configure report frequency.', 'botblocker-security' ) )
            ->render();
        ?>
        <div>
            <?php
            SettingsGroup::make()
                ->withTitle( __( 'Notification Types', 'botblocker-security' ) )
                ->withItems( static function () use ( $data ): void {
                    FieldPair::make()
                        ->withItems( static function () use ( $data ): void {
                            ToggleOption::make()->withName( 'email_notifications' )->withChecked( $data->is_checked( 'email_notifications' ) )->withLabel( __( 'Email', 'botblocker-security' ) )->withTooltip( __( 'Receive security alerts via email.', 'botblocker-security' ) )->render();
                        } )
                        ->render();
                } )
                ->render();

            SettingsGroup::make()
                ->withTitle( __( 'Notification Settings', 'botblocker-security' ) )
                ->withItems( static function () use ( $data, $freq_opts, $freq ): void {
                    FieldPair::make()
                        ->withItems( static function () use ( $data, $freq_opts, $freq ): void {
                            ToggleOption::make()->withName( 'critical_load_notifications' )->withChecked( $data->is_checked( 'critical_load_notifications' ) )->withLabel( __( 'Notify on Critical Server Load', 'botblocker-security' ) )->withTooltip( __( 'Alert when server load is critical or unusual bot activity is detected.', 'botblocker-security' ) )->render();

                            CustomSelect::make()
                                ->withName( 'regular_notifications_frequency' )
                                ->withValue( $freq )
                                ->withOptions( $freq_opts )
                                ->withLabel( __( 'Regular Report Frequency', 'botblocker-security' ) )
                                ->withTooltip( __( 'How often to receive status reports.', 'botblocker-security' ) )
                                ->render();
                        } )
                        ->render();
                } )
                ->render();

            ?>
        </div>
    </div>
<?php };
