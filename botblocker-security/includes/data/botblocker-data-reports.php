<?php 
if ( ! defined( 'ABSPATH' ) ) exit; // Exit if accessed directly

/**
 * Get the browser name from the user agent
 *
 * @param string $userAgent The user agent
 * @return string The browser name
 */
function bbcs_getBrowserType($userAgent)
{
    $browsers = [
        'Opera' => 'Opera',
        'OPR' => 'Opera',
        'Edge' => 'Microsoft Edge',
        'Edg' => 'Microsoft Edge',
        'Chrome' => 'Google Chrome',
        'Safari' => 'Safari',
        'Firefox' => 'Mozilla Firefox',
        'MSIE' => 'Internet Explorer',
        'Trident/7.0' => 'Internet Explorer 11',
        'Vivaldi' => 'Vivaldi',
        'Brave' => 'Brave',
        'UCBrowser' => 'UC Browser',
        'YaBrowser' => 'Yandex Browser',
        'SamsungBrowser' => 'Samsung Internet',
        'Silk' => 'Amazon Silk',
        'Maxthon' => 'Maxthon',
        'Avant Browser' => 'Avant Browser',
        'Seamonkey' => 'SeaMonkey',
        'Konqueror' => 'Konqueror',
        'Falkon' => 'Falkon',
        'Webkit' => 'Webkit-based browser',
        'Gecko' => 'Gecko-based browser',
        'KHTML' => 'KHTML-based browser',
        'NetFront' => 'NetFront',
        'iCab' => 'iCab',
        'OmniWeb' => 'OmniWeb',
        'Lynx' => 'Lynx',
        'Links' => 'Links',
        'ELinks' => 'ELinks',
        'BrowseX' => 'BrowseX',
        'Epiphany' => 'Epiphany',
        'K-Meleon' => 'K-Meleon',
        'Midori' => 'Midori',
        'QupZilla' => 'QupZilla',
        'Otter' => 'Otter Browser',
        'Dooble' => 'Dooble',
        'Pale Moon' => 'Pale Moon',
        'Basilisk' => 'Basilisk',
        'Waterfox' => 'Waterfox',
        'Comodo Dragon' => 'Comodo Dragon',
        'Sleipnir' => 'Sleipnir',
        'Lunascape' => 'Lunascape',
        'QQ' => 'QQ Browser',
        'Sogou' => 'Sogou Explorer',
        'Chromium' => 'Chromium'
    ];

    foreach ($browsers as $key => $value) {
        if (stripos($userAgent, $key) !== false) {
            return $value;
        }
    }

    return 'Unknown Browser';
}

/**
 * Get the operating system from the user agent
 *
 * @param string $userAgent The user agent
 * @return string The operating system
 */
function bbcs_getOSType($userAgent)
{
    $osArray = [
        '/windows nt 10.0/i'    => 'Windows 10',
        '/windows nt 6.3/i'     => 'Windows 8.1',
        '/windows nt 6.2/i'     => 'Windows 8',
        '/windows nt 6.1/i'     => 'Windows 7',
        '/windows nt 6.0/i'     => 'Windows Vista',
        '/windows nt 5.2/i'     => 'Windows Server 2003/XP x64',
        '/windows nt 5.1/i'     => 'Windows XP',
        '/windows xp/i'         => 'Windows XP',
        '/windows nt 5.0/i'     => 'Windows 2000',
        '/windows me/i'         => 'Windows ME',
        '/windows 98/i'         => 'Windows 98',
        '/windows 95/i'         => 'Windows 95',
        '/windows nt 4.0/i'     => 'Windows NT 4.0',
        '/win16/i'              => 'Windows 3.11',
        '/iphone/i'             => 'iOS (iPhone)',
        '/ipod/i'               => 'iOS (iPod)',
        '/ipad/i'               => 'iOS (iPad)',
        '/macintosh|mac os x/i' => 'Mac OS X',
        '/mac_powerpc/i'        => 'Mac OS 9',
        '/mac os/i'             => 'Mac OS',      
        '/android 14/i'         => 'Android 14',        
        '/android 13/i'         => 'Android 13',
        '/android 12/i'         => 'Android 12',
        '/android 11/i'         => 'Android 11',
        '/android 10/i'         => 'Android 10',
        '/android/i'            => 'Android',
        '/blackberry/i'         => 'BlackBerry',
        '/webos/i'              => 'webOS',
        '/windows phone/i'      => 'Windows Phone',
        '/cros/i'               => 'Chrome OS',
        '/tizen/i'              => 'Tizen',
        '/sailfish/i'           => 'Sailfish OS',
        '/symbian/i'            => 'Symbian OS',
        '/fedora/i'             => 'Fedora',
        '/centos/i'             => 'CentOS',
        '/red hat/i'            => 'Red Hat',
        '/debian/i'             => 'Debian',
        '/ubuntu/i'             => 'Ubuntu',
        '/arch/i'               => 'Arch Linux',
        '/manjaro/i'            => 'Manjaro',
        '/gentoo/i'             => 'Gentoo',
        '/slackware/i'          => 'Slackware',
        '/mint/i'               => 'Linux Mint',
        '/elementary/i'         => 'elementary OS',
        '/opensuse/i'           => 'openSUSE',
        '/freebsd/i'            => 'FreeBSD',
        '/openbsd/i'            => 'OpenBSD',
        '/netbsd/i'             => 'NetBSD',
        '/sunos/i'              => 'Sun Solaris',
        '/beos/i'               => 'BeOS',
        '/nintendo/i'           => 'Nintendo',
        '/playstation/i'        => 'PlayStation',
        '/xbox/i'               => 'Xbox',
        '/linux/i'              => 'Linux'
    ];

    foreach ($osArray as $regex => $value) {
        if (preg_match($regex, $userAgent)) {
            return $value;
        }
    }

    return 'Unknown OS';
}