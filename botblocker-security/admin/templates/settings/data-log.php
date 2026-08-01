<?php

declare(strict_types=1);
if (!defined('ABSPATH')) exit;

use BotBlocker\Component\InfoColumn;
use BotBlocker\Component\SettingsGroup;
use BotBlocker\Component\FieldPair;
use BotBlocker\Component\ToggleOption;
use BotBlocker\Component\CustomSelect;

return static function (Botblocker_SettingsViewModel $data, bool $isActive): void {
    $periods = [3, 5, 7, 10, 14, 30];
    $rop = [];
    foreach ($periods as $d) {
        $rop[(string)$d] = $data->store_period_label($d);
    }
    $store_period = (string)($data->get('admin_store_period', '7'));
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
                ->withItems( static function () use ( $data, $rop, $store_period ): void {
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
                        ->withItems( static function () use ( $rop, $store_period ): void {
                            CustomSelect::make()
                                ->withName( 'admin_store_period' )
                                ->withValue( $store_period )
                                ->withOptions( $rop )
                                ->withLabel( __( 'Log Retention Period:', 'botblocker-security' ) )
                                ->withTooltip( __( 'How many days to keep raw visitor log data before automatic cleanup.', 'botblocker-security' ) )
                                ->render();
                        } )
                        ->render();
                } )
                ->render();
            ?>
        </div>
    </div>
<?php };
