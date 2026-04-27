<?php

namespace BotBlocker\Vendor;

$dir = \dirname(__FILE__);
\spl_autoload_register(function ($class) use ($dir) {
    $classMap = [
        // "maxmind-db/reader"
        "BotBlocker\\Vendor\\MaxMind\\Db\\Reader"                              => $dir . "/../src/MaxMind/Db/Reader.php",
        "BotBlocker\\Vendor\\MaxMind\\Db\\Reader\\Decoder"                     => $dir . "/../src/MaxMind/Db/Reader/Decoder.php",
        "BotBlocker\\Vendor\\MaxMind\\Db\\Reader\\InvalidDatabaseException"    => $dir . "/../src/MaxMind/Db/Reader/InvalidDatabaseException.php",
        "BotBlocker\\Vendor\\MaxMind\\Db\\Reader\\Metadata"                    => $dir . "/../src/MaxMind/Db/Reader/Metadata.php",
        "BotBlocker\\Vendor\\MaxMind\\Db\\Reader\\Util"                        => $dir . "/../src/MaxMind/Db/Reader/Util.php",
    ];
    $fileFound = $classMap[$class] ?? \false;
    if ($fileFound) {
        require $fileFound;
        return \true;
    }
    return \false;
});
