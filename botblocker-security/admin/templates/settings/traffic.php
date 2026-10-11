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
        	->withDocPage( BotBlockerDocs::page( 'search-referrers' ) )
            ->withIconImage( BOTBLOCKER_URL . 'public/icons/traffic.svg', __( 'Traffic and Referrer Settings', 'botblocker-security' ) )
            ->withDescription( __( 'Manage referrer analysis, UTM processing, and iframe restrictions to control traffic sources.', 'botblocker-security' ) )
            ->withDescription( __( 'Use noindex and noarchive directives to control how search engines index UTM pages and the pages that BotBlocker lets through.', 'botblocker-security' ) )
            ->render();
        ?>
        <div>
            <?php
            SettingsGroup::make()
                ->withTitle( __( 'Traffic and Referrer Settings', 'botblocker-security' ) )
                ->withItems( static function () use ( $data ): void {
                    FieldPair::make()
                        ->withItems( static function () use ( $data ): void {
                            ToggleOption::make()->withName( 'utm_referrer' )->withChecked( $data->is_checked( 'utm_referrer' ) )->withLabel( __( 'UTM Referrer Processing', 'botblocker-security' ) )->withTooltip( __( 'Track traffic sources and filter suspicious referrers via UTM parameters.', 'botblocker-security' ) )->withDocLink( BotBlockerDocs::url( 'utm_referrer' ) )->render();
                            ToggleOption::make()->withName( 'check_get_ref' )->withChecked( $data->is_checked( 'check_get_ref' ) )->withLabel( __( 'Check GET Parameters in Referrer', 'botblocker-security' ) )->withTooltip( __( 'Scan the URL referrer for specific GET parameters to detect unwanted or suspicious traffic sources.', 'botblocker-security' ) )->withDocLink( BotBlockerDocs::url( 'check_get_ref' ) )->render();
                        } )
                        ->render();
                    ToggleOption::make()->withName( 'iframe_stop' )->withChecked( $data->is_checked( 'iframe_stop' ) )->withLabel( __( 'Block Cross-Origin Iframes', 'botblocker-security' ) )->withTooltip( __( 'Block cross-origin iframes to prevent clickjacking.', 'botblocker-security' ) )->withDocLink( BotBlockerDocs::url( 'iframe_stop' ) )->render();
                } )
                ->render();

            SettingsGroup::make()
                ->withTitle( __( 'Header Settings', 'botblocker-security' ) )
                ->withItems( static function () use ( $data ): void {
                    FieldPair::make()
                        ->withItems( static function () use ( $data ): void {
                            ToggleOption::make()->withName( 'noarchive' )->withChecked( $data->is_checked( 'noarchive' ) )->withLabel( __( 'Add noarchive to All Pages', 'botblocker-security' ) )->withTooltip( __( 'Ask search engines not to show a cached copy of the pages that BotBlocker lets through.', 'botblocker-security' ) )->withDocLink( BotBlockerDocs::url( 'noarchive' ) )->render();
                            ToggleOption::make()->withName( 'utm_noindex' )->withChecked( $data->is_checked( 'utm_noindex' ) )->withLabel( __( 'Add noindex to UTM Pages', 'botblocker-security' ) )->withTooltip( __( 'Prevent search engines from indexing UTM pages to avoid duplicate content.', 'botblocker-security' ) )->withDocLink( BotBlockerDocs::url( 'utm_noindex' ) )->render();
                        } )
                        ->render();
                } )
                ->render();
            ?>
        </div>
    </div>
<?php };
