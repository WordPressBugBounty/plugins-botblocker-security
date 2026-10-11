<?php

namespace BotBlocker\Vendor;

if (!\defined('ABSPATH')) {
    exit;
}
if (!\class_exists('BotBlocker\Vendor\Freemius_InvalidArgumentException')) {
    exit;
}
if (!\class_exists('BotBlocker\Vendor\Freemius_EmptyArgumentException')) {
    class Freemius_EmptyArgumentException extends Freemius_InvalidArgumentException
    {
    }
    \class_alias('BotBlocker\Vendor\Freemius_EmptyArgumentException', 'Freemius_EmptyArgumentException', \false);
}
