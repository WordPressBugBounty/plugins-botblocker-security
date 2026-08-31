<?php

declare(strict_types=1);
if (!defined('ABSPATH')) exit;

use BotBlocker\Component\InfoColumn;
use BotBlocker\Component\SettingsGroup;
use BotBlocker\Component\ToggleOption;
use BotBlocker\Component\TextInput;
use BotBlocker\Component\FieldPair;

$bbcs_tls_md = '';
$bbcs_tls_md_path = BOTBLOCKER_DIR . 'docs/TLS-FINGERPRINTING.md';
if ( file_exists( $bbcs_tls_md_path ) ) {
    $bbcs_tls_md = (string) file_get_contents( $bbcs_tls_md_path ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
}

return static function (Botblocker_SettingsViewModel $data, bool $isActive) use ( $bbcs_tls_md ): void {
    $ja3 = $data->current_ja3 !== '' ? $data->current_ja3 : __('(not detected)', 'botblocker-security');
    $ja4 = $data->current_ja4 !== '' ? $data->current_ja4 : __('(not detected)', 'botblocker-security');
    ?>
    <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
    <div role="tabpanel" class="bbcs-tabpanel bbcs-protect-layout" data-tabpanel="tls_fingerprint"<?php echo $isActive ? '' : ' hidden' ?>>
        <?php
        InfoColumn::make()
            ->withIconImage( BOTBLOCKER_URL . 'public/icons/security.svg', __( 'TLS Fingerprint Settings', 'botblocker-security' ) )
            ->withDescription( __( 'TLS fingerprinting detects bots by analyzing the TLS handshake signature (JA3/JA4). Real browsers have distinct TLS fingerprints vs headless/automation tools.', 'botblocker-security' ) )
            ->withDescription( __( 'Requires a web server module (nginx, HAProxy, LiteSpeed) or Cloudflare (Business+) to pass the fingerprint via HTTP header.', 'botblocker-security' ) )
            ->withDocLink( 'https://github.com/FoxIO-LLC/ja4', __( 'JA4 Spec', 'botblocker-security' ) )
            ->withDocLink( 'https://ja3er.com/', __( 'JA3 DB', 'botblocker-security' ) )
            ->render();
        ?>
        <?php
        FieldPair::make()
            ->withItems( static function () use ( $data, $ja3, $ja4 ): void {
                ?>
            <div>
                <?php
                SettingsGroup::make()
                    ->withTitle( __( 'TLS Fingerprint Settings', 'botblocker-security' ) )
                    ->withItems( static function () use ( $data ): void {
                        ToggleOption::make()->withName( 'tls_fingerprint_check' )->withChecked( $data->is_checked( 'tls_fingerprint_check' ) )->withLabel( __( 'Enable TLS Fingerprint Check', 'botblocker-security' ) )->withTooltip( __( 'Enable JA3/JA4 TLS fingerprint analysis for bot detection. Requires fingerprint headers from web server.', 'botblocker-security' ) )->withAnchor( 'tls_fingerprint_check' )->render();
                    } )
                    ->render();

                SettingsGroup::make()
                    ->withTitle( __( 'Header Configuration', 'botblocker-security' ) )
                    ->withItems( static function () use ( $data ): void {
                        TextInput::make()
                            ->withName( 'tls_fingerprint_header_ja3' )
                            ->withValue( (string) $data->get( 'tls_fingerprint_header_ja3', 'X-TLS-JA3' ) )
                            ->withLabel( __( 'JA3 Header Name', 'botblocker-security' ) )
                            ->withTooltip( __( 'HTTP header name for JA3 fingerprint. Default: X-TLS-JA3. Cloudflare sends Cf-JA3-Fingerprint automatically.', 'botblocker-security' ) )
                            ->withAnchor( 'tls_fingerprint_header_ja3' )
                            ->render();

                        TextInput::make()
                            ->withName( 'tls_fingerprint_header_ja4' )
                            ->withValue( (string) $data->get( 'tls_fingerprint_header_ja4', 'X-TLS-JA4' ) )
                            ->withLabel( __( 'JA4 Header Name', 'botblocker-security' ) )
                            ->withTooltip( __( 'HTTP header name for JA4 fingerprint. Default: X-TLS-JA4.', 'botblocker-security' ) )
                            ->withAnchor( 'tls_fingerprint_header_ja4' )
                            ->render();
                    } )
                    ->render();

                SettingsGroup::make()
                    ->withTitle( __( 'Fingerprint Database', 'botblocker-security' ) )
                    ->withItems( static function () use ( $data ): void {
                        $tls_sync_enabled = $data->is_checked( 'tls_fingerprint_check' );
                        ?>
                        <div class="bbcs-button-container">
                            <div class="bbcs_settings_button">
                                <button type="button" id="bbcs_tls_import" class="mb-1 bbcs-btn bbcs-btn--xs btn-default"><i class="fa-solid fa-cloud-arrow-down"></i> <?php esc_html_e( 'Import JSON', 'botblocker-security' ); ?></button>
                                <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
                                <?php echo \BotBlocker\Component\Base::tooltip( __( 'Import a JSON file containing known JA3/JA4 TLS fingerprints. The file must be an array of objects with fields: fingerprint, category, ua_family, description.', 'botblocker-security' ) ); ?>
                            </div>
                            <div class="bbcs_settings_button">
                                <button type="button" id="bbcs_tls_clear" class="mb-1 bbcs-btn bbcs-btn--xs btn-default"><i class="fa-solid fa-trash-can"></i> <?php esc_html_e( 'Clear All', 'botblocker-security' ); ?></button>
                                <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
                                <?php echo \BotBlocker\Component\Base::tooltip( __( 'Clear all known JA3/JA4 TLS fingerprints.', 'botblocker-security' ) ); ?>
                            </div>
                            <div class="bbcs_settings_button">
                                <button type="button" id="bbcs_tls_sync" class="mb-1 bbcs-btn bbcs-btn--xs btn-default<?php echo $tls_sync_enabled ? '' : ' is-disabled'; ?>"<?php echo $tls_sync_enabled ? '' : ' disabled title="' . esc_attr__( 'Enable TLS Fingerprint Check and save settings before syncing.', 'botblocker-security' ) . '"'; ?>><i class="fa-solid fa-sync"></i> <?php esc_html_e( 'Sync Now', 'botblocker-security' ); ?></button>
                                <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
                                <?php echo \BotBlocker\Component\Base::tooltip( __( 'Sync known JA3/JA4 TLS fingerprints from the BotBlocker server.', 'botblocker-security' ) ); ?>
                            </div>
                        </div>
                        <?php
                    } )
                    ->render();
                ?>
            </div>
            <div>
                <?php
                SettingsGroup::make()
                    ->withTitle( __( 'Trusted Proxy', 'botblocker-security' ) )
                    ->withItems( static function () use ( $data ): void {
                        TextInput::make()
                            ->withName( 'tls_fingerprint_trusted_proxy' )
                            ->withValue( (string) $data->get( 'tls_fingerprint_trusted_proxy', '' ) )
                            ->withPlaceholder( __( 'e.g. 173.245.48.0/20', 'botblocker-security' ) )
                            ->withLabel( __( 'Trusted Proxy IP/CIDR', 'botblocker-security' ) )
                            ->withTooltip( __( 'Only accept TLS fingerprint headers from this trusted proxy IP or CIDR range. Cloudflare IPs are auto-detected.', 'botblocker-security' ) )
                            ->withAnchor( 'tls_fingerprint_trusted_proxy' )
                            ->render();
                    } )
                    ->render();

                SettingsGroup::make()
                    ->withTitle( __( 'Diagnostics', 'botblocker-security' ) )
                    ->withItems( static function () use ( $data, $ja3, $ja4 ): void {
                        ?>
                        <div class="bbcs-field" id="bbcs_current_ja3" data-anchor="current_ja3">
                            <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
                            <div class="bbcs-field-label"><span><?php esc_html_e( 'Current JA3', 'botblocker-security' ); ?></span><?php echo \BotBlocker\Component\Base::tooltip( __( 'Current JA3 fingerprint received from your web server or Cloudflare.', 'botblocker-security' ) ); ?></div>
                            <div class="bbcs-field-box"><input type="text" class="bbcs-input" name="current_ja3" readonly value="<?php echo esc_attr( $ja3 ); ?>"></div>
                        </div>
                        <div class="bbcs-field" id="bbcs_current_ja4" data-anchor="current_ja4">
                            <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- component renderer returns safe HTML ?>
                            <div class="bbcs-field-label"><span><?php esc_html_e( 'Current JA4', 'botblocker-security' ); ?></span><?php echo \BotBlocker\Component\Base::tooltip( __( 'Current JA4 fingerprint received from your web server.', 'botblocker-security' ) ); ?></div>
                            <div class="bbcs-field-box"><input type="text" class="bbcs-input" name="current_ja4" readonly value="<?php echo esc_attr( $ja4 ); ?>"></div>
                        </div>
                        <?php
                    } )
                    ->render();

                SettingsGroup::make()
                    ->withTitle( __( 'Documentation', 'botblocker-security' ) )
                    ->withItems( static function (): void {
                        ?>
                        <div class="bbcs-option bbcs-hoverbg">
                            <button type="button" class="bbcs-btn" id="bbcs-tls-fingerprint-guide-trigger">
                                <svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-doc"></use></svg>
                                <?php esc_html_e( 'Open TLS-FINGERPRINTING.md', 'botblocker-security' ); ?>
                            </button>
                            <span class="bbcs-option-label"><?php esc_html_e( 'Full guide: JA3/JA4 requirements, server modules and setup options.', 'botblocker-security' ); ?></span>
                            <span class="bbcs-help"><span class="bbcs-help-q">?</span><span class="bbcs-help-tip"><?php esc_attr_e( 'Shows the raw contents of docs/TLS-FINGERPRINTING.md shipped with the plugin.', 'botblocker-security' ); ?></span></span>
                        </div>
                        <?php
                    } )
                    ->render();
                ?>
            </div>
                <?php
            } )
            ->render();
        ?>
    </div>

    <div class="bbcs-modal-overlay" id="bbcsTlsFingerprintModal" style="display:none;">
        <div class="bbcs-modal bbcs-modal--wide">
            <div class="bbcs-modal-header">
                <div class="bbcs-modal-title">
                    <svg class="bbcs-ico bbcs-ico--sm" style="margin-right:var(--bbcs-sp-1);"><use href="#bbcs-i-doc"></use></svg>
                    <?php esc_html_e( 'TLS-FINGERPRINTING.md', 'botblocker-security' ); ?>
                </div>
                <button type="button" class="bbcs-modal-close" data-modal-close>
                    <svg class="bbcs-ico bbcs-ico--sm"><use href="#bbcs-i-x"></use></svg>
                </button>
            </div>
            <div class="bbcs-modal-body">
                <pre class="bbcs-md-view"><?php echo esc_html( $bbcs_tls_md ); ?></pre>
            </div>
            <div class="bbcs-modal-footer">
                <button type="button" class="bbcs-btn" data-modal-close><?php esc_html_e( 'Close', 'botblocker-security' ); ?></button>
            </div>
        </div>
    </div>

    <script>
    (function() {
        'use strict';
        var trigger = document.getElementById('bbcs-tls-fingerprint-guide-trigger');
        var overlay = document.getElementById('bbcsTlsFingerprintModal');
        if (!trigger || !overlay) return;

        trigger.addEventListener('click', function(e) {
            e.preventDefault();
            overlay.style.display = 'flex';
        });

        overlay.addEventListener('click', function(e) {
            var btn = e.target.closest('[data-modal-close]');
            if (btn) {
                overlay.style.display = 'none';
                return;
            }
            if (e.target === overlay) {
                overlay.style.display = 'none';
            }
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape' && overlay.style.display === 'flex') {
                overlay.style.display = 'none';
            }
        });
    })();
    </script>
<?php };
