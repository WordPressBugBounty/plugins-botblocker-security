<?php
if ( ! defined( 'ABSPATH' ) ) exit;

trait BotBlockerPaymentTrait {

    public function check_payment_bypass(): bool
    {
        if ( ! isset( $this->settings ) ) {
            return false;
        }

        $enabled = isset( $this->settings->payment_bypass_enable )
            ? (int) $this->settings->payment_bypass_enable
            : 0;

        if ( $enabled !== 1 ) {
            return false;
        }

        if ( ! $this->is_payment_callback_request() ) {
            return false;
        }

        $reason = $this->payment_bypass_reason ?? 'Payment gateway callback';

        $this->visitorType    = self::VISITOR_LEGALBOT;
        $this->white_bot      = 'payment-gateway';
        $this->result_of_action = $reason;

        $log_enabled = isset( $this->settings->payment_bypass_log )
            ? (int) $this->settings->payment_bypass_log
            : 1;

        if ( $log_enabled === 1 && function_exists( 'bbcs_storeData' ) ) {
            bbcs_storeData( $reason, 81 );
        }

        if ( function_exists( 'bbcs_process_hit' ) ) {
            bbcs_process_hit( 81 );
        }

        return true;
    }

    public function is_payment_callback_request(): bool
    {
        $uri = isset( $this->uri ) ? (string) $this->uri : '';
        if ( $uri === '' && isset( $_SERVER['REQUEST_URI'] ) ) {
            $uri = (string) wp_unslash( $_SERVER['REQUEST_URI'] );
        }

        if ( $uri === '' ) {
            return false;
        }

        $method = isset( $this->request_method ) ? strtoupper( (string) $this->request_method ) : '';
        if ( $method === '' && isset( $_SERVER['REQUEST_METHOD'] ) ) {
            $method = strtoupper( (string) preg_replace( '/[^A-Za-z]/', '', (string) wp_unslash( $_SERVER['REQUEST_METHOD'] ) ) );
        }

        if ( $method !== '' && ! in_array( $method, [ 'GET', 'POST', 'HEAD' ], true ) ) {
            return false;
        }

        $matched = $this->match_payment_path( $uri );
        if ( $matched !== null ) {
            $this->payment_bypass_reason = 'Payment bypass: path ' . $matched;
            return true;
        }

        $matched = $this->match_payment_query_key( $uri );
        if ( $matched !== null ) {
            $this->payment_bypass_reason = 'Payment bypass: query ' . $matched;
            return true;
        }

        $matched = $this->match_payment_action( $uri );
        if ( $matched !== null ) {
            $this->payment_bypass_reason = 'Payment bypass: action ' . $matched;
            return true;
        }

        $matched = $this->match_payment_signature_header();
        if ( $matched !== null ) {
            $this->payment_bypass_reason = 'Payment bypass: header ' . $matched;
            return true;
        }

        return false;
    }

    public function match_payment_path( string $uri ): ?string
    {
        if ( $uri === '' ) {
            return null;
        }

        $path = function_exists( 'wp_parse_url' )
            ? wp_parse_url( $uri, PHP_URL_PATH )
            : parse_url( $uri, PHP_URL_PATH );

        if ( ! is_string( $path ) || $path === '' ) {
            $path = explode( '?', $uri, 2 )[0];
        }

        foreach ( bbcs_get_payment_paths() as $needle ) {
            if ( $needle === '' ) {
                continue;
            }
            if ( stripos( $path, $needle ) !== false ) {
                return $needle;
            }
        }
        return null;
    }

    public function match_payment_query_key( string $uri ): ?string
    {
        $query = '';
        $qpos = strpos( $uri, '?' );
        if ( $qpos !== false ) {
            $query = substr( $uri, $qpos + 1 );
        }

        $params = [];
        if ( $query !== '' ) {
            parse_str( $query, $params );
        }

        if ( empty( $params ) && ! empty( $_GET ) ) {
            $params = wp_unslash( $_GET );
        }

        if ( empty( $params ) || ! is_array( $params ) ) {
            return null;
        }

        $keys_lc = array_change_key_case( $params, CASE_LOWER );

        foreach ( bbcs_get_payment_query_keys() as $key ) {
            $key_lc = strtolower( $key );
            if ( array_key_exists( $key_lc, $keys_lc ) ) {
                return $key;
            }
        }
        return null;
    }

    public function match_payment_action( string $uri ): ?string
    {
        if ( stripos( $uri, 'admin-ajax.php' ) === false
            && stripos( $uri, 'admin-post.php' ) === false ) {
            return null;
        }

        $action = '';
        if ( ! empty( $_REQUEST['action'] ) ) {
            $action = strtolower( sanitize_text_field( wp_unslash( (string) $_REQUEST['action'] ) ) );
        }

        if ( $action === '' ) {
            $qpos = strpos( $uri, '?' );
            if ( $qpos !== false ) {
                parse_str( substr( $uri, $qpos + 1 ), $parsed );
                if ( ! empty( $parsed['action'] ) ) {
                    $action = strtolower( (string) $parsed['action'] );
                }
            }
        }

        if ( $action === '' ) {
            return null;
        }

        $exact = bbcs_get_payment_actions();
        foreach ( $exact as $known ) {
            if ( strtolower( $known ) === $action ) {
                return $known;
            }
        }

        foreach ( bbcs_get_payment_action_substrings() as $needle ) {
            if ( $needle === '' ) {
                continue;
            }
            if ( stripos( $action, $needle ) !== false ) {
                return $needle;
            }
        }

        return null;
    }

    public function match_payment_signature_header(): ?string
    {
        foreach ( bbcs_get_payment_signature_headers() as $header ) {
            if ( ! empty( $_SERVER[ $header ] ) ) {
                return $header;
            }
        }
        return null;
    }
}
