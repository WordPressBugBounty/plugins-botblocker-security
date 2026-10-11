<?php

declare(strict_types=1);
if (!defined('ABSPATH')) exit;

use BotBlocker\Component\InfoColumn;
use BotBlocker\Component\SettingsGroup;
use BotBlocker\Component\ToggleOption;
use BotBlocker\Component\FieldPair;

return static function (Botblocker_SettingsViewModel $data, bool $isActive): void { ?>
    <div role="tabpanel" class="bbcs-tabpanel bbcs-protect-layout" data-tabpanel="log"<?php echo $isActive ? '' : ' hidden' ?>>
        <?php
        InfoColumn::make()
        	->withDocPage( BotBlockerDocs::page( 'logging-audit' ) )
            ->withIconImage( BOTBLOCKER_URL . 'public/icons/logging-settings.svg', __( 'Logging Settings', 'botblocker-security' ) )
            ->withDescription( __( 'Choose which security events and visitor activities to log.', 'botblocker-security' ) )
            ->withDescription( __( 'Log verification requests, allowed visitors, blocked attempts, and admin actions.', 'botblocker-security' ) )
            ->render();
        ?>
        <?php
        FieldPair::make()
            ->withItems( static function () use ( $data ): void {
                ?>
            <div>
                <?php
                SettingsGroup::make()
                    ->withTitle( __( 'Visitor Logging Settings', 'botblocker-security' ) )
                    ->withItems( static function () use ( $data ): void {
                        ToggleOption::make()->withName( 'botblocker_log_tests' )->withChecked( $data->is_checked( 'botblocker_log_tests' ) )->withLabel( __( 'Log Manual Verification Requests', 'botblocker-security' ) )->withTooltip( __( 'Record visitors sent to Captcha challenges. Useful for analysing bot detection effectiveness.', 'botblocker-security' ) )->withDocLink( BotBlockerDocs::url( 'botblocker_log_tests' ) )->render();
                        ToggleOption::make()->withName( 'botblocker_log_local' )->withChecked( $data->is_checked( 'botblocker_log_local' ) )->withLabel( __( 'Log Verified Local Visitors', 'botblocker-security' ) )->withTooltip( __( 'Track returning verified visitors. Helps identify repeat traffic patterns and false positives.', 'botblocker-security' ) )->withDocLink( BotBlockerDocs::url( 'botblocker_log_local' ) )->render();
                        ToggleOption::make()->withName( 'botblocker_log_allow' )->withChecked( $data->is_checked( 'botblocker_log_allow' ) )->withLabel( __( 'Log Allowed Visitors', 'botblocker-security' ) )->withTooltip( __( 'Record visitors who pass all security checks. Useful for traffic analysis and audit trails.', 'botblocker-security' ) )->withDocLink( BotBlockerDocs::url( 'botblocker_log_allow' ) )->render();
                        ToggleOption::make()->withName( 'botblocker_log_fake' )->withChecked( $data->is_checked( 'botblocker_log_fake' ) )->withLabel( __( 'Log Suspected Fake Bots', 'botblocker-security' ) )->withTooltip( __( 'Record visitors detected as likely bots via fingerprinting inconsistencies.', 'botblocker-security' ) )->withDocLink( BotBlockerDocs::url( 'botblocker_log_fake' ) )->render();
                        ToggleOption::make()->withName( 'botblocker_log_goodip' )->withChecked( $data->is_checked( 'botblocker_log_goodip' ) )->withLabel( __( 'Log Known Good IPs', 'botblocker-security' ) )->withTooltip( __( 'Record traffic from whitelisted IPs to verify rule effectiveness.', 'botblocker-security' ) )->withDocLink( BotBlockerDocs::url( 'botblocker_log_goodip' ) )->render();
                        ToggleOption::make()->withName( 'botblocker_log_block' )->withChecked( $data->is_checked( 'botblocker_log_block' ) )->withLabel( __( 'Log Blocked Visitors', 'botblocker-security' ) )->withTooltip( __( 'Record blocked visitor data for attack pattern analysis and threat intelligence.', 'botblocker-security' ) )->withDocLink( BotBlockerDocs::url( 'botblocker_log_block' ) )->render();
                    } )
                    ->render();
                ?>
            </div>
            <div>
                <?php
                SettingsGroup::make()
                    ->withTitle( __( 'Admin and WordPress Logging', 'botblocker-security' ) )
                    ->withItems( static function () use ( $data ): void {
                        ToggleOption::make()->withName( 'botblocker_log_admin' )->withChecked( $data->is_checked( 'botblocker_log_admin' ) )->withLabel( __( 'Log Actions in WordPress Admin Panel', 'botblocker-security' ) )->withTooltip( __( 'Track admin panel activity for security auditing and multi-user accountability.', 'botblocker-security' ) )->withDocLink( BotBlockerDocs::url( 'botblocker_log_admin' ) )->render();
                        ToggleOption::make()->withName( 'botblocker_log_bbcs' )->withChecked( $data->is_checked( 'botblocker_log_bbcs' ) )->withLabel( __( 'Log BotBlocker Page Visits', 'botblocker-security' ) )->withTooltip( __( 'Record visits to BotBlocker admin screens in the log.', 'botblocker-security' ) )->withDocLink( BotBlockerDocs::url( 'botblocker_log_bbcs' ) )->render();
                        ToggleOption::make()->withName( 'botblocker_log_wp' )->withChecked( $data->is_checked( 'botblocker_log_wp' ) )->withLabel( __( 'Log WordPress Actions', 'botblocker-security' ) )->withTooltip( __( 'Record visitor hits to WordPress system URLs such as logins, password resets, and blocked access attempts.', 'botblocker-security' ) )->withDocLink( BotBlockerDocs::url( 'botblocker_log_wp' ) )->render();
                    } )
                    ->render();

                SettingsGroup::make()
                    ->withTitle( __( 'Error Logging', 'botblocker-security' ) )
                    ->withItems( static function () use ( $data ): void {
                        ToggleOption::make()->withName( 'botblocker_log_error' )->withChecked( $data->is_checked( 'botblocker_log_error' ) )->withLabel( __( 'Log BotBlocker Errors', 'botblocker-security' ) )->withTooltip( __( 'Record internal errors for debugging and support troubleshooting.', 'botblocker-security' ) )->withDocLink( BotBlockerDocs::url( 'botblocker_log_error' ) )->render();
                        ToggleOption::make()->withName( 'botblocker_log_cli' )->withChecked( $data->is_checked( 'botblocker_log_cli' ) )->withLabel( __( 'Log CLI requests', 'botblocker-security' ) )->withTooltip( __( 'Track WP-CLI and cron execution. Helps detect automated exploitation attempts.', 'botblocker-security' ) )->withDocLink( BotBlockerDocs::url( 'botblocker_log_cli' ) )->render();
                        ToggleOption::make()->withName( 'botblocker_log_disabled' )->withChecked( $data->is_checked( 'botblocker_log_disabled' ) )->withLabel( __( 'Log Visits When BotBlocker Protection is Disabled', 'botblocker-security' ) )->withTooltip( __( 'Record traffic during maintenance windows or when protection is intentionally disabled.', 'botblocker-security' ) )->withDocLink( BotBlockerDocs::url( 'botblocker_log_disabled' ) )->render();
                    } )
                    ->render();
                ?>
            </div>
                <?php
            } )
            ->render();
        ?>
    </div>
<?php };
