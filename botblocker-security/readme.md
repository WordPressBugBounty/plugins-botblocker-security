=== BotBlocker Security - Firewall & Bot Protection ===
Contributors: globusstudio, alukashevych, alexandrkinakh
Tags: security, firewall, anti-spam, captcha, brute force
Requires at least: 5.0
Tested up to: 6.9
Requires PHP: 7.4
Stable tag: 1.6.17
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Protect your WordPress site: firewall, bot & brute-force protection, anti-spam, multi-layer CAPTCHA, optional cloud threat intel.

== Description ==

= WordPress Security Plugin & Firewall (WAF) =

**Every day, automated bots and hackers bombard websites with attacks.** Mass botnets, fake search engine crawlers, brute-force login attempts, and spam bots can overwhelm your WordPress site - stealing data, overloading your server, and defacing content. It's a 24/7 threat to your business. If you’re looking for **WordPress site protection**, you need a proactive defense that stops these attacks before they reach your website.

**BotBlocker Security is the all-in-one solution to keep your site safe from automated threats.** This powerful **WordPress security plugin and Web Application Firewall (WAF)** acts as a dedicated **anti-bot** firewall, blocking malicious traffic at the front gate without slowing down your site.

BotBlocker's setup and onboarding experience allows anyone to secure their **WordPress site** in under 1 minute, regardless of technical expertise. You can rest assured knowing you have enabled the right **site protection** settings to protect your website.

= 🔥 WordPress Firewall (WAF) =

BotBlocker Security includes an endpoint **firewall/WAF** that identifies and blocks malicious traffic before it reaches WordPress. Built and maintained by a team focused 100% on WordPress security, our Web Application Firewall protects your site while reducing server load.

**BotBlocker intercepts bad traffic at the earliest stage** - even before WordPress or your theme loads. By running as a must-use plugin (MU-plugin) on early init, it blocks threats before WordPress initializes, drastically reducing server load during attacks.

**Key Firewall Features:**

* Real-time firewall rule updates via the BotBlocker Threat Defense Feed
* Real-time IP Blocklist blocks all requests from the most malicious IPs
* Early-init protection - blocks threats before WordPress loads
* Cloud-based threat intelligence - cross-checks every visitor against global threat databases
* No visitor data collected - only technical request parameters analyzed (GDPR/CCPA-compliant)
* Brute force protection with login attempt limits and multi-layer verification

= 📡 WordPress Security Scanner & Site Protection =

Every attempt to access your site is thoroughly analyzed and filtered. BotBlocker provides comprehensive **site protection** across all entry points:

* **XML-RPC and API Protection** - all endpoints blocked by default. Create access rules for trusted services and add allowed URLs for payment plugins
* **Spam Prevention** - spammers cannot connect to your site. Automatically block IP addresses that exceed spam comment thresholds
* **File Access Protection** - theme and plugin files securely protected from unauthorized access
* **Deep Analysis** - User-Agent, Accept-Language, GeoIP, PTR, DNSBL, cookies, browser fingerprint, AdBlock, Incognito detection
* **Network & Protocol Control** - block obsolete HTTP/1.0 clients and disable IPv6 if not used. Cloudflare-aware protection blocks origin bypass attempts

= 🔒 Login Security & 2FA =

All login attempts pass through multi-layer filtering and CAPTCHA verification:

* **Two-Factor Authentication Support** - 2FA enhanced login security for admin area. Backup codes for recovery access. Universal 2FA app support – works with Google Authenticator, Authy, etc.
* **Multi-layer CAPTCHA Protection** - color buttons, animal images, floating shapes, floating math, Google reCAPTCHA v2/v3, and more. Any internal CAPTCHA can be combined with reCAPTCHA v3 for dual-layer protection
* **Brute Force Protection** - configurable login attempt limits. Failed attempts trigger temporary bans, with escalating penalties for repeated failures
* **Advanced Anti-bot Challenges** - proprietary CAPTCHA designed to be nearly impossible to bypass, even by AI-based anti-CAPTCHA services
* **Intelligent Ban System** - failed CAPTCHA results in configurable ban periods. Repeated failures trigger 24-hour bans
* **Admin Access Simplification** - special mechanism to ease site administrator login while maintaining security
* **XML-RPC Control** - options including complete disabling

= 🛠️ Security Tools =

Comprehensive tools to block attackers and monitor your site in real-time:

* **Advanced Blocking Rules** - block by IP or build rules based on IP Range, Hostname, User Agent, Referrer, PTR record, ASN, country, city, and more
* **IP-PTR-Host Mismatch Detection** - automatically detect and block fake crawlers (e.g., fake Googlebots)
* **Blacklist & Whitelist Management** - instantly allow or block any IP, ASN, range, or User-Agent
* **Live Traffic Monitoring** - see all traffic in real-time: robots, humans, 404 errors, logins/logouts, file requests, and content consumption
* **Server IP Identification** - prevent lockouts by automatically identifying and protecting server IPs
* **Visual Dashboard** - intuitive charts and stats showing blocked attacks, world map of threat origins, top offending IPs/countries
* **Detailed Security Log** - every event logged with IP address, user agent, country, and blocking reason
* **Hide Login URL** *(Premium Addon)*

= ⚡ Performance & Integration =

BotBlocker's robust defense won't slow your site down - in fact, it often improves performance under attack:

* **Lightweight & Fast** - negligible overhead in normal conditions. Reduces database and server load during attacks
* **Built-in Caching** - Redis and Memcached support for high-traffic environments
* **Cache Plugin Compatibility** - automatic `DONOTCACHEPAGE` + `Cache-Control: no-store` on verification pages. Works with WP Super Cache (PHP mode), W3 Total Cache, WP Rocket, LiteSpeed Cache, Hummingbird, and more. Server-level caches (Nginx FastCGI, Varnish, Cloudflare) may need a cookie-based bypass rule - see `docs/CACHE-COMPATIBILITY.md`
* **DDoS Protection Compatibility** - automatic detection of JS-challenges from DDoS-Guard, Stormwall, and similar services. See `docs/DDOS-COMPATIBILITY.md` for advanced configuration
* **Seamless Compatibility** - works with Cloudflare, CDN services, caching plugins, and optimizers
* **Full IPv6 Support** - all security functions work with both IPv4 and IPv6
* **Server Optimization** *(Premium Addon)* - additional performance enhancements for high-traffic sites

= 👤 Easy Setup & User-Friendly Interface =

You don't have to be a security expert to use BotBlocker:

* **Quick Installation Wizard** - step-by-step setup guide for configuration in under 1 minute
* **Intuitive Admin Panel** - organized settings with clear descriptions and tooltips
* **Multilingual** - translated into English, Spanish, German, French, Polish, Russian, Ukrainian, and more
* **No Conflicts** - built following WordPress best practices, tested with recent WP versions
* **Adjustable Logging** - configurable retention periods with time zone awareness and daylight saving support

**Security first - BotBlocker's on guard!**

= 🔥 PRO Version =

Upgrade to PRO for enhanced protection and performance features:

* Real-time cloud threat intelligence checks against global databases
* Zero-day threat detection - behavioral analysis and heuristic rules catch unknown attack patterns before signatures are available
* Hide login URL and protect against targeted attacks
* Security Headers - automatic HSTS, X-Content-Type-Options, X-Frame-Options, Referrer-Policy, Permissions-Policy, and Content-Security-Policy (CSP) configuration
* Early-init (Before WordPress loads) filtering for maximum performance and security
* WordPress Acceleration - frontend optimization
* Speed optimization features for high-traffic sites
* Server optimization features for high-traffic sites
* Priority support and updates
* Access to premium add-ons

== Features ==

= Detection & Analysis =

BotBlocker employs advanced multi-layer detection to identify and block threats:

**Detection Mechanisms:**

* Local and cloud signature databases with real-time updates
* IP reputation and blacklist checks with global threat intelligence
* DNS-based and PTR lookups to detect fake crawlers
* Heuristic and behavioral analysis for suspicious patterns
* Browser fingerprint and feature mismatch detection
* Header and protocol validation
* JavaScript challenge and capability verification
* Multi-layered CAPTCHA verification

**Comprehensive Request Analysis:**

* **Network & IP:** Full IPv4/IPv6 support, blacklist/whitelist, country/GeoIP, ASN, hosting/VPN detection, TOR detection, PTR/DNSBL checks
* **Browser & Client:** User-Agent validation, browser/OS/device detection, fingerprint analysis, headless browser detection, JavaScript/cookie support
* **Headers & Protocol:** Accept-Language, Referer validation, HTTP version control, Cloudflare/proxy detection
* **Advanced Fingerprinting:** Font rendering, WebGL, media devices, touch events, battery API, permissions, timing analysis, plugin verification

= CAPTCHA Modes =

Choose from various CAPTCHA types to protect your site:

* **Single Button** - one-click verification for quick validation
* **Google reCAPTCHA v2** - standard image/checkbox challenge
* **Google reCAPTCHA v3** - invisible background scoring
* **BotBlocker Color CAPTCHA** - select colored buttons challenge
* **BotBlocker Digits CAPTCHA** - floating math challenge
* **BotBlocker Images CAPTCHA** - animal image selection
* **BotBlocker Shapes CAPTCHA** - floating shapes challenge
* **BotBlocker Hold Button** - press and hold to verify, no images or math required
* **Silent Auto-Verify** - no CAPTCHA shown. Real users pass automatically via JS fingerprint checks; bots see "Access denied"
* **Hybrid Mode** - combine any CAPTCHA with reCAPTCHA v3 for dual-layer protection

= Additional Capabilities =

* Early-init & MU plugin support
* Real-time cloud threat checks
* Dynamic and graphical anti-bot challenges
* Automatic logging with adjustable retention
* Session tracking and verification
* No visitor data collected - GDPR/CCPA-compliant (see FAQ for admin notification details)

== Installation ==

1. Download the plugin archive or install directly from your WordPress dashboard
2. Unpack to `wp-content/plugins/botblocker-security/` if uploading manually
3. Activate **BotBlocker Security** in the Plugins menu
4. Go to **BotBlocker** to configure protection settings

The setup wizard will guide you through initial configuration in under 1 minute.

== Frequently Asked Questions ==

= How does BotBlocker Security protect sites from attackers? =

BotBlocker uses multi-layer **site protection**: early-init filtering before WordPress loads, cloud-based threat intelligence, advanced CAPTCHA challenges, deep request analysis, and real-time IP blocking. This comprehensive approach stops bots, scrapers, brute force attacks, and spam before they reach your site.

= How does the BotBlocker WordPress Firewall (WAF) work? =

The **firewall/WAF** operates at the earliest stage - before WordPress loads - analyzing every request's technical fingerprint. It checks User-Agent strings, headers, IP reputation, PTR records, and behavioral patterns to identify and block malicious traffic instantly.

= Does the plugin collect personal data? =

BotBlocker does **not** collect any visitor PII - only technical request parameters (IP, headers, User-Agent) are analyzed locally. Full details are available in `docs/PRIVACY.md` included with the plugin.

= Do I need an external service? =

No. Local protection works out of the box. **Cloud checks (PRO)** are optional and provide enhanced threat intelligence from global databases.

= Will it work with Cloudflare or a CDN? =

Yes. BotBlocker recognizes proxy headers to resolve the real client IP and can block origin bypass attempts. Fully compatible with Cloudflare and other CDN services.

= Does BotBlocker work behind DDoS protection services (DDoS-Guard, Stormwall, etc.)? =

Yes. Since version 1.6.13, BotBlocker automatically detects and handles simple JS-challenge responses from external DDoS protection services. For advanced challenges (Proof-of-Work, interactive CAPTCHA from the DDoS provider), add `/wp-admin/admin-ajax.php` to the challenge bypass list in your DDoS service control panel. See `docs/DDOS-COMPATIBILITY.md` included with the plugin for detailed configuration examples.

= Does BotBlocker work with caching plugins? =

Yes. BotBlocker automatically sets `DONOTCACHEPAGE` and `Cache-Control: no-store` headers on verification/denied pages, preventing PHP-based cache plugins from caching them. WP Super Cache (PHP mode), W3 Total Cache, WP Rocket, LiteSpeed Cache, and Hummingbird work out of the box. For server-level caches (Nginx FastCGI, Varnish) or WP Super Cache Expert (mod_rewrite) mode, add a cookie-based bypass rule - see `docs/CACHE-COMPATIBILITY.md` included with the plugin. The MU-plugin phase also defines `DONOTCACHEPAGE` for visitors without a BotBlocker cookie.

= Can I protect XML-RPC/REST API or login/comments? =

Yes. XML-RPC and REST API endpoints are blocked by default. You can create access rules for trusted services and protect login/comments with multi-layer CAPTCHA verification.

= What CAPTCHA types are available? =

One-click button, color buttons, animal images, floating shapes, floating math, hold button, silent auto-verify, plus Google reCAPTCHA v2/v3. Silent Auto-Verify is the recommended default - real users pass automatically with zero interaction. Any internal CAPTCHA can be combined with reCAPTCHA v3. Our proprietary CAPTCHAs are designed to be nearly impossible to bypass with AI-based anti-CAPTCHA services.

= Does BotBlocker Security support IPv6? =

Yes. Full IPv6 support with all security functions including country blocking, range blocking, city lookup, whois lookup, and all other features. Compatible with IPv4-only, IPv6-only, or dual-stack configurations.

= Will it conflict with other security plugins? =

BotBlocker operates very early in the request lifecycle and usually coexists well with other plugins. Avoid duplicating the exact same CAPTCHA on the same form.

= How do I avoid locking out admins or cron jobs? =

Use **Allowlist** for admin IPs/services and enable "allow server self-IP" so WP-Cron and internal calls pass safely. The plugin automatically identifies server IPs to prevent lockouts.

= What security monitoring features does BotBlocker include? =

**Live Traffic** view shows all visits in real-time: robots, humans, 404 errors, logins/logouts, file requests, heartbeat, and content consumption. **Detailed security logs** track every blocked attack, passed challenge, and admin action with full context (IP, country, user agent, reason).

== Screenshots ==

1. Dashboard overview with visual charts and statistics
2. Wizard setup for quick configuration
3. 2FA setup for admin users
4. Live traffic monitoring and threat log
5. Rules management interface
6. Settings panel with detailed options
7. Speed optimization settings (PRO)
8. Integration settings for ReCAPTCHA, Redis, Memcached and more
9. Addon management interface
10. Health check and diagnostics tool

== Changelog ==
 
= 1.6.17 =
Fix third-party library compatibility issues affecting some hosting environments
Fix minor bugs and plugin incompatibilities with popular WordPress plugins
Improve legacy browser support
Improve Security Headers addon with stricter defaults and additional directives
Improve shared hosting compatibility with enhanced environment detection and fallback logic
Improve statistics and reporting 
Add updated ASN tables
Add cookie diagnostics tool
Add cache compatibility
Update vulnerability signature database
Update translation files

= 1.6.16 =
Add new CAPTCHA mode: Silent Auto-Verify - real users pass automatically with zero interaction, bots see "Access denied"
Add Silent Auto-Verify as the new recommended default in the setup wizard
Add Security Headers addon support (HSTS, CSP, X-Frame-Options, Permissions-Policy - coming soon to the addon marketplace)
Add updated LLM and AI bot whitelist
Add improved ASN validation with extended provider database and stricter hosting/VPN detection
Add improved PTR record verification with multi-resolver fallback for more accurate fake-crawler detection
Add cache compatibility for Swift Performance, Cache Enabler, and Starter Templates caching
Fix CAPTCHA challenge token race condition in extended secure mode (SECURE_MODE_FULL)
Fix GD library fallback - now correctly falls back to Simple Button (mode 0) instead of Color Buttons when GD and reCAPTCHA are both unavailable
Fix CAPTCHA timeout handling for Silent Auto-Verify mode to prevent potential redirect loops
Fix 2FA backup code validation edge case on PHP 8.5
Improve challenge token security with mode-specific transient TTL (1 hour for Silent Auto-Verify)
Improve silent mode retry logic with sessionStorage-based counter surviving page reloads
Improve setup wizard UI - removed duplicate "Recommended" badge from Image Recognition
Update translation files

= 1.6.15 =
Add multisite support
Add LLM whitelist for trusted crawlers and services
Add new security rules to block emerging threats
Add compatibility improvements for WordPress 6.9.4
Fix minor bugs and UI glitches in admin panel
Update translation files

= 1.6.14 =
Add automatic DDoS protection service compatibility (DDoS-Guard, Stormwall, etc.)
Add docs/DDOS-COMPATIBILITY.md documentation
Update cache compatibility layer
Update 2FA libraries
Update translation files

= 1.6.13 =
Improve support for shared hosting environments with dynamic self-IP detection and allowlist management
Improve statistics sammary generation
Update browser detection
Update OS detection
Add privacy readme file
Update translation files

= 1.6.12 =
Add new mode of image CAPTCHA: Image Delivery Mode (for high-traffic sites with caching)
Improve compatibility with Firefox and Safari browsers
Fix minor issues with CAPTCHA rendering in some environments
Fix lagacy mode of Image CAPTCHA
Update translation mode

= 1.6.11 =
Add new captcha type: hold button
Add cache compatibility layer: no-cache headers, DONOTCACHEPAGE, MU-phase cookie check
Add Vary: Cookie header option (Settings → Cookies → Cache Compatibility)
Add cache plugin incompatibility detection and admin alerts
Add docs/CACHE-COMPATIBILITY.md with Nginx, Varnish, Apache, Cloudflare config examples
Add new security rules to block emerging threats
Import data security improvements
Update libraries and dependencies
Improve translation files
Fix minor bugs

= 1.6.10 =
Fix captcha verification issue in some environments
Fix minor UI glitches in admin panel
Add OpenAI, Claude, and Gemini user agent detection

= 1.6.9 =
Add 2FA support for admin users
Add setup wizard improvements
Add PRO features
Fix performance issue in some environments
Improve translation files
Update libraries
Update admin CSS styles

= 1.6.8 =
Fix cookie setting issue in some environments
Fix minor UI glitches in admin panel
Fix translation string issues

= 1.6.7 =
Add extended secure mode
Fix gauge chart rendering issue in some environments
Add missing translation strings
Add PHP 8.5 compatibility improvements

= 1.6.6 =
Fixed issue with cloud status page description not displaying correctly.
Fixed minor UI glitches in admin panel.
Add compatibility improvements for WordPress 6.9
Improved translation files.

= 1.6.5 =
Minor bug fixes and improvements. Enhanced compatibility with WordPress 6.8

= 1.6.4 =
Improved compatibility with various hosting environments. Minor bug fixes and performance optimizations.

= 1.6.3 =
Bug fixes and improvements. Plugin now uses upload directory for better compatibility.

= 1.6.2 =
Major update: migrated to Chart.js for faster statistics rendering. Updated libraries and fixed minor bugs.

= 1.6.1 =
Maintenance release with bug fixes, updated libraries, and license improvements.

= 1.6.0 =
Significant performance improvements and extended detection layers for enhanced security.

== Privacy ==

BotBlocker Security does **not** collect or process personal data of your visitors. All cloud analysis is performed on technical parameters only (IP, headers, User-Agent). No personally identifiable information is collected, stored, or transmitted to any external service.

== Support and Documentation ==

* Product site: [https://botblocker.top/products/](https://botblocker.top/products/)
* Documentation: [https://botblocker.top/docs/](https://botblocker.top/docs/)
* Contact/support: [https://botblocker.top/contacts/](https://botblocker.top/contacts/)
* Community: [https://botblocker.top/community/](https://botblocker.top/community/)

== License ==

This plugin is licensed under the GPLv2 or later. See LICENSE.txt for details.

== Credits & Authors ==

BotBlocker Security is developed and maintained by GLOBUS.studio.

* Concept, architecture & code - Yevhen Leonidov: [https://leonidov.dev/](https://leonidov.dev/)
* Code, code review - Andrii Lukashevych
* Code, translations - Aleksandr Kinakh

**BotBlocker Security - The first line of defense for your WordPress site.**