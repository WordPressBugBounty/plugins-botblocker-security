=== BotBlocker Security - Firewall & Bot Protection ===
Contributors: globusstudio, alukashevych, alexandrkinakh
Tags: security, firewall, anti-spam, captcha, brute force
Requires at least: 5.0
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.6.21
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Stop bots, brute force, spam, and fake crawlers before they reach WordPress. Three-layer firewall, 9 CAPTCHAs, FCrDNS, 2FA. Setup in 60 seconds.

== Description ==

**BotBlocker Security blocks 99% of automated attacks before WordPress even loads.** No bloat, no slowdowns, no monthly fees for core protection.

If your site is hit by login brute force, spam comments, fake Googlebots, content scrapers, or XML-RPC floods, you are not alone: bots generate over 47% of all web traffic. Most security plugins react after WordPress boots, wasting CPU and memory on every bad request. **BotBlocker stops them at the door.**

= Why site owners switch to BotBlocker =

* **Faster than the competition.** Runs on early init through three interception layers, before themes and plugins load. Server load drops during attacks instead of spiking.
* **Smarter CAPTCHA.** 9 modes including Silent Auto-Verify - zero clicks for humans, hard wall for bots. Proprietary CAPTCHAs defeat AI-based solvers that crack reCAPTCHA for $2-3 per 1 000.
* **Honest free version.** Full firewall, all 9 CAPTCHA modes, full 2FA, full logging, full Multisite support. No nag screens, no crippled features.
* **Privacy-first.** No visitor data leaves your server. GDPR and CCPA compliant out of the box.
* **Works with everything.** Cloudflare, WP Rocket, LiteSpeed, WooCommerce, Elementor, multisite, IPv6, PHP 7.4 to 8.5.

= 🛡️ Core Firewall (Free) =

* **Three-Layer Architecture** - intercepts traffic at wp-config.php (before WordPress), MU-plugin phase, and main shield. The first layer blocks known threats without loading WordPress at all, saving 30-100ms and 5-20MB RAM per blocked request.
* **Web Application Firewall (WAF)** with real-time rule updates via the BotBlocker Threat Defense Feed
* **2 899 User-Agent signatures** - largest blacklist among WordPress plugins - covering Scrapy, Selenium, Puppeteer, PhantomJS, curl, wget, Python, Java, Perl, and SQL injection tools
* **Brute force protection** with progressive lockouts - 5 attempts per 15 minutes, escalating bans for repeat offenders
* **Anti-spam** for comments, registration, contact forms - spammers blocked before they connect
* **XML-RPC and REST API** locked down by default with allowlist for trusted services
* **Fake crawler detection** via FCrDNS (dual-direction DNS verification), ASN tokens, and published IP ranges - 95% effective, impossible to spoof without controlling the provider's DNS zone
* **LLM / AI crawler management** - allow or block GPTBot, ChatGPT-User, ClaudeBot, PerplexityBot, Bytespider via CIDR-verified IP ranges. Trusted crawlers verified, impersonators blocked.
* **Country, ASN, IP range, User-Agent, Referer** blocking rules with instant enforcement
* **Cloudflare-aware** real-IP resolution and origin bypass protection
* **Full IPv6 support** - separate tables and logic for IPv4 and IPv6, every feature works with both
* **Live traffic monitor** with attack map, country, ASN, device, browser, and exact block reason for every request
* **Built-in caching** via Redis and Memcached - free, auto-disable on connection failure

= 🔒 Login Security & 2FA (Free) =

* **Two-Factor Authentication** compatible with Google Authenticator, Authy, 1Password, Bitwarden - TOTP standard with 10 backup codes
* **9 CAPTCHA modes**: Silent Auto-Verify, Single Button, Color CAPTCHA, Images CAPTCHA, Shapes CAPTCHA (60fps Canvas), Digits CAPTCHA, Hold Button CAPTCHA, plus Google reCAPTCHA v2 and v3
* **Hybrid Mode** - combine any internal CAPTCHA with reCAPTCHA v3 for two-layer invisible defense
* **Hide login URL** *(PRO)*
* **Configurable lockout durations** with escalation for repeat offenders - failed CAPTCHA triggers short ban, repeated failure triggers 24-hour ban

= 💳 Payment Gateway Bypass (Free) =

Auto-detects 25+ e-commerce platforms (WooCommerce, Easy Digital Downloads, SureCart, MemberPress, Paid Memberships Pro, Give, Dokan, CartFlows, FunnelKit, and more) and 150+ payment providers (Stripe, PayPal, Mollie, Adyen, Braintree, Square, Razorpay, Klarna, Paddle, Authorize.Net, 2Checkout, YooKassa, LiqPay, and more). **Webhooks, IPN callbacks, and payment notifications never get blocked.** Four detection layers ensure zero false positives on payment traffic.

= 📊 Visibility & Control (Free) =

* Visual dashboard with attack map, top offenders, blocked-vs-allowed ratio, world traffic map
* Detailed event log with IP, country, ASN, User-Agent, and exact block reason - 54 unique event codes
* Health Score gauge - 42 parameters across 3 categories, 5 security levels from Critical to Secure
* 3 security presets - Light, Strong, Full - one-click configuration
* Setup Wizard - 8 steps from welcome to test attack, setup in under 5 minutes
* 8 interface languages - English, Deutsch, Español, Français, Polski, Русский, Українська + POT template
* Configurable retention with timezone and DST awareness
* Clean uninstall - drops all 16 tables, removes 40+ options, clears cron hooks. Zero leftover data.

= 🚀 PRO Adds (Premium / Pro / Ultimate) =

* Real-time cloud threat intelligence cross-checked against global databases - 5M+ attack IPs, hundreds of thousands of bot signatures, updated daily
* Zero-day behavioral and heuristic detection - catches unknown attack patterns before signatures exist
* VPN, Tor, proxy, ASN, and hosting reputation checks
* Early Init Mode - filtering before WordPress Core loads, maximum resource savings during attacks
* Hide Login URL addon - custom admin URL, hardened wp-login.php protection
* Security Headers addon - HSTS, CSP, X-Frame-Options, Permissions-Policy, Referrer-Policy, X-Content-Type-Options
* Speed Up WordPress addon - 14 frontend and server optimizations
* Malware Scanner addon - 25 patterns scanning files + 7 database tables, detects webshells, eval injections, base64-obfuscated code hidden in wp_options and post_content
* Priority support - 24-hour response time

Four plans to match your traffic: **Premium** ($12/month, 25k cloud checks), **Pro** ($50/month, 100k cloud checks), **Ultimate** ($100/month, 250k cloud checks + emergency 24h support). Annual billing includes 1 month free. 30-day refund policy. Licensed per domain, billed securely via Freemius.

[Compare plans →](https://botblocker.top/pricing/)

= ⚡ Performance & Compatibility =

* **Zero database queries** for returning visitors - 9 runtime PHP files with SHA-256 integrity signatures, loaded via `include`
* Measured overhead: **+3-15ms** TTFB for cached visitors, **+50-200ms** for first-time PTR lookups, **+2-4MB** memory
* Redis and Memcached support - free, auto-disables gracefully on connection failure
* **Cache plugin compatibility** - automatic `DONOTCACHEPAGE` and `Cache-Control: no-store` on verification pages. Works with WP Super Cache, W3 Total Cache, WP Rocket, LiteSpeed Cache, Hummingbird, WP Fastest Cache, Cache Enabler
* **CDN and WAF compatibility** - Cloudflare, Sucuri, Incapsula, AWS CloudFront, Fastly, KeyCDN, StackPath. Multi-header real-IP resolution (CF-Connecting-IP, X-Forwarded-For, X-Real-IP)
* **DDoS Protection Compatibility** - automatic detection of JS-challenges from DDoS-Guard, Stormwall, Qrator. HMAC-signed AJAX responses, Circuit Breaker with automatic retry and backoff. BotBlocker is the only WordPress plugin that works correctly behind aggressive DDoS protection without manual configuration.
* **Multisite Support** - network activation, per-site data, per-site cleanup. Free on all plans.
* **PHP 7.4 – 8.5** - tested across 7 PHP versions. **WordPress 5.0 – 7.0+**. Linux and Windows.
* GDPR and CCPA compliant - no PII collected, technical parameters only, Legitimate Interest basis (Art. 6(1)(f))

= 🤝 Trusted by =

* 3 000+ active installations
* Translated into 8 languages
* Tested up to WordPress 7.0 and PHP 8.5
* Developed and maintained by GLOBUS.studio

> "Replaced two security plugins and a CAPTCHA plugin with one. Site is faster and the spam stopped overnight." - WordPress.org user

== Installation ==

= 60-second setup =

1. In WordPress admin, go to **Plugins → Add New** and search for "BotBlocker Security"
2. Click **Install Now**, then **Activate**
3. Open **BotBlocker** in the admin menu and follow the Setup Wizard - 8 steps with compatibility test and test attack

Default settings protect most sites immediately. For advanced configuration, three security presets (Light / Strong / Full) give you one-click protection tuned to your needs.

== Frequently Asked Questions ==

= Is BotBlocker Security really free? =

Yes. The free version includes: three-layer firewall, all 9 CAPTCHA modes, FCrDNS bot verification, 2FA with backup codes, anti-spam, brute-force protection, XML-RPC and REST API protection, live traffic monitor, Redis/Memcached, Multisite support, and DDoS compatibility. PRO adds cloud threat intelligence (5M+ attack IPs, hundreds of thousands of bot signatures), Early Init Mode, premium addons (Hide Login, Security Headers, Speed Up, Malware Scanner), and priority support. Premium starts at $12/month.

= Will it slow down my site? =

No. Measured overhead is +3-15ms for verified visitors with zero database queries - all rules load from 9 pre-generated PHP files with SHA-256 integrity. Under attack, server load typically **drops** because bad requests are rejected at the earliest interception layer, before WordPress, PHP, or database code runs. FULL mode saves 30-100ms and 5-20MB RAM per blocked request.

= Does it work with Cloudflare or a CDN? =

Yes. BotBlocker reads proxy headers (CF-Connecting-IP, X-Forwarded-For, X-Real-IP) to find the real client IP and blocks attempts to bypass Cloudflare by hitting your origin directly. Fully compatible with Cloudflare, Sucuri, Incapsula, AWS CloudFront, Fastly, KeyCDN, and StackPath.

= Does it work with WooCommerce and payment gateways? =

Yes. Version 1.6.18 added auto-detection for 25+ e-commerce platforms and 150+ payment providers. Stripe, PayPal, Mollie, Adyen, Razorpay, YooKassa, and other webhooks are automatically recognized and never blocked. Four detection layers (paths, query keys, AJAX actions, signature headers) ensure zero interference with payment processing.

= Does it work with caching plugins? =

Yes. BotBlocker automatically sets `DONOTCACHEPAGE` and `Cache-Control: no-store` on verification pages so PHP-based cache plugins never cache security barriers. Works out of the box with WP Super Cache (PHP mode), W3 Total Cache, WP Rocket, LiteSpeed Cache, Hummingbird, WP Fastest Cache, and Cache Enabler. Server-level caches (Nginx FastCGI, Varnish) need a cookie-based bypass rule - see `docs/CACHE-COMPATIBILITY.md`.

= Does it work behind DDoS-Guard, Stormwall, or similar services? =

Yes. Since version 1.6.13, BotBlocker auto-detects JS-challenges from external DDoS protection services. HMAC-signed AJAX responses let the plugin distinguish its own responses from DDoS-provider challenge pages. Circuit Breaker prevents retry storms (3 failures → 30-second cooldown). BotBlocker is the only WordPress security plugin that works correctly behind aggressive DDoS protection without manual whitelisting. See `docs/DDOS-COMPATIBILITY.md`.

= Will it lock me out? =

No. BotBlocker auto-detects your server IP during setup and lets you allowlist admin IPs and trusted services. WP-Cron and internal WordPress calls always pass. If you ever get locked out, a hashed Secret URL (generated in the admin panel and sent to the admin email) provides emergency access - no FTP required.

= Does it collect visitor data? =

No. Only technical request parameters (IP, headers, User-Agent) are analyzed locally on your server. Nothing personal is stored or sent anywhere. GDPR-compliant under Legitimate Interest (Art. 6(1)(f)). CCPA compliant - no PII collection, no data sale. Full details in `docs/PRIVACY.md`.

= Does it support IPv6? =

Yes. Every feature works with IPv4, IPv6, or dual-stack setups. Separate database tables and logic for each protocol family.

= Does it support multisite? =

Yes, since version 1.6.15. Network activation, per-site data isolation, per-site settings, and per-site cleanup on uninstall. Free on all plans.

= Will it conflict with Wordfence, Sucuri, or other security plugins? =

BotBlocker is designed to coexist. It operates very early in the request lifecycle and typically works alongside other plugins. The only thing to avoid is enabling the same CAPTCHA twice on the same form. Most users replace their previous security stack entirely.

= Which CAPTCHA should I choose? =

**Silent Auto-Verify** is the recommended default. Real users pass with zero clicks via JavaScript fingerprint checks behind the scenes - they see nothing. Bots see "Access denied." For login pages, combine Silent Mode with reCAPTCHA v3 in Hybrid Mode for two-layer invisible defense. Shapes CAPTCHA (60fps Canvas with moving geometric figures) is the strongest against AI-based CAPTCHA solvers - it requires real-time computer vision, making it roughly 100x more expensive to crack than standard reCAPTCHA.

= How does BotBlocker verify search engine bots? =

Through **FCrDNS** (Forward-confirmed Reverse DNS) - the same method used by Cloudflare Bot Management, DataDome, and Akamai Bot Manager. Googlebot is verified via PTR (.googlebot.com) + ASN (15169). YandexBot uses triple verification (PTR + ASN 13238 + IP CIDR). Facebook gets dual verification (PTR + ASN 32934). 95% effective against fake crawlers - you cannot spoof FCrDNS without controlling the provider's DNS zone.

= Can I block AI crawlers (ChatGPT, Claude, Perplexity)? =

Yes. GPTBot, ChatGPT-User, OAI-SearchBot, ClaudeBot, Claude-SearchBot, and PerplexityBot are verified via CIDR ranges synced from the cloud API. You can allow or block each provider independently. Bytespider (ByteDance) is verified via PTR (.bytedance.com). Trusted AI crawlers pass; impersonators are blocked.

= What is the Health Score? =

A 42-parameter security assessment displayed as a visual gauge (0-100). Five levels: Critical (<25), Weak (25-49), Moderate (50-69), Strong (70-84), Secure (≥85). Three categories weighted: core protection (75%), cloud extended (25%), neutral indicators. The score updates in real-time as you change settings - a built-in guide to improving your site's security posture.

= Does the PRO version include a trial? =

No traditional trial. Instead, the free version includes the full firewall, all 9 CAPTCHA modes, FCrDNS verification, 2FA, Multisite, Redis/Memcached, and live traffic monitoring - enough to protect most sites permanently. A limited-time Premium promo (14 days, no credit card) is available inside the plugin to try cloud features. PRO plans start at $12/month with a 30-day refund policy.

= What happens when I delete the plugin? =

Clean uninstall: all 16 database tables are dropped, 40+ WordPress options deleted, 22+ transients cleared, 12 cron hooks removed, MU-plugin files cleaned, and the uploads/botblocker/ directory deleted. On multisite, per-site cleanup runs in batches of 50. Zero leftover data - no orphaned rows, no stale cron jobs.

== Screenshots ==

1. Dashboard with attack map, blocked-vs-allowed chart, and real-time statistics
2. 8-step Setup Wizard - from welcome to test attack in under 5 minutes
3. Two-Factor Authentication setup with backup codes
4. Live traffic monitor with full request context - IP, country, ASN, device, browser, block reason
5. Rules manager - IP, IP range, ASN, country, User-Agent, Referer, hostname
6. Settings panel with CAPTCHA mode selector, security presets, and detailed options
7. Speed optimization settings (PRO)
8. Integration settings for reCAPTCHA, Redis, Memcached and more
9. Addon marketplace - one-click install for Security Headers, Hide Login, Speed Up, Malware Scanner
10. Health Score gauge - 42 parameters, 5 security levels, real-time scoring

== Changelog ==
 
= 1.6.21 =
Add LLM/AI Crawler Whitelist system with dedicated database, admin management UI, and cloud-synced coverage for OpenAI, Claude, Gemini, Perplexity, and other AI crawlers
Add Daily Summary Statistics pipeline with incremental aggregation for fast multi-day analytics
Add Geo-Blocking - block entire countries from admin dashboard with import/export support
Add DDoS Resilience Mode - HMAC-signed verification responses prevent forged challenge bypass
Add Session Token Verification - cookie-less browser fingerprint for restricted hosting environments
Add Data File Tampering Detection - automatic recovery from corrupted runtime data files
Add Addon Traffic Decision Pipeline - 6 interception points for addons to control visitor flow at each stage
Add Centralized Alert System - admin alerts for cloud connection, ASN database, file integrity, and cache plugin conflicts
Add RKN (Roskomnadzor) IP Blocking - cloud-synced Russian government blocklist with CIDR matching, scheduled auto-update, self-healing, and manual refresh from admin tools
Improve verified crawler coverage - WhatsApp, Bluesky (Cardyb), BingPreview with updated Yandex CIDRs and ASN tokens
Improve multisite support - per-site early init bootstrap generation, addon lifecycle fixes across network sites
Improve compatibility - WordPress Plugin Check compliance, nonce_user_logged_out guard for third-party plugin conflicts, WP-Cron and core update screen bypass

= 1.6.20 =
Add WordPress 7.0 compatibility and Connections support for BotBlocker Security
Fix WordPress 7.0 REST OPTIONS permission checks from wp-admin pages
Add ASN allow, block, dark, and gray rule handling with safer crawler verification
Improve anti-detect checks for critical browser fingerprint mismatch combinations
Fix Geo country rule sanitization and Cloud API contact email validation
Improve plugin update notices when remote changelog data is unavailable

= 1.6.19 =
Add new security rules to block emerging threats with updated ASN coverage
Update coverage for new bots and crawlers
Add coverage for 20+ payment providers in the Payment Gateway Bypass whitelist
Add HEAD request support for security checks and blocking
Fix minor bugs and UI glitches in admin panel
Fix language selection issue
Fix setup wizard issue with some hosting environments
Update translation files

= 1.6.18 =
Add new ASN database with auto-update
Add Payment Gateway Bypass: dedicated whitelist for legitimate payment callbacks (webhooks, IPN, postbacks) so checkout notifications are never blocked
Add auto-detection for 25+ e-commerce platforms (WooCommerce, EDD, SureCart, MemberPress, RCP, PMPro, Give, Dokan, WCFM, CartFlows, FunnelKit, etc.)
Add built-in coverage for 30+ payment providers: Stripe, PayPal, Mollie, Adyen, Braintree, Square, Razorpay, CloudPayments, WayForPay, LiqPay, Fondy, PayU, Klarna, Paystack, Flutterwave, GoCardless, Paddle, Authorize.Net, 2Checkout and more
Add new "Payment Gateways" tab in Advanced Settings

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
* Pricing and PRO plans: [https://botblocker.top/pricing/](https://botblocker.top/pricing/)
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
