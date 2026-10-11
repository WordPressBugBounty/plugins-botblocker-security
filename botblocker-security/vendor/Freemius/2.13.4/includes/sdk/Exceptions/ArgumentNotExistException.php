<?php

namespace BotBlocker\Vendor;

if (!\defined('ABSPATH')) {
    exit;
}
if (!\class_exists('BotBlocker\Vendor\Freemius_InvalidArgumentException')) {
    exit;
}
if (!\class_exists('BotBlocker\Vendor\Freemius_ArgumentNotExistException')) {
    class Freemius_ArgumentNotExistException extends Freemius_InvalidArgumentException
    {
    }
    \class_alias('BotBlocker\Vendor\Freemius_ArgumentNotExistException', 'Freemius_ArgumentNotExistException', \false);
}
