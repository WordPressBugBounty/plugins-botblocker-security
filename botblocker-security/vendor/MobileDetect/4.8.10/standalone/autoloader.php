<?php

namespace BotBlocker\Vendor;

$dir = \dirname(__FILE__);
\spl_autoload_register(function ($class) use ($dir) {
    $classMap = [
        // "mobiledetect/mobiledetectlib"
        "BotBlocker\\Vendor\\Detection\\Cache\\Cache" => $dir . "/../src/Cache/Cache.php",
        "BotBlocker\\Vendor\\Detection\\Cache\\CacheException" => $dir . "/../src/Cache/CacheException.php",
        "BotBlocker\\Vendor\\Detection\\Cache\\CacheInvalidArgumentException" => $dir . "/../src/Cache/CacheInvalidArgumentException.php",
        "BotBlocker\\Vendor\\Detection\\Exception\\MobileDetectException" => $dir . "/../src/Exception/MobileDetectException.php",
        "BotBlocker\\Vendor\\Detection\\Exception\\MobileDetectExceptionCode" => $dir . "/../src/Exception/MobileDetectExceptionCode.php",
        "BotBlocker\\Vendor\\Detection\\MobileDetect" => $dir . "/../src/MobileDetect.php",
        // "psr/simple-cache"
        "BotBlocker\\Vendor\\Psr\\SimpleCache\\CacheException" => $dir . "/deps/simple-cache/src/CacheException.php",
        "BotBlocker\\Vendor\\Psr\\SimpleCache\\CacheInterface" => $dir . "/deps/simple-cache/src/CacheInterface.php",
        "BotBlocker\\Vendor\\Psr\\SimpleCache\\InvalidArgumentException" => $dir . "/deps/simple-cache/src/InvalidArgumentException.php",
    ];
    $fileFound = $classMap[$class] ?? \false;
    if ($fileFound) {
        require $fileFound;
        return \true;
    }
    return \false;
});
