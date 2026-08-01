<?php

declare(strict_types=1);
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
            ->withIconImage( BOTBLOCKER_URL . 'public/icons/error-access.svg', __( 'Error and Access Settings', 'botblocker-security' ) )
            ->withDescription( __( 'Configure HTTP response codes for blocked visitors, ban durations, and search engine directives.', 'botblocker-security' ) )
            ->withDescription( __( 'Repeated violations result in progressively longer bans. X-Robots-Tag directives prevent SEO penalties on blocked pages.', 'botblocker-security' ) )
            ->withDocLink( 'https://en.wikipedia.org/wiki/HTTP', __( 'HTTP', 'botblocker-security' ) )
            ->withDocLink( $data->docs_url . '/what-is-http-understanding-protocol-versions-and-blocking-http-1-0-in-botblocker/', __( 'Old HTTP versions', 'botblocker-security' ) )
            ->withDocLink( 'https://en.wikipedia.org/wiki/List_of_HTTP_status_codes', __( 'HTTP Codes', 'botblocker-security' ) )
            ->withDocLink( $data->docs_url . '/what-is-a-proxy-types-of-proxies-and-how-botblocker-detects-them/', __( 'HTTP proxy', 'botblocker-security' ) )
            ->withDocLink( 'https://en.wikipedia.org/wiki/List_of_HTTP_header_fields/', __( 'Headers', 'botblocker-security' ) )
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
                                ->render();

                            CustomSelect::make()
                                ->withName( 'header_error_code' )
                                ->withValue( $error_code )
                                ->withOptions( $data->error_headers )
                                ->withLabel( __( 'Block Response Code:', 'botblocker-security' ) )
                                ->withTooltip( __( 'HTTP response code returned for blocked requests.', 'botblocker-security' ) )
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
                                ->render();
                            TextInput::make()
                                ->withName( 'time_ban_2' )
                                ->withValue( $time_ban_2 )
                                ->withType( 'number' )
                                ->withLabel( __( 'Repeat Block Time (seconds):', 'botblocker-security' ) )
                                ->withTooltip( __( 'Longer ban applied after repeated failures.', 'botblocker-security' ) )
                                ->withSuffix( __( 'sec', 'botblocker-security' ) )
                                ->withEditable()
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
                            <span class="bbcs-help">
                                <span class="bbcs-help-q">?</span>
                                <span class="bbcs-help-tip"><?php echo esc_attr(sprintf(/* translators: %s: X-Robots-Tag directive name (e.g., noindex, nofollow) */ __( 'Enable %s directive in X-Robots-Tag headers', 'botblocker-security' ), $disp)); ?></span>
                            </span>
                        </div>
                        <?php
                    }
                    ?>
                    </div>
                    <?php
                } )
                ->render();
            ?>
        </div>
    </div>
<?php };
