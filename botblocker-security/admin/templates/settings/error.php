<?php

declare(strict_types=1);

use BotBlocker\Component\Base;
if (!defined('ABSPATH')) exit;

use BotBlocker\Component\InfoColumn;
use BotBlocker\Component\SettingsGroup;
use BotBlocker\Component\CustomSelect;
use BotBlocker\Component\TextInput;
use BotBlocker\Component\FieldPair;

return static function (Botblocker_SettingsViewModel $data, bool $isActive): void {
    $test_code = (string)($data->get('header_test_code', ''));
    $error_code = (string)($data->get('header_error_code', ''));
    $time_ban = (string) $data->get('time_ban', '200');
    $time_ban_2 = (string) $data->get('time_ban_2', '400');
?>
    <div role="tabpanel" class="bbcs-tabpanel bbcs-protect-layout" data-tabpanel="error"<?php echo $isActive ? '' : ' hidden' ?>>
        <?php
        InfoColumn::make()
        	->withDocPage( BotBlockerDocs::page( 'blocks-and-responses' ) )
            ->withIconImage( BOTBLOCKER_URL . 'public/icons/error-access.svg', __( 'Error and Access Settings', 'botblocker-security' ) )
            ->withDescription( __( 'Configure HTTP response codes for blocked visitors, ban durations, and search engine directives.', 'botblocker-security' ) )
            ->withDescription( __( 'Repeated violations result in progressively longer bans. X-Robots-Tag directives tell search engines how to index and cache your pages.', 'botblocker-security' ) )
            ->render();
        ?>
        <div>
            <?php
            SettingsGroup::make()
                ->withTitle( __( 'Error and Access Settings', 'botblocker-security' ) )
                ->withItems( static function () use ( $data, $test_code, $error_code, $time_ban, $time_ban_2 ): void {
                    FieldPair::make()
                        ->withItems( static function () use ( $data, $test_code, $error_code ): void {
                            CustomSelect::make()
                                ->withName( 'header_test_code' )
                                ->withValue( $test_code )
                                ->withOptions( $data->error_headers )
                                ->withLabel( __( 'Test Response Code:', 'botblocker-security' ) )
                                ->withTooltip( __( 'HTTP response code returned for verification test requests.', 'botblocker-security' ) )
                                ->withDocLink( BotBlockerDocs::url( 'header_test_code' ) )
                                ->render();

                            CustomSelect::make()
                                ->withName( 'header_error_code' )
                                ->withValue( $error_code )
                                ->withOptions( $data->error_headers )
                                ->withLabel( __( 'Block Response Code:', 'botblocker-security' ) )
                                ->withTooltip( __( 'HTTP response code returned for blocked requests.', 'botblocker-security' ) )
                                ->withDocLink( BotBlockerDocs::url( 'header_error_code' ) )
                                ->render();
                        } )
                        ->render();

                    FieldPair::make()
                        ->withItems( static function () use ( $time_ban, $time_ban_2 ): void {
                            TextInput::make()
                                ->withName( 'time_ban' )
                                ->withValue( $time_ban )
                                ->withType( 'number' )
                                ->withLabel( __( 'Block Time (seconds):', 'botblocker-security' ) )
                                ->withTooltip( __( 'How long to block a visitor before allowing a retry.', 'botblocker-security' ) )
                                ->withSuffix( __( 'sec', 'botblocker-security' ) )
                                ->withEditable()
                                ->withDocLink( BotBlockerDocs::url( 'time_ban' ) )
                                ->render();
                            TextInput::make()
                                ->withName( 'time_ban_2' )
                                ->withValue( $time_ban_2 )
                                ->withType( 'number' )
                                ->withLabel( __( 'Repeat Block Time (seconds):', 'botblocker-security' ) )
                                ->withTooltip( __( 'Longer ban applied after repeated failures.', 'botblocker-security' ) )
                                ->withSuffix( __( 'sec', 'botblocker-security' ) )
                                ->withEditable()
                                ->withDocLink( BotBlockerDocs::url( 'time_ban_2' ) )
                                ->render();
                        } )
                        ->render();
                } )
                ->render();

            SettingsGroup::make()
                ->withTitle( __( 'Headers for Search Engines', 'botblocker-security' ) )
                ->withItems( static function () use ( $data ): void {
                    ?>
                    <div data-anchor="x_robots_directives">
                    <?php
                    foreach ($data->x_robots_directives as $d => $dv) {
                        $disp = !empty($dv) ? $d . ':' . $dv : $d;
                        ?>
                        <div class="bbcs-option bbcs-hoverbg">
                            <button class="bbcs-toggle<?php echo $data->get_selected_directive($d) ? ' is-on' : ''; ?>" role="switch" aria-checked="<?php echo $data->get_selected_directive($d) ? 'true' : 'false'; ?>" type="button">
                                <span class="bbcs-toggle-knob"></span>
                            </button>
                            <input type="checkbox" name="x_robots_directives[]" value="<?php echo esc_attr($d); ?>" <?php checked($data->get_selected_directive($d)); ?> style="position:absolute;opacity:0;pointer-events:none">
                            <span class="bbcs-option-label"><?php echo esc_html($disp); ?></span>
                            <?php echo Base::help( sprintf(/* translators: %s: X-Robots-Tag directive name (e.g., noindex, nofollow) */ __( 'Enable %s directive in X-Robots-Tag headers', 'botblocker-security' ), $disp), BotBlockerDocs::url( 'x_robots_directives' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
                        </div>
                        <?php
                    }
                    ?>
                    </div>
                    <?php
                } )
                ->render();

            if ( $data->get_selected_directive( 'noindex' ) || $data->get_selected_directive( 'none' ) || $data->get_selected_directive( 'nofollow' ) ) : ?>
                <div class="bbcs-card bbcs-card-pad bbcs-amber-card">
                    <div class="bbcs-row bbcs-g-2h">
                        <svg class="bbcs-ico bbcs-ico--md bbcs-tx-amber">
                            <use href="#bbcs-i-warning"></use>
                        </svg>
                        <span class="bbcs-fs-xs bbcs-tx-amber"><?php esc_html_e( 'noindex, none or nofollow is on. Search engines get this header on every page, so they can remove your whole site from search results.', 'botblocker-security' ); ?></span>
                    </div>
                </div>
            <?php endif; ?>
        </div>
    </div>
<?php };
