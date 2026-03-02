<?php
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

trait BotBlockerCookieTrait {
    
    public function set_bot_blocker_cookie() : void
    {
        $this->set_cookie(
            $this->settings->cookie, 
            $this->uid, 
            $this->time + 31536000, // 1 year
            false, 
            $this->settings->samesite
        ); 
    }

    public function set_allow_cookie_uid() : void
    {
        $this->set_cookie(
            $this->uid, 
            md5($this->settings->salt . $this->settings->cloud_api_pass . $this->host . $this->useragent . $this->ip . $this->time) . '-' . $this->time,
            $this->time + $this->settings->cookie_lifetime, 
            false,
            $this->settings->samesite
        );    
    }

    public function increment_hit_cookie_counter() : void
    {
        $this->set_cookie(
            $this->settings->cookie . '_hits', 
            $this->cookie_hits_counter, 
            $this->time + 86400, // 1 day
            false, 
            $this->settings->samesite
        );
    }

    public function reset_cookies_by_overflow() : void
    {
        $this->set_cookie(
            $this->settings->cookie . '_hits', 
            0, 
            $this->time - 100, 
            false, 
            $this->settings->samesite
        );
        $this->set_cookie(
            $this->uid, 
            0, 
            $this->time - 100, 
            false, 
            $this->settings->samesite
        );
    }

    public function set_secret_cookie() : void
    {
        $this->set_cookie(
            $this->settings->secret_botblocker_get_param, 
            1, 
            $this->time + 2592000, // 1 month
            true, 
            $this->settings->samesite
        );
    }

    public function delete_cookie(string $name) : void
    {
        if ( empty( $name ) ) {
            return;
        }
        
        $this->set_cookie(
            $name,      
            '',        
            $this->time - 100,  
            false,
            $this->settings->samesite
        );
    }

    protected function set_cookie(string $name, $value, int $expires, bool $dot, string $samesite): void
    {
        if ( empty( $name ) ) {
            return;
        }
        
        $samesites = ['Lax', 'Strict', 'None'];
        if ( ! in_array($samesite, $samesites, true) ) {
            $samesite = 'None';
        }

        if ( headers_sent() ) {
            return;
        }

        $domain = '';
        if ( $dot ) {

            $base_host = defined('COOKIE_DOMAIN') && COOKIE_DOMAIN ? COOKIE_DOMAIN : '';

            if ( '' === $base_host ) {
                $parsed = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
                if ( is_string( $parsed ) ) {
                    $base_host = $parsed;
                }
            }
            if ( is_string( $base_host ) && $base_host !== '' ) {

                $host = strtolower( preg_replace( '/:\d+$/', '', ltrim( $base_host, '.' ) ) );

                if ( function_exists('idn_to_ascii') ) {
                    $idn = idn_to_ascii( $host, 0, defined('INTL_IDNA_VARIANT_UTS46') ? INTL_IDNA_VARIANT_UTS46 : 0 );
                    if ( is_string( $idn ) && $idn !== '' ) {
                        $host = $idn;
                    }
                }

                if ( filter_var( $host, FILTER_VALIDATE_DOMAIN, FILTER_FLAG_HOSTNAME ) ) {
                    $domain = '.' . $host;
                }
            }
        }

        $secure = ( $samesite === 'None' ) ? true : ( function_exists('wp_is_using_https') ? wp_is_using_https() : is_ssl() );

        $httponly = true;

        if ( version_compare( PHP_VERSION, '7.3.0', '>=' ) ) {
            setcookie( $name, (string) $value, [
                'expires'  => $expires,
                'path'     => '/',
                'domain'   => $domain,
                'secure'   => $secure,
                'httponly' => $httponly,
                'samesite' => $samesite,
            ] );
        } else {
            setcookie( $name, (string) $value, $expires, '/' . ( $domain ? '; Domain=' . $domain : '' ) . ( $secure ? '; Secure' : '' ) . ( $httponly ? '; HttpOnly' : '' ) );
        }
    }

}
