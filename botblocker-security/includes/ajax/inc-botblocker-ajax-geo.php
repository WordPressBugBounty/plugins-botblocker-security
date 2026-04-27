<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function bbcs_get_geo_countries_callback() {
    if ( ! current_user_can( bbcs_can_manage() ) ) {
        wp_send_json_error( 'Unauthorized' );
    }

    if ( ! check_ajax_referer( 'botblocker_nonce', 'nonce', false ) ) {
        wp_send_json_error( 'Invalid nonce' );
    }

    $blockedCountries = bbcs_get_option( 'bbcs_blocked_countries', [] );
    if ( is_string( $blockedCountries ) ) {
        $decoded = json_decode( $blockedCountries, true );
        if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
            $blockedCountries = $decoded;
        } else {
            $blockedCountries = array_filter( array_map( 'trim', explode( ',', $blockedCountries ) ) );
        }
    }

    if ( ! is_array( $blockedCountries ) ) {
        $blockedCountries = [];
    }

    $blockedCountries = array_values( array_unique( array_map( 'strtoupper', array_filter( $blockedCountries, function( $item ) {
        return is_string( $item ) && preg_match( '/^[A-Z]{2}$/', trim( $item ) );
    } ) ) ) );

    wp_send_json_success( $blockedCountries );
}
add_action( 'wp_ajax_bbcs_get_geo_countries', 'bbcs_get_geo_countries_callback' );

function bbcs_save_geo_countries_callback() {
    if ( ! current_user_can( bbcs_can_manage() ) ) {
        wp_send_json_error( 'Unauthorized' );
    }

    if ( ! check_ajax_referer( 'botblocker_nonce', 'nonce', false ) ) {
        wp_send_json_error( 'Invalid nonce' );
    }

    $countries = [];
    if ( isset( $_POST['countries'] ) ) {
        $countriesRaw = wp_unslash( $_POST['countries'] );
        $decoded = json_decode( $countriesRaw, true );
        if ( json_last_error() === JSON_ERROR_NONE && is_array( $decoded ) ) {
            $countries = $decoded;
        } else {
            $countries = array_filter( array_map( 'trim', explode( ',', $countriesRaw ) ) );
        }
    }

    if ( ! is_array( $countries ) ) {
        $countries = [];
    }

    $sanitized = array_values( array_unique( array_map( 'strtoupper', array_filter( $countries, function( $item ) {
        return is_string( $item ) && preg_match( '/^[A-Z]{2}$/', trim( $item ) );
    } ) ) ) );

    bbcs_update_option( 'bbcs_blocked_countries', $sanitized );
    wp_send_json_success( $sanitized );
}
add_action( 'wp_ajax_bbcs_save_geo_countries', 'bbcs_save_geo_countries_callback' );
