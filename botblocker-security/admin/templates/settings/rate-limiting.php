<?php

declare(strict_types=1);
if (!defined('ABSPATH')) exit;

use BotBlocker\Component\InfoColumn;
use BotBlocker\Component\SettingsGroup;
use BotBlocker\Component\ToggleOption;
use BotBlocker\Component\CustomSelect;
use BotBlocker\Component\TextInput;
use BotBlocker\Component\FieldPair;

return static function (Botblocker_SettingsViewModel $data, bool $isActive): void {
    $captcha_rpm = (string) $data->get('bbcs_rate_captcha_rpm', '30');
    $block_rpm = (string) $data->get('bbcs_rate_block_rpm', '50');
    $block_dur = (string) $data->get('bbcs_rate_block_duration', '600');
    $window = (string) $data->get('bbcs_rate_window_minutes', '5');
    $subnet_mult = (string) $data->get('bbcs_rate_subnet_multiplier', '3');
    $floor_val = (string) $data->get('bbcs_rate_floor_percent', '0.1');
    $floor_percent = (float)$floor_val <= 1.0 ? (string)((float)$floor_val * 100) : (string)$floor_val;
    $subnet_mask = (string)($data->get('bbcs_rate_subnet_mask', '24-64'));
?>
    <div role="tabpanel" class="bbcs-tabpanel bbcs-protect-layout" data-tabpanel="rate-limiting"<?php echo $isActive ? '' : ' hidden' ?>>
        <div>
            <?php
            InfoColumn::make()
                ->withIconImage( BOTBLOCKER_URL . 'public/icons/rate-limiting.svg', __( 'Rate Limiting', 'botblocker-security' ) )
                ->withDescription( __( 'Control request velocity per IP with configurable thresholds, sliding windows, and subnet aggregation.', 'botblocker-security' ) )
                ->withDescription( __( 'Rate limiting protects against brute force, DDoS, and distributed proxy attacks by dynamically adjusting thresholds based on subnet pressure.', 'botblocker-security' ) )
                ->withDocLink( 'https://en.wikipedia.org/wiki/Rate_limiting', __( 'Rate limiting', 'botblocker-security' ) )
                ->withDocLink( 'https://en.wikipedia.org/wiki/Subnet', __( 'Subnet', 'botblocker-security' ) )
                ->render();

            $bbcs_has_pro = class_exists( 'BotBlockerPro' ) && BotBlockerPro::isActive();
            if ( ! $bbcs_has_pro ) : ?>
                <div class="bbcs-infocol-note bbcs-infocol-note--warn">
                    <strong><?php esc_html_e( 'PRO Feature:', 'botblocker-security' ); ?></strong>
                    <?php esc_html_e( 'Upgrade to Pro for the Behavioral Engine. It features advanced multi-signal scoring and decay-based reputation, preventing bots from spamming your site by tightening thresholds for repeat offenders.', 'botblocker-security' ); ?>
                    <br>
                    <a href="<?php echo esc_url( $data->addons_url . '&focus=' . apply_filters( 'bbcs_behavior_engine_tab_id', 'bbcs-behavior' ) ); ?>" class="bbcs-link bbcs-fs-xs" style="display:inline-block;margin-top:6px;"><?php esc_html_e( 'Upgrade to PRO', 'botblocker-security' ); ?></a>
                </div>
            <?php else :
                $bbcs_behavior_active = apply_filters( 'bbcs_has_behavioral_analysis_engine', false );
                if ( $bbcs_behavior_active ) : ?>
                    <div class="bbcs-infocol-note bbcs-infocol-note--success">
                        <strong><?php esc_html_e( 'Pro Feature Active:', 'botblocker-security' ); ?></strong>
                        <?php esc_html_e( 'The Behavioral Engine is installed and active. You can customize the multi-signal thresholds, session limits, and IP reputation decay.', 'botblocker-security' ); ?>
                        <br>
                        <a href="<?php echo esc_url( $data->addons_url . '#' . apply_filters( 'bbcs_behavior_engine_tab_id', 'bbcs-behavior' ) ); ?>" class="bbcs-link bbcs-fs-xs" style="display:inline-block;margin-top:6px;"><?php esc_html_e( 'Configure Behavioral Engine', 'botblocker-security' ); ?></a>
                    </div>
                <?php else : ?>
                    <div class="bbcs-infocol-note bbcs-infocol-note--warn">
                        <strong><?php esc_html_e( 'Pro Feature Available:', 'botblocker-security' ); ?></strong>
                        <?php esc_html_e( 'Activate the Behavioral Engine addon to get advanced multi-signal scoring and IP/subnet reputation protection.', 'botblocker-security' ); ?>
                        <br>
                        <a href="<?php echo esc_url( BotBlockerMultisite::getAdminPageUrl( 'bbcs_addons' ) ); ?>" class="bbcs-link bbcs-fs-xs" style="display:inline-block;margin-top:6px;"><?php esc_html_e( 'Go to Add-ons', 'botblocker-security' ); ?></a>
                    </div>
                <?php endif;
            endif; ?>
        </div>
        <div>
            <?php
            SettingsGroup::make()
                ->withTitle( __( 'Rate Limiting', 'botblocker-security' ) )
                ->withItems( static function () use ( $data, $captcha_rpm, $block_rpm, $block_dur, $window ): void {
                    ToggleOption::make()->withName( 'bbcs_rate_check_enabled' )->withChecked( $data->is_checked( 'bbcs_rate_check_enabled' ) )->withLabel( __( 'Enable Rate Limiting', 'botblocker-security' ) )->withTooltip( __( 'Track request velocity per IP and issue Captcha or block when thresholds are exceeded.', 'botblocker-security' ) )->render();

                    FieldPair::make()
                        ->withItems( static function () use ( $data, $captcha_rpm, $block_rpm ): void {
                            TextInput::make()
                                ->withName( 'bbcs_rate_captcha_rpm' )
                                ->withValue( $captcha_rpm )
                                ->withType( 'number' )
                                ->withLabel( __( 'Captcha Threshold', 'botblocker-security' ) )
                                ->withTooltip( __( 'Average requests per minute over the sliding window that trigger a Captcha challenge. Total hits in window / window minutes. Default: 30.', 'botblocker-security' ) )
                                ->withSuffix( __( 'rpm', 'botblocker-security' ) )
                                ->withEditable()
                                ->render();
                            TextInput::make()
                                ->withName( 'bbcs_rate_block_rpm' )
                                ->withValue( $block_rpm )
                                ->withType( 'number' )
                                ->withLabel( __( 'Block Threshold', 'botblocker-security' ) )
                                ->withTooltip( __( 'Average requests per minute over the sliding window that trigger an immediate block. Total hits in window / window minutes. Default: 50.', 'botblocker-security' ) )
                                ->withSuffix( __( 'rpm', 'botblocker-security' ) )
                                ->withEditable()
                                ->render();
                        } )
                        ->render();

                    FieldPair::make()
                        ->withItems( static function () use ( $data, $block_dur, $window ): void {
                            TextInput::make()
                                ->withName( 'bbcs_rate_block_duration' )
                                ->withValue( $block_dur )
                                ->withType( 'number' )
                                ->withLabel( __( 'Block Time', 'botblocker-security' ) )
                                ->withTooltip( __( 'How long the IP stays blocked after exceeding the rate limit. Default: 600 (10 minutes).', 'botblocker-security' ) )
                                ->withSuffix( __( 'sec', 'botblocker-security' ) )
                                ->withEditable()
                                ->render();
                            TextInput::make()
                                ->withName( 'bbcs_rate_window_minutes' )
                                ->withValue( $window )
                                ->withType( 'number' )
                                ->withLabel( __( 'Window', 'botblocker-security' ) )
                                ->withTooltip( __( 'Sliding window size in minutes. RPM = total hits in window / window minutes. Default: 5.', 'botblocker-security' ) )
                                ->withSuffix( __( 'min', 'botblocker-security' ) )
                                ->withEditable()
                                ->render();
                        } )
                        ->render();
                } )
                ->render();

            SettingsGroup::make()
                ->withTitle( __( 'Subnet Aggregation', 'botblocker-security' ) )
                ->withItems( static function () use ( $data, $subnet_mult, $floor_percent, $floor_val, $subnet_mask ): void {
                    ToggleOption::make()->withName( 'bbcs_rate_subnet_enabled' )->withChecked( $data->is_checked( 'bbcs_rate_subnet_enabled' ) )->withLabel( __( 'Subnet Aggregation', 'botblocker-security' ) )->withTooltip( __( 'Aggregate RPM across the subnet. High subnet pressure dynamically lowers per-IP thresholds to catch distributed proxy attacks.', 'botblocker-security' ) )->render();

                    FieldPair::make()
                        ->withItems( static function () use ( $data, $subnet_mult, $floor_val, $floor_percent ): void {
                            TextInput::make()
                                ->withName( 'bbcs_rate_subnet_multiplier' )
                                ->withValue( $subnet_mult )
                                ->withType( 'number' )
                                ->withLabel( __( 'Subnet Multiplier', 'botblocker-security' ) )
                                ->withTooltip( __( 'Multiplier applied to block RPM for subnet threshold. Higher = more tolerant. Default: 3.', 'botblocker-security' ) )
                                ->withEditable()
                                ->render();
                            TextInput::make()
                                ->withName( 'bbcs_rate_floor_percent' )
                                ->withValue( $floor_percent )
                                ->withType( 'number' )
                                ->withStep( '1' )
                                ->withLabel( __( 'Floor %', 'botblocker-security' ) )
                                ->withTooltip( __( 'Minimum threshold as a percentage of block RPM, preventing thresholds from dropping to zero under high subnet pressure. Default: 10%.', 'botblocker-security' ) )
                                ->withEditable()
                                ->render();
                        } )
                        ->render();

                    CustomSelect::make()
                        ->withName( 'bbcs_rate_subnet_mask' )
                        ->withValue( $subnet_mask )
                        ->withOptions( $data->rate_subnet_mask_options )
                        ->withLabel( __( 'Subnet Mask', 'botblocker-security' ) )
                        ->withTooltip( __( 'CIDR mask pair for subnet aggregation (v4_v6). Tighter masks target attackers more precisely. Default: 24-64.', 'botblocker-security' ) )
                        ->render();
                } )
                ->render();
            ?>
        </div>
    </div>
<?php };
