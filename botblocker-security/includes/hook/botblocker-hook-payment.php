<?php
if ( ! defined( 'ABSPATH' ) ) exit;

function bbcs_payment_maybe_autoenable_bypass(): bool {
    if ( ! function_exists( 'bbcs_payment_detect_ecommerce' ) ) {
        return false;
    }

    if ( function_exists( 'bbcs_get_option' ) ) {
        $already = (int) bbcs_get_option( 'bbcs_payment_autoenabled_done', 0 );
        if ( $already === 1 ) {
            return false;
        }
    }

    if ( ! bbcs_payment_detect_ecommerce() ) {
        return false;
    }

    global $wpdb;
    if ( ! isset( $wpdb->bbcs_settings ) ) {
        return false;
    }

    // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
    $current = $wpdb->get_var(
        $wpdb->prepare(
            "SELECT `value` FROM `{$wpdb->bbcs_settings}` WHERE `key` = %s",
            'payment_bypass_enable'
        )
    );

    if ( (int) $current !== 1 ) {
        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $wpdb->replace(
            $wpdb->bbcs_settings,
            [ 'key' => 'payment_bypass_enable', 'value' => '1' ],
            [ '%s', '%s' ]
        );

        // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
        $log_existing = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT `value` FROM `{$wpdb->bbcs_settings}` WHERE `key` = %s",
                'payment_bypass_log'
            )
        );
        if ( $log_existing === null ) {
            // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
            $wpdb->replace(
                $wpdb->bbcs_settings,
                [ 'key' => 'payment_bypass_log', 'value' => '1' ],
                [ '%s', '%s' ]
            );
        }

        if ( function_exists( 'bbcs_generateSettingsFileFromDb' ) ) {
            bbcs_generateSettingsFileFromDb();
        }

        if ( function_exists( 'bbcs_update_option' ) ) {
            bbcs_update_option( 'bbcs_payment_bypass_autoenabled_at', time() );
        }
    }

    if ( function_exists( 'bbcs_update_option' ) ) {
        bbcs_update_option( 'bbcs_payment_autoenabled_done', 1 );
    }

    return true;
}

function bbcs_payment_on_plugin_activated( $plugin, $network_wide = false ): void {
    unset( $plugin, $network_wide );
    bbcs_payment_maybe_autoenable_bypass();
}

function bbcs_payment_on_upgrader_complete( $upgrader, $hook_extra ): void {
    unset( $upgrader );
    if ( ! is_array( $hook_extra ) ) {
        return;
    }
    $type   = isset( $hook_extra['type'] ) ? (string) $hook_extra['type'] : '';
    $action = isset( $hook_extra['action'] ) ? (string) $hook_extra['action'] : '';
    if ( $type !== 'plugin' || $action !== 'install' ) {
        return;
    }
    bbcs_payment_maybe_autoenable_bypass();
}

function bbcs_payment_admin_init_check(): void {
    if ( ! is_admin() ) {
        return;
    }
    if ( function_exists( 'wp_doing_ajax' ) && wp_doing_ajax() ) {
        return;
    }
    if ( function_exists( 'wp_doing_cron' ) && wp_doing_cron() ) {
        return;
    }
    if ( ! function_exists( 'bbcs_get_option' ) ) {
        return;
    }

    $marker = (int) bbcs_get_option( 'bbcs_payment_autodetect_lastrun', 0 );
    if ( $marker > 0 && ( time() - $marker ) < DAY_IN_SECONDS ) {
        return;
    }
    if ( function_exists( 'bbcs_update_option' ) ) {
        bbcs_update_option( 'bbcs_payment_autodetect_lastrun', time() );
    }

    bbcs_payment_maybe_autoenable_bypass();
}

add_action( 'activated_plugin', 'bbcs_payment_on_plugin_activated', 10, 2 );
add_action( 'upgrader_process_complete', 'bbcs_payment_on_upgrader_complete', 20, 2 );
add_action( 'admin_init', 'bbcs_payment_admin_init_check', 99 );
