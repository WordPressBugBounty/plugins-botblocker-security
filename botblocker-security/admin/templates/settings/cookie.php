<?php

declare(strict_types=1);
if (!defined('ABSPATH')) exit;

use BotBlocker\Component\InfoColumn;
use BotBlocker\Component\SettingsGroup;
use BotBlocker\Component\FieldPair;
use BotBlocker\Component\CustomSelect;
use BotBlocker\Component\TextInput;

return static function (Botblocker_SettingsViewModel $data, bool $isActive): void {
    $samesite_opts = array(
        'Lax'    => __('Lax', 'botblocker-security'),
        'Strict' => __('Strict', 'botblocker-security'),
        'None'   => __('None', 'botblocker-security'),
    );
    $session_opts = array(
        '1' => __('Enabled (Recommended)', 'botblocker-security'),
        '0' => __('Disabled (Legacy)', 'botblocker-security'),
    );
    $timeout_opts = array(
        '2'  => '2s',
        '3'  => '3s',
        '5'  => '5s (' . __( 'default', 'botblocker-security' ) . ')',
        '7'  => '7s',
        '10' => '10s',
        '15' => '15s',
    );
    $vary_opts = array(
        '0' => __('Disabled', 'botblocker-security'),
        '1' => __('Enabled', 'botblocker-security'),
    );
    $cors_opts = array(
        '0' => __('Disabled (Wildcard *)', 'botblocker-security'),
        '1' => __('Enabled (Explicit headers)', 'botblocker-security'),
    );

    $samesite = (string)($data->get('samesite', 'Lax'));
    $cookie_lifetime = (string)($data->get('cookie_lifetime', '86400'));
    $session_token = (string)($data->get('session_token_enabled', '1'));
    $cloud_timeout = (string)($data->get('cloud_api_timeout', '5'));
    $vary_cookie = (string)($data->get('vary_cookie', '0'));
    $cors_strict = (string)($data->get('bbcs_cors_strict_headers', '0'));
?>
    <div role="tabpanel" class="bbcs-tabpanel bbcs-protect-layout" data-tabpanel="cookie"<?php echo $isActive ? '' : ' hidden' ?>>
        <?php
        InfoColumn::make()
            ->withIconImage( BOTBLOCKER_URL . 'public/icons/cookie.svg', __( 'Cookie Settings', 'botblocker-security' ) )
            ->withDescription( __( 'Control BotBlocker verification cookies: name, lifetime, salt, and security attributes.', 'botblocker-security' ) )
            ->withDescription( __( 'SameSite policy controls cross-site cookie behavior to protect against CSRF attacks.', 'botblocker-security' ) )
            ->withDocLink( $data->docs_url . '/what-data-does-botblocker-collect-and-how-is-it-stored-and-deleted/', __( 'What Data Does BotBlocker Collect', 'botblocker-security' ) )
            ->withDocLink( 'https://en.wikipedia.org/wiki/HTTP_cookie', __( 'Cookies', 'botblocker-security' ) )
            ->withDocLink( 'https://en.wikipedia.org/wiki/Salt_(cryptography)', __( 'Salt', 'botblocker-security' ) )
            ->render();
        ?>
        <div>
            <?php
            SettingsGroup::make()
                ->withTitle( __( 'Cookie Settings', 'botblocker-security' ) )
                ->withItems( static function () use ( $data, $samesite_opts, $samesite, $cookie_lifetime, $session_opts, $session_token ): void {
                    FieldPair::make()
                        ->withItems( static function () use ( $data, $samesite_opts, $samesite ): void {
                            TextInput::make()
                                ->withName( 'cookie' )
                                ->withValue( (string) $data->get( 'cookie', 'BotBlocker' ) )
                                ->withLabel( __( 'Cookie Name', 'botblocker-security' ) )
                                ->withTooltip( __( 'Cookie name used by BotBlocker. Changing it resets all existing cookies.', 'botblocker-security' ) )
                                ->render();

                            CustomSelect::make()
                                ->withName( 'samesite' )
                                ->withValue( $samesite )
                                ->withOptions( $samesite_opts )
                                ->withLabel( __( 'Cookie SameSite Policy', 'botblocker-security' ) )
                                ->withTooltip( __( 'Set SameSite attribute (Lax, Strict, None).', 'botblocker-security' ) )
                                ->render();
                        } )
                        ->render();

                    FieldPair::make()
                        ->withItems( static function () use ( $data, $cookie_lifetime, $session_opts, $session_token ): void {
                            CustomSelect::make()
                                ->withName( 'cookie_lifetime' )
                                ->withValue( $cookie_lifetime )
                                ->withOptions( $data->cookie_lifetimes )
                                ->withLabel( __( 'Cookie Lifetime', 'botblocker-security' ) )
                                ->withTooltip( __( 'How long the verification cookie remains valid.', 'botblocker-security' ) )
                                ->render();

                            CustomSelect::make()
                                ->withName( 'session_token_enabled' )
                                ->withValue( $session_token )
                                ->withOptions( $session_opts )
                                ->withLabel( __( 'Session Token Verification', 'botblocker-security' ) )
                                ->withTooltip( __( 'Replaces IP/host/UA-bound cookie hash with a session token. Eliminates Captcha loops for VPN/proxy users. Disable only if experiencing issues.', 'botblocker-security' ) )
                                ->render();
                        } )
                        ->render();

                    TextInput::make()
                        ->withName( 'salt' )
                        ->withValue( (string) $data->get( 'salt', '' ) )
                        ->withLabel( __( 'Salt', 'botblocker-security' ) )
                        ->withTooltip( __( 'Random string that makes cookie values unpredictable.', 'botblocker-security' ) )
                        ->render();
                } )
                ->render();

            SettingsGroup::make()
                ->withTitle( __( 'Cache Compatibility', 'botblocker-security' ) )
                ->withItems( static function () use ( $data, $timeout_opts, $cloud_timeout, $vary_opts, $vary_cookie, $cors_opts, $cors_strict ): void {
                    FieldPair::make()
                        ->withItems( static function () use ( $timeout_opts, $cloud_timeout, $vary_opts, $vary_cookie ): void {
                            CustomSelect::make()
                                ->withName( 'cloud_api_timeout' )
                                ->withValue( $cloud_timeout )
                                ->withOptions( $timeout_opts )
                                ->withLabel( __( 'Cloud API Timeout', 'botblocker-security' ) )
                                ->withTooltip( __( 'Timeout in seconds for cloud API requests. Increase if your server has slow outbound connections.', 'botblocker-security' ) )
                                ->render();

                            CustomSelect::make()
                                ->withName( 'vary_cookie' )
                                ->withValue( $vary_cookie )
                                ->withOptions( $vary_opts )
                                ->withLabel( __( 'Send Vary: Cookie Header', 'botblocker-security' ) )
                                ->withTooltip( __( 'Add Vary: Cookie header for CDN compatibility. May reduce cache hit ratio.', 'botblocker-security' ) )
                                ->render();
                        } )
                        ->render();

                    CustomSelect::make()
                        ->withName( 'bbcs_cors_strict_headers' )
                        ->withValue( $cors_strict )
                        ->withOptions( $cors_opts )
                        ->withLabel( __( 'Strict CORS Headers', 'botblocker-security' ) )
                        ->withTooltip( __( 'Use explicit CORS headers (Content-Type, X-Requested-With) instead of wildcard (*). Fixes Fetch-spec violation when Access-Control-Allow-Credentials is active. Enable if CAPTCHA verification breaks in browsers.', 'botblocker-security' ) )
                        ->render();
                } )
                ->render();
            ?>
        </div>
    </div>
<?php };
