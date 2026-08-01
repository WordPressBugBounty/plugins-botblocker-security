<?php

declare(strict_types=1);
if (!defined('ABSPATH')) exit;

use BotBlocker\Component\InfoColumn;
use BotBlocker\Component\SettingsGroup;
use BotBlocker\Component\FieldPair;
use BotBlocker\Component\ToggleOption;
use BotBlocker\Component\CustomSelect;

return static function (Botblocker_SettingsViewModel $data, bool $isActive): void {
    $uniq_types = array(
        'host' => __( 'Unique Visitors (by IP)', 'botblocker-security' ),
        'hit'  => __( 'All hits', 'botblocker-security' ),
    );
    $report_periods = [ 3, 5, 7, 10, 14, 30 ];
    $gmt_offsets = [ -12, -11, -10, -9.5, -9, -8, -7, -6, -5, -4, -3.5, -3, -2, -1, 0, 1, 2, 3, 3.5, 4, 4.5, 5, 5.5, 5.75, 6, 6.5, 7, 8, 8.75, 9, 9.5, 10, 10.5, 11, 12, 13, 14 ];
    $rop = [];
    foreach ($report_periods as $d) $rop[(string)$d] = $data->store_period_label($d);
    $gop = [];
    foreach ($gmt_offsets as $o) $gop[(string)$o] = $data->gmt_label($o);

    $cache_dur = (string)($data->get('cache_ui_duration', '300'));
    $report_period = (string)($data->get('admin_report_period', '5'));
    $gmt_offset = (string)($data->get('admin_gmt_offset', '0'));
    $uniq_type = (string)($data->get('admin_uniq_type', 'host'));
?>
    <div role="tabpanel" class="bbcs-tabpanel bbcs-protect-layout" data-tabpanel="settings-ui"<?php echo $isActive ? '' : ' hidden' ?>>
        <?php
        InfoColumn::make()
            ->withIconImage( BOTBLOCKER_URL . 'public/icons/ui.svg', __( 'UI Settings', 'botblocker-security' ) )
            ->withDescription( __( 'Control admin interface caching and statistics display settings.', 'botblocker-security' ) )
            ->withDescription( __( 'Configure report period, timezone, and how statistics are counted.', 'botblocker-security' ) )
            ->withDocLink( $data->docs_url . '/log-retention-in-botblocker-how-to-manage-storage-period-time-zone-and-analytics/', __( 'Log retention', 'botblocker-security' ) )
            ->withDocLink( 'https://en.wikipedia.org/wiki/Time_zone', __( 'GMT and Time Zone', 'botblocker-security' ) )
            ->withDocLink( $data->docs_url . '/interface-caching-in-botblocker-configurable-cache-time-real-time-mode-and-wordpress-transients/', __( 'Cache UI', 'botblocker-security' ) )
            ->render();
        ?>
        <div>
            <?php
            SettingsGroup::make()
                ->withTitle( __( 'Interface Caching', 'botblocker-security' ) )
                ->withItems( static function () use ( $data, $cache_dur ): void {
                    FieldPair::make()
                        ->withItems( static function () use ( $data, $cache_dur ): void {
                            ToggleOption::make()->withName( 'cache_ui_data' )->withChecked( $data->is_checked( 'cache_ui_data' ) )->withLabel( __( 'Cache Plugin Interface', 'botblocker-security' ) )->withTooltip( __( 'Cache the admin interface for faster loading.', 'botblocker-security' ) )->render();

                            CustomSelect::make()
                                ->withName( 'cache_ui_duration' )
                                ->withValue( $cache_dur )
                                ->withOptions( $data->cache_durations )
                                ->withLabel( __( 'Cache Duration', 'botblocker-security' ) )
                                ->withTooltip( __( 'How long to cache the interface (in seconds).', 'botblocker-security' ) )
                                ->render();
                        } )
                        ->render();
                } )
                ->render();

            SettingsGroup::make()
                ->withTitle( __( 'Reports and Statistics', 'botblocker-security' ) )
                ->withItems( static function () use ( $data, $rop, $report_period, $gop, $gmt_offset, $uniq_types, $uniq_type ): void {
                    FieldPair::make()
                        ->withItems( static function () use ( $rop, $report_period, $gop, $gmt_offset ): void {
                            CustomSelect::make()
                                ->withName( 'admin_report_period' )
                                ->withValue( $report_period )
                                ->withOptions( $rop )
                                ->withLabel( __( 'Report Period:', 'botblocker-security' ) )
                                ->withTooltip( __( 'Days to include in reports.', 'botblocker-security' ) )
                                ->render();

                            CustomSelect::make()
                                ->withName( 'admin_gmt_offset' )
                                ->withValue( $gmt_offset )
                                ->withOptions( $gop )
                                ->withLabel( __( 'GMT Offset for Reports:', 'botblocker-security' ) )
                                ->withTooltip( __( 'Timezone for report timestamps.', 'botblocker-security' ) )
                                ->render();
                        } )
                        ->render();

                    CustomSelect::make()
                        ->withName( 'admin_uniq_type' )
                        ->withValue( $uniq_type )
                        ->withOptions( $uniq_types )
                        ->withLabel( __( 'Statistics Display Mode', 'botblocker-security' ) )
                        ->withTooltip( __( 'Show unique visitors (by IP) or all requests.', 'botblocker-security' ) )
                        ->render();
                } )
                ->render();
            ?>
        </div>
    </div>
<?php };
