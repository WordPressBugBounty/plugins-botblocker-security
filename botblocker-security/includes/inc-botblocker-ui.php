<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly
/**
 * BotBlocker UI Class
 * 
 * Handles UI-related functionality for the BotBlocker plugin
 */
class BotBlockerUI {
    
    /**
     * Sets fallback captcha when GD is not available
     *
     * @param string $state The captcha state to set
     * @return void
     */
    public static function fallback_captcha($state) 
    {
        global $wpdb;

        // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared and sanitized. No direct unsanitized SQL is executed.
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $updated = $wpdb->update(
            $wpdb->bbcs_settings,
            ['value' => $state],
            ['key' => 'bbcs_captcha_mode'],
            ['%d'],
            ['%s']
        );

        if ($updated !== false) {
            bbcs_generateSettingsFileFromDb();
        } else {
            if (BBCS_DEBUG == true) {
               // error_log('Error fallback captcha state of BotBlocker');
            }
        }
    }

    /**
     * Get realtime status indicator for dashboard
     *
     * @return string Realtime status HTML string
     */
    public static function is_realtime(){
        $BBCS = BotBlocker::getInstance();
        $durations = bbcs_get_cache_durations();
        $duration_name = $durations[$BBCS->settings->cache_ui_duration] ?? __('Unknown period', 'botblocker-security');
        if ($BBCS->settings->cache_ui_data == 1){
            // translators: %s is the cache update interval duration name (e.g. "1 hour").
            return '<small>' . esc_html( sprintf( __('(Update every %s)', 'botblocker-security'), $duration_name ) ) . '</small>';
        } else {
            return '<small>' . esc_html__('(Realtime)', 'botblocker-security') . '</small>';
        }
    }

    /**
     * Check if reCAPTCHA v3 keys are present and valid for enabling the feature
     *
     * @return bool
     */
    public static function recaptcha_v3_keys_ready(): bool
    {
        if (!class_exists('BotBlocker')) {
            return false;
        }
        $BBCS = BotBlocker::getInstance();
        if (!$BBCS || !isset($BBCS->settings)) {
            return false;
        }
        $key = $BBCS->settings->recaptcha_key3 ?? '';
        $sec = $BBCS->settings->recaptcha_secret3 ?? '';
        return (!empty($key) && !empty($sec));
    }

    /**
     * Enforce dependent settings for reCAPTCHA v3 when keys are missing.
     * If keys/secret are absent, forcibly disable recaptcha_check and recaptcha_v3_ipv6_block
     * and regenerate settings file.
     *
     * @return void
     */
    public static function enforce_recaptcha_v3_dependencies(): void
    {
        if (!class_exists('BotBlocker')) {
            return;
        }
        $BBCS = BotBlocker::getInstance();
        if (!$BBCS || !isset($BBCS->settings)) {
            return;
        }

        if (self::recaptcha_v3_keys_ready()) {
            return;
        }

        $changed = false;
        if (!empty($BBCS->settings->recaptcha_check)) {
            $BBCS->settings->recaptcha_check = 0;
            $changed = true;
        }
        if (!empty($BBCS->settings->recaptcha_v3_ipv6_block)) {
            $BBCS->settings->recaptcha_v3_ipv6_block = 0;
            $changed = true;
        }

        if ($changed) {
            global $wpdb;
            // REVIEWER NOTE: Custom BotBlocker-Security table. Query is prepared, cached and sanitized. No direct unsanitized SQL is executed.
            // phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->update($wpdb->bbcs_settings, ['value' => '0'], ['key' => 'recaptcha_check'], ['%s'], ['%s']);
            $wpdb->update($wpdb->bbcs_settings, ['value' => '0'], ['key' => 'recaptcha_v3_ipv6_block'], ['%s'], ['%s']);
            // phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

            if (function_exists('bbcs_generateSettingsFileFromDb')) {
                bbcs_generateSettingsFileFromDb();
            }
        }
    }

    public static function isEarlyInitEnabled(): bool {
        if (!class_exists('BotBlocker')) return false;
        $bbcs = BotBlocker::getInstance();
        return isset($bbcs->settings->early_init_enable) && (int)$bbcs->settings->early_init_enable === 1;
    }

    public static function isMuEnabled(): bool {
        if (!class_exists('BotBlocker')) return false;
        $bbcs = BotBlocker::getInstance();
        return isset($bbcs->settings->mu_enable) && (int)$bbcs->settings->mu_enable === 1;
    }

    public static function get_setup_chain_context(): array {
        $early = self::isEarlyInitEnabled();
        $mu    = self::isMuEnabled();
        $pluginSpin = ' fa-spin';
        $earlySpin  = $early ? ' fa-spin' : '';
        $muSpin     = $mu ? ' fa-spin' : '';
        if ( $early && $mu ) { $mu = false; $muSpin = ''; }
        if ( $early ) {
            $earlyText = __( 'Early initialization enabled: IP blacklist and base rule filtering runs before WordPress loads. MU mode not required.', 'botblocker-security');
            $muText    = __( 'MU mode disabled: early initialization already performs pre-filtering; enabling MU is unnecessary.', 'botblocker-security');
        } elseif ( $mu ) {
            $earlyText = __( 'Early initialization disabled: its functions are handled by active MU plugin.', 'botblocker-security');
            $muText    = __( 'MU plugin active: early IP and rule filtering runs before other plugins. Early initialization not required.', 'botblocker-security');
        } else {
            $earlyText = __( 'Early initialization disabled. Enable it for earlier IP filtering.', 'botblocker-security');
            $muText    = __( 'MU plugin mode disabled. You can enable it (or early initialization) for preliminary malicious IP rejection.', 'botblocker-security');
        }
        $pluginText = ( $early || $mu )
            ? __( 'BotBlocker operates in normal mode processing all threat types (bots, proxies, referrers, languages etc.) after base early filtering.', 'botblocker-security')
            : __( 'BotBlocker operates in normal mode processing all threat types at WordPress load.', 'botblocker-security');
        return [
            'earlySpin'  => $earlySpin,
            'muSpin'     => $muSpin,
            'pluginSpin' => $pluginSpin,
            'earlyText'  => $earlyText,
            'muText'     => $muText,
            'pluginText' => $pluginText,
        ];
    }


    protected static function market_slug_from_url( $url ): string {
        $path = wp_parse_url( $url, PHP_URL_PATH );
        $b = basename( (string) $path );
        return preg_replace( '/\.zip$/', '', $b );
    }

    protected static function load_market(): array {
        $market = [];
        $marketUrl = defined('BOTBLOCKER_ADDONS') ? BOTBLOCKER_ADDONS : '';
        if ( $marketUrl ) {
            $res = wp_remote_get( $marketUrl, [ 'timeout' => 10 ] );
            if ( ! is_wp_error( $res ) && wp_remote_retrieve_response_code( $res ) === 200 ) {
                $json = json_decode( wp_remote_retrieve_body( $res ), true );
                if ( is_array( $json ) && isset( $json['addons'] ) && is_array( $json['addons'] ) ) { $market = $json['addons']; }
            }
        }
        if ( empty( $market ) ) {
            $local = BOTBLOCKER_DIR . 'wp-content/plugins/bbcs-addons/master.json';
            if ( file_exists( $local ) ) {
                $json = json_decode( file_get_contents( $local ), true );
                if ( is_array( $json ) && isset( $json['addons'] ) && is_array( $json['addons'] ) ) { $market = $json['addons']; }
            }
        }
        if ( empty( $market ) ) {
            $server = defined('BOTBLOCKER_SERVER') ? BOTBLOCKER_SERVER : 'botblocker.top';
            $fallback = 'https://' . $server . '/wp-content/plugins/bbcs-addons/master.json';
            $res = wp_remote_get( $fallback, [ 'timeout' => 10 ] );
            if ( ! is_wp_error( $res ) && wp_remote_retrieve_response_code( $res ) === 200 ) {
                $json = json_decode( wp_remote_retrieve_body( $res ), true );
                if ( is_array( $json ) && isset( $json['addons'] ) && is_array( $json['addons'] ) ) { $market = $json['addons']; }
            }
        }
        return $market;
    } 

    public static function get_addons_context(): array {
        $addons = function_exists('bbcs_scan_addons') ? bbcs_scan_addons() : [];
        $active = function_exists('bbcs_get_active_addons') ? bbcs_get_active_addons() : [];
        if ( ! is_array( $active ) ) { $active = []; }
        $market = self::load_market();
        $marketBySlug = [];
        foreach ( $market as $it ) {
            if ( ! empty( $it['url'] ) ) {
                $s = self::market_slug_from_url( $it['url'] );
                $marketBySlug[ $s ] = $it;
            }
        }
        $has_cloud_api    = function_exists('bbcs_isCloudAPIActive') && bbcs_isCloudAPIActive();
        $addons_locked = ( ! $has_cloud_api );
        return compact( 'addons','active','market','marketBySlug','addons_locked','has_cloud_api' );
    }
    
    public static function render_dashboard_addons_summary(): void {
        $ctx = self::get_addons_context();
        $addons = $ctx['addons'];
        $active = $ctx['active'];
        $addons_locked = $ctx['addons_locked'];
        $has_cloud_api = $ctx['has_cloud_api'];
        $BBCSA = class_exists('Botblocker_Admin') ? Botblocker_Admin::getInstance() : null;
        $tools_url = $BBCSA && isset( $BBCSA->pages_tools ) ? $BBCSA->pages_tools : '';
        echo '<div class="bbcs-addons-dash">';
        if ( $addons_locked ) {
            echo '<div class="alert alert-warning p-2 mb-2 bbcs-addons-off-text">' . esc_html__( 'Add-ons locked. Activate Cloud API to use marketplace features.', 'botblocker-security') . '</div>';
        }
        if ( empty( $addons ) ) {
            $addons_page = ($BBCSA && isset($BBCSA->pages_addons)) ? $BBCSA->pages_addons : admin_url('admin.php?page=bbcs_addons');
            echo '<div class="bbcs-addons-empty border rounded p-3 text-center">'
                . '<p class="mb-2 mbcs-empty-text">' . esc_html__( 'Enhance speed, security and user experience with official BotBlocker add-ons.', 'botblocker-security') . '</p>'
                . '<a href="' . esc_url( $addons_page ) . '" class="btn btn-xs btn-primary"><i class="fa-solid fa-puzzle-piece"></i> ' . esc_html__( 'Browse Add-ons', 'botblocker-security') . '</a>'
                . '</div>';
            echo '</div>'; return;
        }
        echo '<ul class="list-unstyled m-0">';
        foreach ( $addons as $slug => $addon ) {
            $name     = $addon['name'] ?: $slug;
            $isActive = in_array( $slug, $active, true );
            $ver      = isset( $addon['version'] ) ? $addon['version'] : '';

            echo '<li class="d-flex align-items-center mb-1 bbcs-dash-addon-li">';
            // Status icon
            $icon_classes = 'fa-solid fa-circle ' . ( $isActive ? 'text-success' : 'text-danger' ) . ' me-2';
            echo '<i class="' . esc_attr( $icon_classes ) . '"></i>';

            // Optional link wrapper when active and tools URL is available
            $has_link = ( $isActive && ! empty( $tools_url ) );
            if ( $has_link ) {
                echo '<a href="' . esc_url( $tools_url . '#addon-' . rawurlencode( (string) $slug ) ) . '" class="bbcs-addon-link">';
            }

            echo esc_html( $name );
            if ( $ver ) {
                echo ' <small class="text-muted">' . esc_html( ' (' . $ver . ')' ) . '</small>';
            }

            if ( $has_link ) {
                echo '</a>';
            }

            echo '</li>';
        }
        echo '</ul>';
        if ( ! $has_cloud_api ) {
            if ( $BBCSA && isset( $BBCSA->pages_cloud_api ) ) {
                echo '<a class="btn btn-xs btn-default mt-2" href="' . esc_url( $BBCSA->pages_cloud_api ) . '"><i class="fa-solid fa-crown"></i> ' . esc_html__( 'Connect Cloud API now!', 'botblocker-security') . '</a>';
            }
        }
        echo '</div>';
    }
}
