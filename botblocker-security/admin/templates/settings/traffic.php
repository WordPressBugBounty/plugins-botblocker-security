<?php

declare(strict_types=1);
if (!defined('ABSPATH')) exit;

use BotBlocker\Component\FieldPair;
use BotBlocker\Component\InfoColumn;
use BotBlocker\Component\SettingsGroup;
use BotBlocker\Component\ToggleOption;

return static function (Botblocker_SettingsViewModel $data, bool $isActive): void { ?>
    <div role="tabpanel" class="bbcs-tabpanel bbcs-protect-layout" data-tabpanel="traffic"<?php echo $isActive ? '' : ' hidden' ?>>
        <?php
        InfoColumn::make()
            ->withIconImage( BOTBLOCKER_URL . 'public/icons/traffic.svg', __( 'Traffic and Referrer Settings', 'botblocker-security' ) )
            ->withDescription( __( 'Manage referrer analysis, UTM processing, and iframe restrictions to control traffic sources.', 'botblocker-security' ) )
            ->withDescription( __( 'Use noindex and noarchive directives to prevent search engines from indexing blocked or UTM pages.', 'botblocker-security' ) )
            ->withDocLink( 'https://en.wikipedia.org/wiki/UTM_parameters', __( 'UTM', 'botblocker-security' ) )
            ->withDocLink( 'https://en.wikipedia.org/wiki/Query_string', __( 'Query string', 'botblocker-security' ) )
            ->withDocLink( 'https://en.wikipedia.org/wiki/Frame_(World_Wide_Web)', __( 'iFrame', 'botblocker-security' ) )
            ->render();
        ?>
        <div>
            <?php
            SettingsGroup::make()
                ->withTitle( __( 'Traffic and Referrer Settings', 'botblocker-security' ) )
                ->withItems( static function () use ( $data ): void {
                    FieldPair::make()
                        ->withItems( static function () use ( $data ): void {
                            ToggleOption::make()->withName( 'utm_referrer' )->withChecked( $data->is_checked( 'utm_referrer' ) )->withLabel( __( 'UTM Referrer Processing', 'botblocker-security' ) )->withTooltip( __( 'Track traffic sources and filter suspicious referrers via UTM parameters.', 'botblocker-security' ) )->render();
                            ToggleOption::make()->withName( 'check_get_ref' )->withChecked( $data->is_checked( 'check_get_ref' ) )->withLabel( __( 'Check GET Parameters in Referrer', 'botblocker-security' ) )->withTooltip( __( 'Scan the URL referrer for specific GET parameters to detect unwanted or suspicious traffic sources.', 'botblocker-security' ) )->render();
                        } )
                        ->render();
                    ToggleOption::make()->withName( 'iframe_stop' )->withChecked( $data->is_checked( 'iframe_stop' ) )->withLabel( __( 'Block Cross-Origin Iframes', 'botblocker-security' ) )->withTooltip( __( 'Block cross-origin iframes to prevent clickjacking.', 'botblocker-security' ) )->render();
                } )
                ->render();

            SettingsGroup::make()
                ->withTitle( __( 'Header Settings', 'botblocker-security' ) )
                ->withItems( static function () use ( $data ): void {
                    FieldPair::make()
                        ->withItems( static function () use ( $data ): void {
                            ToggleOption::make()->withName( 'noarchive' )->withChecked( $data->is_checked( 'noarchive' ) )->withLabel( __( 'Add noarchive to Blocked Pages', 'botblocker-security' ) )->withTooltip( __( 'Prevent search engines from caching blocked pages.', 'botblocker-security' ) )->render();
                            ToggleOption::make()->withName( 'utm_noindex' )->withChecked( $data->is_checked( 'utm_noindex' ) )->withLabel( __( 'Add noindex to UTM Pages', 'botblocker-security' ) )->withTooltip( __( 'Prevent search engines from indexing UTM pages to avoid duplicate content.', 'botblocker-security' ) )->render();
                        } )
                        ->render();
                } )
                ->render();
            ?>
        </div>
    </div>
<?php };
