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
    $tv = $data->is_checked('telegram_notifications', 1);
    $freq = (string)($data->get('regular_notifications_frequency', ''));
    $freq_opts = [
        "disabled" => __("Disabled", "botblocker-security"),
        "daily" => __("Every day", "botblocker-security"),
        "twice_week" => __("Twice a week", "botblocker-security"),
        "monthly" => __("Once a month", "botblocker-security")
    ];
?>
    <div role="tabpanel" class="bbcs-tabpanel bbcs-protect-layout" data-tabpanel="notifications"<?php echo $isActive ? '' : ' hidden' ?>>
        <?php
        InfoColumn::make()
            ->withIconImage( BOTBLOCKER_URL . 'public/icons/notification.svg', __( 'Notifications', 'botblocker-security' ) )
            ->withDescription( __( 'Configure how and when you receive alerts about bot activity.', 'botblocker-security' ) )
            ->withDescription( __( 'Choose notification channels, set up load alerts, and configure report frequency.', 'botblocker-security' ) )
            ->withDocLink( 'https://pusher.com/', __( 'Pusher', 'botblocker-security' ) )
            ->withDocLink( 'https://t.me/BotFather', __( 'Telegram BotFather', 'botblocker-security' ) )
            ->render();
        ?>
        <div>
            <?php
            SettingsGroup::make()
                ->withTitle( __( 'Notification Types', 'botblocker-security' ) )
                ->withItems( static function () use ( $data ): void {
                    FieldPair::make()
                        ->withItems( static function () use ( $data ): void {
                            ToggleOption::make()->withName( 'email_notifications' )->withChecked( $data->is_checked( 'email_notifications' ) )->withLabel( __( 'Email', 'botblocker-security' ) )->withTooltip( __( 'Receive security alerts via email.', 'botblocker-security' ) )->render();
                            ToggleOption::make()->withName( 'telegram_notifications' )->withChecked( $data->is_checked( 'telegram_notifications' ) )->withLabel( __( 'Telegram', 'botblocker-security' ) )->withTooltip( __( 'Receive security alerts via Telegram.', 'botblocker-security' ) )->render();
                        } )
                        ->render();
                } )
                ->render();

            SettingsGroup::make()
                ->withTitle( __( 'Telegram Configuration', 'botblocker-security' ) )
                ->withItems( static function () use ( $data, $tv ): void {
                    ?>
                    <div class="bbcs-telegram-fields" id="bbcs_telegram_fields" style="padding-left:24px;">
                        <?php
                        FieldPair::make()
                            ->withItems( static function () use ( $data, $tv ): void {
                                TextInput::make()
                                    ->withName( 'telegram_bot_token' )
                                    ->withValue( (string) $data->get( 'telegram_bot_token', '' ) )
                                    ->withPlaceholder( __( '123456:ABC-DEF1234ghIkl-zyx57W2v1u123ew11', 'botblocker-security' ) )
                                    ->withLabel( __( 'Bot Token', 'botblocker-security' ) )
                                    ->withTooltip( __( 'Your Telegram bot token from @BotFather.', 'botblocker-security' ) )
                                    ->withDisabled( ! $tv )
                                    ->render();

                                TextInput::make()
                                    ->withName( 'telegram_chat_id' )
                                    ->withValue( (string) $data->get( 'telegram_chat_id', '' ) )
                                    ->withPlaceholder( __( '-1001234567890', 'botblocker-security' ) )
                                    ->withLabel( __( 'Chat ID', 'botblocker-security' ) )
                                    ->withTooltip( __( 'Your Telegram chat ID (user or group).', 'botblocker-security' ) )
                                    ->withDisabled( ! $tv )
                                    ->render();
                            } )
                            ->render();
                        ?>
                    </div>
                    <?php
                } )
                ->render();

            SettingsGroup::make()
                ->withTitle( __( 'Notification Settings', 'botblocker-security' ) )
                ->withItems( static function () use ( $data, $freq_opts, $freq ): void {
                    FieldPair::make()
                        ->withItems( static function () use ( $data, $freq_opts, $freq ): void {
                            ToggleOption::make()->withName( 'critical_load_notifications' )->withChecked( $data->is_checked( 'critical_load_notifications' ) )->withLabel( __( 'Notify on Critical Server Load', 'botblocker-security' ) )->withTooltip( __( 'Alert when server load is critical or unusual bot activity is detected.', 'botblocker-security' ) )->render();

                            CustomSelect::make()
                                ->withName( 'regular_notifications_frequency' )
                                ->withValue( $freq )
                                ->withOptions( $freq_opts )
                                ->withLabel( __( 'Regular Report Frequency', 'botblocker-security' ) )
                                ->withTooltip( __( 'How often to receive status reports.', 'botblocker-security' ) )
                                ->render();
                        } )
                        ->render();
                } )
                ->render();

            FieldPair::make()
                ->withItems( static function () use ( $data ): void {
                    SettingsGroup::make()
                        ->withTitle( __( 'Pusher', 'botblocker-security' ) )
                        ->withItems( static function () use ( $data ): void {
                            ?>
                            <div class="bbcs-option bbcs-hoverbg">
                                <button class="bbcs-toggle" role="switch" aria-checked="false" type="button" disabled>
                                    <span class="bbcs-toggle-knob"></span>
                                </button>
                                <input type="hidden" name="pusher_notifications" value="0">
                                <span class="bbcs-option-label"><?php esc_html_e( 'Pusher', 'botblocker-security' ); ?></span>
                                <span class="bbcs-help">
                                    <span class="bbcs-help-q">?</span>
                                    <span class="bbcs-help-tip"><?php esc_attr_e( 'Receive real-time security alerts via Pusher.', 'botblocker-security' ); ?></span>
                                </span>
                                <small class="text-muted bbcs-ps-5"><?php esc_html_e( 'Coming soon', 'botblocker-security' ); ?> (<a href="<?php echo esc_url( $data->addons_url ); ?>"><?php esc_html_e( 'Add-ons', 'botblocker-security' ); ?></a>)</small>
                            </div>
                            <?php
                        } )
                        ->render();
                } )
                ->render();
            ?>
        </div>
    </div>
<?php };
