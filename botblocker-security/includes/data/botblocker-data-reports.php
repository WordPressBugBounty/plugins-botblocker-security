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
    // Order matters: specific browsers MUST come before generic engines they contain.
    // Chromium-based → before Chrome; Firefox-based → before Firefox; ELinks → before Links.
    $browsers = [
        // Chromium-based (contain "Chrome" and/or "Safari" in UA) - must be first
        'Opera' => 'Opera',
        'OPR' => 'Opera',
        'Edg' => 'Microsoft Edge',          // Chromium Edge (Edg/ covers Edge/ too)
        'Vivaldi' => 'Vivaldi',
        'YaBrowser' => 'Yandex Browser',
        'SamsungBrowser' => 'Samsung Internet',
        'UCBrowser' => 'UC Browser',
        'Silk' => 'Amazon Silk',
        'Whale' => 'Naver Whale',
        'Brave' => 'Brave',
        'DuckDuckGo' => 'DuckDuckGo Browser',
        'Kiwi' => 'Kiwi Browser',
        'Ecosia' => 'Ecosia Browser',
        'Maxthon' => 'Maxthon',
        'Comodo Dragon' => 'Comodo Dragon',
        'Sleipnir' => 'Sleipnir',
        'Lunascape' => 'Lunascape',
        'QQBrowser' => 'QQ Browser',
        'SogouMobileBrowser' => 'Sogou Explorer',
        'Sogou' => 'Sogou Explorer',
        'HuaweiBrowser' => 'Huawei Browser',
        'MiuiBrowser' => 'Mi Browser',
        'HeadlessChrome' => 'Headless Chrome',
        'Chromium' => 'Chromium',
        'Chrome' => 'Google Chrome',         // Catch-all Chromium

        // Firefox / Gecko-based (contain "Firefox" in UA)
        'Pale Moon' => 'Pale Moon',
        'Basilisk' => 'Basilisk',
        'Waterfox' => 'Waterfox',
        'K-Meleon' => 'K-Meleon',
        'Seamonkey' => 'SeaMonkey',
        'Tor Browser' => 'Tor Browser',
        'Firefox' => 'Mozilla Firefox',      // Catch-all Firefox

        // IE (no Chrome/Safari in UA, safe here)
        'Trident/7.0' => 'Internet Explorer 11',
        'MSIE' => 'Internet Explorer',

        // Safari must be after all Chromium-based (they all contain "Safari")
        'Safari' => 'Safari',

        // Other / niche
        'Dolphin' => 'Dolphin Browser',
        'Puffin' => 'Puffin Browser',
        'Konqueror' => 'Konqueror',
        'Falkon' => 'Falkon',
        'Avant Browser' => 'Avant Browser',
        'NetFront' => 'NetFront',
        'iCab' => 'iCab',
        'OmniWeb' => 'OmniWeb',
        'Epiphany' => 'Epiphany',
        'Midori' => 'Midori',
        'QupZilla' => 'QupZilla',
        'Otter' => 'Otter Browser',
        'Dooble' => 'Dooble',
        'BrowseX' => 'BrowseX',

        // Text-mode (ELinks before Links - "ELinks" contains "Links")
        'Lynx' => 'Lynx',
        'ELinks' => 'ELinks',
        'Links' => 'Links',

        // Generic engines - last resort fallback
        'AppleWebKit' => 'Webkit-based browser',
        'Gecko' => 'Gecko-based browser',
        'KHTML' => 'KHTML-based browser',
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
    // Order matters: specific patterns MUST come before generic ones.
    // Mobile OS before desktop counterparts (Windows Phone before Windows, etc.).
    $osArray = [
        // Mobile-first (their UAs may also contain desktop OS tokens)
        '/windows phone/i'      => 'Windows Phone',
        '/iphone/i'             => 'iOS (iPhone)',
        '/ipod/i'               => 'iOS (iPod)',
        '/ipad/i'               => 'iOS (iPad)',

        // Windows (specific → generic)
        '/windows nt 10.0/i'    => 'Windows 10/11',
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

        // macOS / Mac
        '/macintosh|mac os x/i' => 'Mac OS X',
        '/mac_powerpc/i'        => 'Mac OS 9',
        '/mac os/i'             => 'Mac OS',

        // HarmonyOS before Android (Huawei devices contain "Android" in UA too)
        '/harmonyos/i'          => 'HarmonyOS',

        // Fire OS before generic Android (Amazon Fire tablets)
        '/\bsilk\b/i'          => 'Fire OS',

        // Android (specific version → generic)
        '/android 15/i'         => 'Android 15',
        '/android 14/i'         => 'Android 14',
        '/android 13/i'         => 'Android 13',
        '/android 12/i'         => 'Android 12',
        '/android 11/i'         => 'Android 11',
        '/android 10/i'         => 'Android 10',
        '/android/i'            => 'Android',

        // Other mobile
        '/kaios/i'              => 'KaiOS',
        '/blackberry/i'         => 'BlackBerry',
        '/webos/i'              => 'webOS',
        '/tizen/i'              => 'Tizen',
        '/sailfish/i'           => 'Sailfish OS',
        '/symbian/i'            => 'Symbian OS',

        // Chrome OS
        '/cros/i'               => 'Chrome OS',

        // Linux distros (specific → generic; use word boundaries to avoid false positives)
        '/ubuntu/i'             => 'Ubuntu',
        '/fedora/i'             => 'Fedora',
        '/centos/i'             => 'CentOS',
        '/red hat/i'            => 'Red Hat',
        '/debian/i'             => 'Debian',
        '/\barch linux\b/i'     => 'Arch Linux',
        '/manjaro/i'            => 'Manjaro',
        '/gentoo/i'             => 'Gentoo',
        '/slackware/i'          => 'Slackware',
        '/linux mint/i'         => 'Linux Mint',
        '/elementary os/i'      => 'elementary OS',
        '/opensuse/i'           => 'openSUSE',

        // BSD / Unix
        '/freebsd/i'            => 'FreeBSD',
        '/openbsd/i'            => 'OpenBSD',
        '/netbsd/i'             => 'NetBSD',
        '/sunos/i'              => 'Sun Solaris',

        // Generic Linux (last of *nix)
        '/linux/i'              => 'Linux',

        // Other / niche
        '/beos/i'               => 'BeOS',
        '/nintendo/i'           => 'Nintendo',
        '/playstation/i'        => 'PlayStation',
        '/xbox/i'               => 'Xbox',
    ];

    foreach ($osArray as $regex => $value) {
        if (preg_match($regex, $userAgent)) {
            return $value;
        }
    }

    return 'Unknown OS';
}