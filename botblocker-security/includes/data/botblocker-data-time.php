<?php 
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function bbcs_get_cache_durations(): array
{
    return [
        300     => __('5 minutes', 'botblocker-security'),
        900     => __('15 minutes', 'botblocker-security'),
        1800    => __('30 minutes', 'botblocker-security'),
        3600    => __('1 hour', 'botblocker-security'),
        7200    => __('2 hours', 'botblocker-security'),
        21600   => __('6 hours', 'botblocker-security'),
        43200   => __('12 hours', 'botblocker-security'),
    ];
}

function bbcs_get_cookie_lifetimes(): array
{
    return [
        86400    => __('1 day', 'botblocker-security'),
        172800   => __('2 days', 'botblocker-security'),
        259200   => __('3 days', 'botblocker-security'),
        604800   => __('1 week', 'botblocker-security'),
        1209600  => __('2 weeks', 'botblocker-security'),
        2592000  => __('1 month', 'botblocker-security'),
        7776000  => __('3 months', 'botblocker-security'),
        15552000 => __('6 months', 'botblocker-security'),
        23328000 => __('9 months', 'botblocker-security'),
        31536000 => __('1 year', 'botblocker-security'),
    ];
}

function bbcs_get_ptr_lifetimes(): array
{
    return [
        86400    => __('1 day', 'botblocker-security'),
        172800   => __('2 days', 'botblocker-security'),
        259200   => __('3 days', 'botblocker-security'),
        604800   => __('1 week', 'botblocker-security'),
        1209600  => __('2 weeks', 'botblocker-security'),
        2592000  => __('1 month' , 'botblocker-security')
    ];
}
