<?php

namespace BotBlocker\Vendor;

if (!\defined('ABSPATH')) {
    exit;
}
if (!\class_exists('BotBlocker\Vendor\Freemius_Exception')) {
    exit;
}
if (!\class_exists('BotBlocker\Vendor\Freemius_OAuthException')) {
    class Freemius_OAuthException extends Freemius_Exception
    {
        public function __construct($pResult)
        {
            parent::__construct($pResult);
        }
    }
    \class_alias('BotBlocker\Vendor\Freemius_OAuthException', 'Freemius_OAuthException', \false);
}
