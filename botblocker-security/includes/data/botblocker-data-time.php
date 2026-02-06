<?php 
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

function bbcs_get_cache_durations(): array
{
    return [
        300     => '5 minutes',
        900     => '15 minutes',
        1800    => '30 minutes',
        3600    => '1 hour',
        7200    => '2 hours',
        21600   => '6 hours',
        43200   => '12 hours',
    ];
}

function bbcs_get_cookie_lifetimes(): array
{
    return [
        86400    => '1 day',     
        172800   => '2 days',     
        259200   => '3 days',     
        604800   => '1 week',    
        1209600  => '2 weeks',  
        2592000  => '1 month',    
        7776000  => '3 months',   
        15552000 => '6 months',  
        23328000 => '9 months',   
        31536000 => '1 year',     
    ];
}

function bbcs_get_ptr_lifetimes(): array
{
    return [
        86400    => '1 day',     
        172800   => '2 days',     
        259200   => '3 days',     
        604800   => '1 week',    
        1209600  => '2 weeks',  
        2592000  => '1 month'   
    ];
}
