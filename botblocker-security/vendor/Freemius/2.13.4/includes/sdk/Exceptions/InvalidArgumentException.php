<?php

namespace BotBlocker\Vendor;

if (!\defined('ABSPATH')) {
    exit;
}
if (!\class_exists('BotBlocker\Vendor\Freemius_Exception')) {
    exit;
}
if (!\class_exists('BotBlocker\Vendor\Freemius_InvalidArgumentException')) {
    class Freemius_InvalidArgumentException extends Freemius_Exception
    {
    }
    \class_alias('BotBlocker\Vendor\Freemius_InvalidArgumentException', 'Freemius_InvalidArgumentException', \false);
}
