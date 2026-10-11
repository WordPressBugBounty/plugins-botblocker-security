<?php

$dir = \dirname(__FILE__);
if (!\defined('BOTBLOCKER_FREEMIUS_SDK_LOADED')) {
    \define('BOTBLOCKER_FREEMIUS_SDK_LOADED', true);
    require $dir . '/../start.php';
}
