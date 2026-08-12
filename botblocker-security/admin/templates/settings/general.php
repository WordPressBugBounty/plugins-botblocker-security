<?php

declare(strict_types=1);
if (!defined('ABSPATH')) exit;

use BotBlocker\Component\InfoColumn;
use BotBlocker\Component\SettingsGroup;
use BotBlocker\Component\FieldPair;
use BotBlocker\Component\ToggleOption;
use BotBlocker\Component\CustomSelect;
use BotBlocker\Component\TextInput;

return static function (Botblocker_SettingsViewModel $data, bool $isActive): void {
    $sm = (string) $data->get('secure_mode', '2');
    $hits = $data->get('hits_per_user', '500');
    $ptr_time = (string)($data->get('ptrcache_time', DAY_IN_SECONDS));
    $ptr_subnet = (string)($data->get('ptrcache_subnet', '24-64'));
    $ptr_ttl = (string)($data->get('ptrcache_rule_ttl', '90'));
?>
    <div role="tabpanel" class="bbcs-tabpanel bbcs-protect-layout" data-tabpanel="general"<?php echo $isActive ? '' : ' hidden' ?>>
        <?php
        InfoColumn::make()
            ->withIconImage( BOTBLOCKER_URL . 'public/icons/general.svg', __( 'General', 'botblocker-security' ) )
            ->withDescription( __( 'Control how BotBlocker verifies visitors, applies security rules, and manages hit limits.', 'botblocker-security' ) )
            ->withDescription( __( 'PTR cache improves DNS lookup speed. Auto-save admin IPs prevents accidental lockout.', 'botblocker-security' ) )
            ->withDocLink( 'https://en.wikipedia.org/wiki/HTTP_cookie', __( 'Cookies', 'botblocker-security' ) )
            ->withDocLink( $data->docs_url . '/ptr-record-checks-detecting-fake-bots-with-reverse-dns-in-botblocker/', __( 'PTR', 'botblocker-security' ) )
            ->render();
        ?>
        <div>
            <?php
            SettingsGroup::make()
                ->withTitle( __( 'General', 'botblocker-security' ) )
                ->withItems( static function () use ( $data, $sm, $hits ): void {
                    FieldPair::make()
                        ->withItems( static function () use ( $data, $sm, $hits ): void {
                            CustomSelect::make()
                                ->withName( 'secure_mode' )
                                ->withValue( $sm )
                                ->withOptions( array( '2' => __( 'Full Mode (Check all requests)', 'botblocker-security' ), '1' => __( 'Frontend Mode (Check frontend only)', 'botblocker-security' ) ) )
                                ->withLabel( __( 'Security Check Mode', 'botblocker-security' ) )
                                ->withTooltip( __( 'Full Mode: Inspect all requests (frontend, admin, API, AJAX, cron). Frontend Mode: Inspect public-facing requests only.', 'botblocker-security' ) )
                                ->render();

                            TextInput::make()
                                ->withType( 'number' )
                                ->withName( 'hits_per_user' )
                                ->withValue( (string) $hits )
                                ->withLabel( __( 'Hits Per User', 'botblocker-security' ) )
                                ->withTooltip( __( 'Requests allowed per verified visitor before re-verification.', 'botblocker-security' ) )
                                ->withInputClass( 'bbcs-input--num' )
                                ->withMin( '1' )
                                ->withStep( '1' )
                                ->withEditable()
                                ->withSuffix( __( 'hits', 'botblocker-security' ) )
                                ->render();
                        } )
                        ->render();
                } )
                ->render();

            SettingsGroup::make()
                ->withTitle( __( 'Reverse DNS (PTR)', 'botblocker-security' ) )
                ->withItems( static function () use ( $data, $ptr_time, $ptr_subnet, $ptr_ttl ): void {
                    $t = $data->sidebar->toggles;

                    FieldPair::make()
                        ->withItems( static function () use ( $data, $t, $ptr_time ): void {
                            ToggleOption::make()
                                ->withName( 'ptr_cache_in_db' )
                                ->withChecked( (bool) $t->ptr_cache_checked )
                                ->withLabel( __( 'PTR cache', 'botblocker-security' ) )
                                ->withTooltip( __( 'Caches PTR lookups to speed up repeat visitor checks. TTL is set by the PTR Cache Lifetime option.', 'botblocker-security' ) )
                                ->render();

                            CustomSelect::make()
                                ->withName( 'ptrcache_time' )
                                ->withValue( $ptr_time )
                                ->withOptions( $data->ptr_lifetimes )
                                ->withLabel( __( 'PTR Cache Lifetime', 'botblocker-security' ) )
                                ->withTooltip( __( 'How long to cache DNS lookup results.', 'botblocker-security' ) )
                                ->render();
                        } )
                        ->render();

                    FieldPair::make()
                        ->withItems( static function () use ( $data, $ptr_subnet, $ptr_ttl ): void {
                            CustomSelect::make()
                                ->withName( 'ptrcache_subnet' )
                                ->withValue( $ptr_subnet )
                                ->withOptions( $data->subnet_mask_options )
                                ->withLabel( __( 'PTR Rule Subnet Mask', 'botblocker-security' ) )
                                ->withTooltip( __( 'Subnet size for verified bot allow-rules (IPv4/IPv6). Smaller = more secure, larger = fewer DNS lookups.', 'botblocker-security' ) )
                                ->render();

                            CustomSelect::make()
                                ->withName( 'ptrcache_rule_ttl' )
                                ->withValue( $ptr_ttl )
                                ->withOptions( $data->ptrcache_rule_ttl_options )
                                ->withLabel( __( 'PTR Rule Lifetime', 'botblocker-security' ) )
                                ->withTooltip( __( 'How long to keep allow-rules for verified bots before they expire.', 'botblocker-security' ) )
                                ->render();
                        } )
                        ->render();
                } )
                ->render();

            SettingsGroup::make()
                ->withTitle( __( 'Administrator Settings', 'botblocker-security' ) )
                ->withItems( static function () use ( $data ): void {
                    FieldPair::make()
                        ->withItems( static function () use ( $data ): void {
                            ToggleOption::make()->withName( 'autosave_admin_ip' )->withChecked( $data->is_checked( 'autosave_admin_ip' ) )->withLabel( __( 'Auto-save administrator IPs', 'botblocker-security' ) )->withTooltip( __( 'Automatically save admin IPs to prevent lockout when changing settings.', 'botblocker-security' ) )->render();
                            ToggleOption::make()->withName( 'skip_logged_in_users' )->withChecked( $data->is_checked( 'skip_logged_in_users' ) )->withLabel( __( 'Skip checks for all logged-in users', 'botblocker-security' ) )->withTooltip( __( 'When enabled, all authenticated WordPress users (including subscribers, authors, contributors) bypass BotBlocker checks. By default, only administrators, editors and moderators are bypassed.', 'botblocker-security' ) )->render();
                        } )
                        ->render();
                } )
                ->render();
            ?>
        </div>
    </div>
<?php };
