<?php

$dir = \dirname(__FILE__);
\spl_autoload_register(function ($class) use ($dir) {
    $prefix = 'BotBlocker\\Vendor\\GlobusStudio\\QRCode\\';
    $len    = 38;
    if (\strncmp($class, $prefix, $len) !== 0) {
        return \false;
    }
    $relative = \substr($class, $len);
    $file     = $dir . '/../src/' . \str_replace('\\', '/', $relative) . '.php';
    if (\file_exists($file)) {
        require $file;
        return \true;
    }
    return \false;
});
