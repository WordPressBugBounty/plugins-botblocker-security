=== BotBlocker Security - Complete security platform ===
Contributors: globusstudio, alukashevych, alexandrkinakh
Tags: security, firewall, anti-spam, captcha, brute force
Requires at least: 5.1
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.7.4
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Complete security platform for everyday protection against bots, brute-force attacks, viruses, spam, and fake crawlers. Includes a firewall, 9 CAPTCHA options, FCrDNS verification, 2FA, and 200+ security tools - set up in 60 seconds.

== Description ==
 
**BotBlocker Security is a complete security platform that blocks 99% of attacks before WordPress loads.** Firewall, bot detection, rate limiting, CAPTCHA, 2FA, analytics, notifications, tools - one plugin replaces your entire security stack: no need for separate firewall, CAPTCHA, 2FA, and anti-spam plugins. No bloat, no monthly fees for core protection.

If your site is hit by login brute force, spam comments, fake Googlebots, content scrapers, or XML-RPC floods, you are not alone: bots generate over 47% of all web traffic. Most security plugins react after WordPress boots, wasting CPU and memory on every bad request. **BotBlocker stops them at the door - then gives you the analytics, alerts, and tools to understand exactly what it blocked and why.**

Because BotBlocker intercepts traffic before themes and plugins run, it also shields vulnerabilities in your entire plugin and theme stack. Zero-days in contact form plugins, unpatched slider code, outdated e-commerce extensions - attackers never reach the vulnerable code. **BotBlocker is your first and last line of defense, every day.**

= Why site owners switch to BotBlocker =

* **Faster than the competition.** Runs on early init through three interception layers, before themes and plugins load. Server load drops during attacks instead of spiking. Zero database queries for returning visitors - 11 pre-generated PHP files with SHA-256 integrity, loaded via native `include`.
* **One plugin, full security stack.** Firewall, CAPTCHA, 2FA, anti-spam, rate limiting, bot detection - BotBlocker covers virtually every security need a WordPress site has. Most users uninstall 2-3 separate plugins after switching. PRO adds malware scanning, security headers, speed optimization, and behavioral analysis - still one plugin.
* **Smarter bot detection.** 2,899 User-Agent signatures - largest blacklist among WordPress plugins - plus 2,800+ additional bot signatures. 13 header checks including AdBlock detection, incognito mode detection, Canvas/WebGL fingerprinting, and device API verification. Anti-detect scoring with 9 weighted signals catches headless Chrome, Puppeteer, Selenium, and PhantomJS even when they spoof user-agent strings.
* **Smarter CAPTCHA.** 9 modes including Silent Auto-Verify - zero clicks for humans, hard wall for bots. Proprietary CAPTCHAs defeat AI-based solvers that crack reCAPTCHA for $2-3 per 1,000.
* **Honest free version.** Full firewall, all 9 CAPTCHA modes, full 2FA, full logging, full Multisite, Redis/Memcached, shortcodes, command palette, Telegram alerts, visual analytics, 15 diagnostic tools. No nag screens, no crippled features.
* **Privacy-first.** No visitor data leaves your server. GDPR and CCPA compliant out of the box.
* **Works with everything.** Cloudflare, WP Rocket, LiteSpeed, WooCommerce, Elementor, multisite, IPv6, PHP 7.4 to 8.5.

= 🛡️ Core Firewall (Free) =

* **Three-Layer Architecture** - intercepts traffic at wp-config.php (before WordPress), MU-plugin phase, and main shield. The first layer blocks known threats before WordPress, plugins, or themes even load - saving 30-100ms and 5-20MB RAM per blocked request. This also means BotBlocker shields you from vulnerabilities in other plugins and themes: attackers exploiting a zero-day in a contact form or slider plugin never reach the vulnerable code.
* **Web Application Firewall (WAF)** with real-time rule updates via the BotBlocker Threat Defense Feed
* **2,899 User-Agent signatures** - largest blacklist among WordPress plugins - covering Scrapy, Selenium, Puppeteer, PhantomJS, curl, wget, Python, Java, Perl, and SQL injection tools. Plus 2,800+ additional bot signatures.
* **Brute force protection** with progressive lockouts - 5 attempts per 15 minutes, escalating bans for repeat offenders
* **Rate Limiting** - sliding window velocity tracking per IP. 30 requests/minute triggers CAPTCHA challenge; 50 requests/minute blocks IP for 10 minutes. Subnet-level aggregation catches distributed attacks across /24 IPv4 or /64 IPv6 ranges. Floor protection prevents false positives at low thresholds.
* **Anti-spam** for comments, registration, contact forms - spammers blocked before they connect
* **XML-RPC and REST API** locked down by default with allowlist for trusted services
* **HTTP method-level hardening** - CONNECT and TRACE blocked (XST attack prevention). OPTIONS handled for CORS preflight. Explicit method allowlist.
* **Fake crawler detection** via FCrDNS (dual-direction DNS verification), ASN tokens, and published IP ranges - 95% effective, impossible to spoof without controlling the provider's DNS zone. Multi-resolver DNS fallback.
* **LLM / AI crawler management** - allow or block GPTBot, ChatGPT-User, ClaudeBot, PerplexityBot, Bytespider via CIDR-verified IP ranges. 1,435 LLM source ranges synced from cloud. Trusted crawlers verified, impersonators blocked.
* **Country, ASN, IP range, User-Agent, Referer** blocking rules with instant enforcement. ASN database: 3.6M verified source records. Geo-blocking with visual country selector and import/export.
* **Cloudflare-aware** real-IP resolution and origin bypass protection
* **TLS fingerprinting (JA3/JA4)** - detects bots by TLS handshake signature, cross-validates against User-Agent. 189 JA3/JA4 signatures. Opt-in, requires server module. See `docs/TLS-FINGERPRINTING.md`
* **RKN (Roskomnadzor) blocking** - 852 CIDR ranges, cloud-synced, auto-updated with self-healing tamper recovery
* **Full IPv6 support** - separate tables and logic for IPv4 and IPv6, every feature works with both
* **Live traffic monitor** with attack map, country, ASN, device, browser, and exact block reason for every request
* **Built-in caching** via Redis and Memcached - free, auto-disable on connection failure

= 🔍 Bot Detection & Anti-Detect (Free) =

**13 HTTP header checks** run on every visitor, catching bots that other plugins miss:

* **Empty User-Agent** / **UA Anomalies** / **Empty Accept-Language**
* **PTR/DNS Mismatch** - forward/reverse DNS mismatch, impossible to fake
* **Geo vs Language Mismatch** - visitor claiming one language from a different country
* **Incognito/Private Mode** detection
* **AdBlock/uBlock** detection in visitor's browser
* **Canvas & WebGL Fingerprinting** - JS consistency verification
* **WhatsApp Preview** / **OPTIONS Preflight** - legitimate traffic whitelisted

**Anti-Detect Challenge Page** activates when header checks raise suspicion - silent browser fingerprinting before any CAPTCHA appears:

* **9 weighted detection signals:** navigator mismatch (×2), fake plugins (×3), font rendering mismatch (×2), WebGL mismatch (×2), Chromium-specific properties (×3), JS execution jitter, touch event mismatch (×2), language mismatch (×3), incognito detection (×2)
* **Critical signal combinations trigger immediate block** - e.g. navigator mismatch + fake plugins together
* Cookie capability test + sessionStorage retry counter
* HMAC-signed responses resist DDoS provider interference
* Circuit Breaker: 3 failures → 30-second cooldown → auto-retry
* CAPTCHA appears only if auto-verification fails - real users rarely see it

= 🎯 CAPTCHA System - All 9 Modes Free =

| # | Mode | User Experience | Best For |
|---|------|-----------------|----------|
| **8** | **Silent Auto-Verify** (Recommended) | Zero clicks. JS fingerprint check passes silently. | Default. Maximum UX. |
| 0 | Simple Button | Single "I am not a robot" button | Minimal friction |
| 1 | Color Buttons | Click the matching color button | Visual verification |
| 2 | Image CAPTCHA | Click the image matching the category. 5 packs: Eagle, Horse, Raccoon, Dog, Cat | Visual recognition |
| 3 | reCAPTCHA v2 + Button | Google checkbox + BotBlocker button | Google ecosystem users |
| 4 | reCAPTCHA v2 | Standard Google reCAPTCHA | Google ecosystem only |
| 5 | Dynamic Shapes | 60fps Canvas with moving geometric figures. 5 shapes × 5 colors = 25 combinations. | Maximum anti-AI protection |
| 6 | Dynamic Digits | Animated math equation | Math verification |
| 7 | Hold Button | Hold and release in the green zone | Physical interaction proof |

* **Hybrid Mode** - combine any internal CAPTCHA with invisible reCAPTCHA v3 for two-layer defense
* **DDoS Resilience Mode** - HMAC-signed verification responses prevent forged challenge bypass
* **Session Token Verification** - cookie-less fingerprint for restricted hosting (no CAPTCHA loops for VPN/proxy users)
* **CAPTCHA Diagnostics** - failure reason codes (token decrypt, transient missing, hash mismatch, mode mismatch) for precise troubleshooting
* **Image Delivery Modes:** Inline Base64 (single request, recommended) or Separate Requests (cache-friendly)

= 🔒 Login Security & 2FA (Free) =

* **Two-Factor Authentication** compatible with Google Authenticator, Authy, 1Password, Bitwarden - TOTP standard with 10 backup codes
* **9 CAPTCHA modes** on login, registration, lost password, and custom forms
* **Hybrid Mode** - combine any internal CAPTCHA with reCAPTCHA v3 for two-layer invisible defense on login
* **Hide login URL** *(PRO)*
* **Configurable lockout durations** with escalation for repeat offenders - failed CAPTCHA triggers short ban, repeated failure triggers 24-hour ban

= 💳 Payment Gateway Bypass (Free) =

Auto-detects 25+ e-commerce platforms (WooCommerce, Easy Digital Downloads, SureCart, MemberPress, Paid Memberships Pro, Give, Dokan, CartFlows, FunnelKit, and more) and 150+ payment providers (Stripe, PayPal, Mollie, Adyen, Braintree, Square, Razorpay, Klarna, Paddle, Authorize.Net, 2Checkout, YooKassa, LiqPay, and more). Also auto-detects webhooks from form plugins (Contact Form 7, WPForms, Gravity Forms, Ninja Forms, Formidable Forms, Forminator, Fluent Forms).

**Four detection layers - 198 path patterns, 227 query keys, 360 action names, 114 signature headers - ensure zero false positives on payment traffic.** Webhooks, IPN callbacks, and payment notifications never get blocked.

= 📊 Visibility & Control (Free) =

* **Visual dashboard with KPI cards** - Requests today, Blocked today, Search engines passed, Protection score (0-100%). Hourly bar chart, distribution donut charts (Hosting, Device, Browser, OS)
* **Dashboard social proof card** - live WordPress.org rating (1-5 stars with half-star), active installs count, number of ratings, link to WP.org (cached 12 hours)
* **Inline IP rule widget** - add IP allow/block rules directly from dashboard via AJAX, no page reload. Bulk IP import with 4 one-click buttons (IPv4/IPv6 whitelist/blacklist).
* **Protection Status Checklist** - individual enable/disable toggles for all features with "Enable remaining" bulk action
* **Health Score gauge** - 44 parameters across 3 categories, 5 security levels from Critical (<25) to Secure (≥85). Real-time score updates as you change settings.
* **3 security presets** - Light, Strong, Full - one-click configuration. Plus Default for new installs.
* **Setup Wizard** - 8 steps: welcome → protection level → compatibility check → exclusions → CAPTCHA → init mode → cache → finish with summary. Under 5 minutes.
* **⌘K Command Palette** - press Ctrl+K / ⌘K on any admin page to instantly search and navigate every setting, tab, and action. 30+ quick-action shortcuts ("Block an IP address", "Set up 2FA", "Run malware scan", "Clear visitor cookies"). Near-zero-click configuration.
* **Interactive analytics** - 8 KPI metrics, donut charts (Hosting, Device, Browser, OS), traffic line chart with time range, world map with country-level visualization, top IPs and top countries ranking tables
* **55 diagnostic event codes** across 12 log categories - exact block reason for every single decision. 12 categories independently toggleable.
* **Detailed event log** with IP, country, ASN, User-Agent, browser type, OS type, device type, and exact block reason
* **17 interface languages** - English, Deutsch, Español, Français, Polski, Русский, Українська, العربية, עברית, Italiano, 日本語, 한국어, Nederlands, Português, Svenska, Türkçe, 中文 + POT template
* **Admin page header bar** - "Blocked today: X · Total: Y" on every admin page without opening dashboard
* **Admin bar WordPress toolbar node** - protection status + Dashboard + Settings links
* **Configurable retention** with timezone and DST awareness. Daily Summary with incremental aggregation for fast multi-day analytics.
* **X-Robots-Tag control** - user-configurable SEO directives (noindex, nofollow, noarchive, nosnippet, noimageindex, notranslate, unavailable_after) on blocked/challenge pages - prevents search engines from indexing security barriers
* **Clean uninstall** - drops all 17 tables, removes 40+ options, clears 22+ transients, removes 12 cron hooks, deletes MU-plugin files and uploads/botblocker/ directory. Uninstall feedback dialog collects departure reason. Zero leftover data.

= 📬 Notifications & Alerts (Free) =

* **Email reports** - daily / twice-weekly / monthly security summaries with key metrics. Critical server load warnings and Cloud API expiry alerts.
* **Telegram Bot integration (add-on)** - weekly security summaries via @BotFather-connected bot: total requests, allowed, blocked, suspicious, search bots, fake bots, timezone, protection mode, security score, block rate. Connection test button for setup verification. Configured in Add-ons → Telegram Notifications.
* **Admin Dashboard Alerts (8 types)** - cloud connection lost, missing runtime files, cloud API expired, cloud API hits exhausted, ASN database update failed, addon update failed, addon incompatible, cache plugin incompatibility

= 🧩 Shortcodes & Developer Tools (Free) =

**20 shortcodes** for frontend dashboards, client portals, and status pages:

| Shortcode | What It Displays |
|-----------|-----------------|
| `[bbcs_counters_grid]` | Hits + blocked + search engine counters |
| `[bbcs_blocked_today]` / `[bbcs_blocked_total]` | Block counters |
| `[bbcs_health_gauge]` | Visual health score gauge chart |
| `[botblocker_generateSiteHealthList]` | Security recommendations checklist |
| `[bbcs_health_full]` | Full health assessment grid |
| `[bbcs_top_ips]` / `[bbcs_top_countries]` | Top IPs and countries (configurable count + days) |
| `[bbcs_top_devices]` / `[bbcs_top_browsers]` | Top devices and browsers |
| `[bbcs_latest_hits]` | Latest visitor hits table |
| `[botblocker_rules_stats]` | IPv4/IPv6 rules + paths + bots statistics |
| `[bbcs_database_update]` / `[bbcs_database_total]` | Cloud IP database stats |
| `[bbcs_system_status]` | Server info: OS, PHP, DB, WP versions |
| `[bbcs_botblocker_news]` | BotBlocker news feed |
| `[bbcs_lang_options]` | Language selector dropdown |
| `[bbcs_plugins_themes]` | Installed plugins and themes list |
| `[bbcs_cron_tasks]` | Scheduled cron tasks widget |
| `[bbcs_recommendations]` | Security improvement recommendations |

* **WP Connectors (WordPress 6.7+)** - BotBlocker registers as a native WordPress Connector on the Connections admin page
* **Custom verification endpoint** (`bbcs-verify`) - decoupled from WordPress permalink structure
* **WordPress Admin → Tools → Site Health → Info** integration
* **Plugin update changelog injection** - changelog displayed directly in the WordPress plugin update screen
* **Floating support widget** on all admin pages - submit support tickets from dashboard without leaving site

= 🔧 Tools & Diagnostics (Free) =

**15 maintenance and diagnostic actions** built into the admin panel:

| Tool | Purpose |
|------|---------|
| **Export Data & Settings** | Full JSON backup of settings, rules, IP lists, configuration |
| **Import Data & Settings** | Restore from JSON backup |
| **Reinstall Database** | Factory-reset all BotBlocker tables |
| **Repair & Optimize DB** | WordPress database repair and optimization |
| **Clear All Visitor Data** | Purge hits, counters, and statistics |
| **Clear Transients** | Remove expired WordPress transients |
| **Update ASN Database** | Download latest 3.6M-record ASN geolocation database |
| **Update RU-Gov List** | Download latest 852-range RKN CIDR blocklist |
| **Sync LLM Providers** | Refresh 1,435 AI crawler IP ranges from cloud |
| **Clear Visitor Cookies** | Reset all BotBlocker cookies - forces re-verification |
| **Reset URL Rewrite Rules** | Fix 404 errors after permalink changes |
| **Clear Object Cache** | Flush WordPress internal + Redis/Memcached caches |
| **Site Health** | Open WordPress Site Health diagnostic |
| **Clear Debug Log** / **Download Debug Log** | Purge or download wp-content/debug.log for support |

= 🚀 PRO Adds (Premium / Pro / Ultimate) =

* **Cloud Threat Intelligence** - 5M+ attack IPs cross-checked against global databases. Zero-day behavioral and heuristic detection catches unknown attack patterns before signatures exist. VPN, Tor, proxy, ASN, and hosting reputation checks.
* **Early Init Mode** - filtering before WordPress Core loads. Boot code in wp-config.php blocks threats at the earliest possible layer. Up to 30-100ms and 5-20MB RAM saved per blocked request. Multisite-aware, auto-disables if Cloud API inactive. Self-healing runtime files.
* **Hide Login URL** - custom admin URL slug. Four behaviors when default URL is accessed: 403 message, 404 page, home redirect, custom URL redirect. Reserved slug detection prevents page conflicts.
* **Security Headers** - MU-plugin deployment for headers before plugins load. HSTS, CSP with custom domain whitelist, X-Frame-Options, X-Content-Type-Options, Permissions-Policy. Auto-detection of reCAPTCHA Google domains + BotBlocker API domains in CSP.
* **Speed Up WordPress** - 14 frontend and server optimizations: disable emojis, remove jQuery Migrate, disable dashicons, remove global styles, disable embeds, remove RSS feeds, disable XML-RPC, hide WP version, remove shortlinks, disable self-pingbacks, and more.
* **Malware Scanner** - 25+ detection patterns: eval() chains, base64 obfuscation, injected scripts, webshell markers. Deobfuscation preprocessor. Scans files (core, plugins, themes, uploads, custom paths) + database (posts, wp_options, users, cron). Truth Source comparator for integrity verification. Scheduled background scanning (hourly to every 5 days). Severity levels: Critical to Info. Confidence: Review to Confirmed.
* **HTTPS Protocol** - HTTP→HTTPS 301 redirect. Mixed content auto-fix via output buffering. SSL detection behind 7 proxy headers (Cloudflare, load balancers).
* **Cookie Alert** - lightweight first-party cookie consent banner. Custom text, policy link, button label, position (top/bottom), theme (dark/light), custom CSS. WP accessibility: aria-label, role attributes. 1-year cookie lifetime.
* **Behavioral Analysis Engine** - 3-layer AI bot detection. Free layer: RPM velocity + quadratic subnet pressure. Pro layer: multi-signal scoring (velocity, URI diversity, session depth, timing regularity, referer consistency). Reputation layer: IP/subnet reputation tracking with lazy decay. Configurable thresholds with 3 quick presets.
* **Cron Jobs** - advanced WordPress cron management, monitoring, and debugging
* **Truth Source** - file integrity verification against known-good WordPress core, plugin, and theme references. Detect modified, added, missing files. Per-file ignore (permanent or until file changes). Wildcard pattern ignore. Scheduled background scanning.
* **XMLRPC Tunnel** - secure XML-RPC access through IP-allowlisted endpoints. IPv4/IPv6/CIDR/wildcard entry formats. Multi-header real-IP resolution. Auto-block non-allowlisted access.
* **Priority support** - 24-hour response time

Four plans to match your traffic: **Premium** ($12/month, 25k cloud checks), **Pro** ($50/month, 100k cloud checks), **Ultimate** ($100/month, 250k cloud checks + emergency 24h support). Annual billing includes 1 month free. 30-day refund policy. Licensed per domain, billed securely via Freemius.

[Compare plans →](https://botblocker.top/pricing/)

= ⚡ Performance & Compatibility =

* **Zero database queries** for returning visitors - 11 runtime PHP files with SHA-256 integrity signatures, loaded via `include`
* Measured overhead: **+3-15ms** TTFB for cached visitors, **+50-200ms** for first-time PTR lookups, **+2-4MB** memory
* **100+ configurable settings** without affecting performance
* Redis and Memcached support - free, auto-disables gracefully on connection failure
* **Cache plugin compatibility** - automatic `DONOTCACHEPAGE` and multiple cache-bypass headers (`Cache-Control: no-store`, `X-LiteSpeed-Cache-Control: no-cache`, `X-Accel-Expires: 0`, `CDN-Cache-Control: no-store, private`, `Surrogate-Control: no-store, max-age=0`) covering PHP caches, Nginx reverse proxy, CDN edge, and surrogate caches in one pass. Works with WP Super Cache, W3 Total Cache, WP Rocket, LiteSpeed Cache, Hummingbird, WP Fastest Cache, Cache Enabler, Swift Performance.
* **CDN and WAF compatibility** - Cloudflare, Sucuri, Incapsula, AWS CloudFront, Fastly, KeyCDN, StackPath. Multi-header real-IP resolution (CF-Connecting-IP, X-Forwarded-For, X-Real-IP)
* **DDoS Protection Compatibility** - automatic detection of JS-challenges from DDoS-Guard, Stormwall, Qrator. HMAC-signed AJAX responses, Circuit Breaker with automatic retry and backoff. BotBlocker is the only WordPress plugin that works correctly behind aggressive DDoS protection without manual configuration.
* **Fatal Error Hive Mode** - even during PHP fatal errors from other plugins or themes, BotBlocker renders an emergency page instead of a white screen, preserving the security barrier.
* **Floating support widget** on all admin pages - submit support tickets directly from the dashboard without leaving your site.
* **Multisite Support** - network activation, per-site data, per-site settings, per-site cleanup. Free on all plans.
* **PHP 7.4 – 8.5** - tested across 7 PHP versions. **WordPress 5.1 – 7.0+**. Linux and Windows.
* **50+ OS versions detected** - Windows 95–11, macOS, Android 10–15 per-version, iOS, Chrome OS, Linux distros (Ubuntu, Fedora, Debian, Arch, etc.), HarmonyOS, Fire OS, KaiOS, BlackBerry, Tizen, FreeBSD, gaming consoles
* **50+ browsers identified** - Opera, Edge, Vivaldi, Brave, Samsung Internet, UC Browser, Yandex Browser, DuckDuckGo, Tor Browser, Headless Chrome, Lynx/ELinks, and more
* GDPR and CCPA compliant - no PII collected, technical parameters only, Legitimate Interest basis (Art. 6(1)(f))

= 🤝 Trusted by =

* 3,000+ active installations
* Translated into 17 languages
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

Yes. The free version includes: three-layer firewall, all 9 CAPTCHA modes, anti-detect scoring, FCrDNS bot verification, rate limiting, 2FA with backup codes, anti-spam, brute-force protection, XML-RPC and REST API protection, live traffic monitor, Telegram alerts, 20 shortcodes, 15 diagnostic tools, command palette, Redis/Memcached, Multisite support, and DDoS compatibility. PRO adds cloud threat intelligence (5M+ attack IPs), Early Init Mode, premium addons (Hide Login, Security Headers, Speed Up, Malware Scanner, Behavioral Analysis, and more), and priority support. Premium starts at $12/month.

= Will it slow down my site? =

No. Measured overhead is +3-15ms for verified visitors with zero database queries - all rules load from 11 pre-generated PHP files with SHA-256 integrity. Under attack, server load typically **drops** because bad requests are rejected at the earliest interception layer, before WordPress, PHP, or database code runs. FULL mode saves 30-100ms and 5-20MB RAM per blocked request.

= Does it work with Cloudflare or a CDN? =

Yes. BotBlocker reads proxy headers (CF-Connecting-IP, X-Forwarded-For, X-Real-IP) to find the real client IP and blocks attempts to bypass Cloudflare by hitting your origin directly. Fully compatible with Cloudflare, Sucuri, Incapsula, AWS CloudFront, Fastly, KeyCDN, and StackPath.

Cloudflare Business+ users can also enable TLS fingerprinting (JA3/JA4) for bot detection - see `docs/TLS-FINGERPRINTING.md`.

= Does it work with WooCommerce and payment gateways? =

Yes. Auto-detection for 25+ e-commerce platforms and 150+ payment providers. Stripe, PayPal, Mollie, Adyen, Razorpay, YooKassa, and other webhooks are automatically recognized and never blocked. Four detection layers (198 path patterns, 227 query keys, 360 action names, 114 signature headers) ensure zero interference with payment processing.

= Does it work with caching plugins? =

Yes. BotBlocker automatically sets `DONOTCACHEPAGE` and multiple cache-bypass headers (`Cache-Control: no-store`, `X-LiteSpeed-Cache-Control`, `X-Accel-Expires`, `CDN-Cache-Control`, `Surrogate-Control`) on verification pages so PHP-based caches, Nginx reverse proxies, CDN edges, and surrogate caches never store security barriers. Works out of the box with WP Super Cache (PHP mode), W3 Total Cache, WP Rocket, LiteSpeed Cache, Hummingbird, WP Fastest Cache, Cache Enabler, and Swift Performance. Server-level caches (Nginx FastCGI, Varnish) need a cookie-based bypass rule - see `docs/CACHE-COMPATIBILITY.md`.

= Does it work behind DDoS-Guard, Stormwall, or similar services? =

Yes. Since version 1.6.13, BotBlocker auto-detects JS-challenges from external DDoS protection services. HMAC-signed AJAX responses let the plugin distinguish its own responses from DDoS-provider challenge pages. Circuit Breaker prevents retry storms (3 failures → 30-second cooldown). BotBlocker is the only WordPress security plugin that works correctly behind aggressive DDoS protection without manual whitelisting. See `docs/DDOS-COMPATIBILITY.md`.

= Will it lock me out? =

No. BotBlocker auto-detects your server IP during setup and lets you allowlist admin IPs and trusted services. WP-Cron and internal WordPress calls always pass. If you ever get locked out, a hashed Secret URL (generated in the admin panel and sent to the admin email) provides emergency access - no FTP required.

= Does it collect visitor data? =

No. Only technical request parameters (IP, headers, User-Agent) are analyzed locally on your server. No PII is stored or sent anywhere. GDPR-compliant under Legitimate Interest (Art. 6(1)(f)). CCPA compliant - no PII collection, no data sale. Full details in `docs/PRIVACY.md`.

= Does it support IPv6? =

Yes. Every feature works with IPv4, IPv6, or dual-stack setups. Separate database tables and logic for each protocol family.

= Does it support multisite? =

Yes, since version 1.6.15. Network activation, per-site data isolation, per-site settings, and per-site cleanup on uninstall. Free on all plans.

= Will it conflict with Wordfence, Sucuri, or other security plugins? =

BotBlocker is designed to coexist. It operates very early in the request lifecycle and typically works alongside other plugins. The only thing to avoid is enabling the same CAPTCHA twice on the same form. Most users replace their previous security stack entirely.

= Which CAPTCHA should I choose? =

**Silent Auto-Verify** is the recommended default. Real users pass with zero clicks via JavaScript fingerprint checks behind the scenes - they see nothing. Bots see "Access denied." For login pages, combine Silent Mode with reCAPTCHA v3 in Hybrid Mode for two-layer invisible defense. Shapes CAPTCHA (60fps Canvas with moving geometric figures) is the strongest against AI-based CAPTCHA solvers - it requires real-time computer vision, making it roughly 100x more expensive to crack than standard reCAPTCHA.

= How does BotBlocker verify search engine bots? =

Through **FCrDNS** (Forward-confirmed Reverse DNS) - the same method used by Cloudflare Bot Management, DataDome, and Akamai Bot Manager. Googlebot is verified via PTR (.googlebot.com) + ASN (15169). YandexBot uses triple verification (PTR + ASN 13238 + IP CIDR). Facebook gets dual verification (PTR + ASN 32934). Multi-resolver DNS fallback for reliability. 95% effective against fake crawlers - you cannot spoof FCrDNS without controlling the provider's DNS zone.

= Can I block AI crawlers (ChatGPT, Claude, Perplexity)? =

Yes. GPTBot, ChatGPT-User, OAI-SearchBot, ClaudeBot, Claude-SearchBot, and PerplexityBot are verified via 1,435 CIDR ranges synced from the cloud API. You can allow or block each provider independently. Bytespider (ByteDance) is verified via PTR (.bytedance.com). Trusted AI crawlers pass; impersonators are blocked.

= Can I get Telegram notifications? =

Yes. Free. Connect a Telegram bot via @BotFather, then open Add-ons → Telegram Notifications and enter the Bot Token and Chat ID. You'll receive weekly security summaries: total requests, allowed, blocked, suspicious, search bots, fake bots, plus timezone, protection mode, security score, and block rate. A connection test button verifies setup instantly.

= Can I display security stats on the frontend of my site? =

Yes. 20 shortcodes let you embed counters, charts, health gauges, top IPs/countries, system status, and more on any page. Perfect for client dashboards, status pages, and public security transparency reports.

= What is the Health Score? =

A 44-parameter security assessment displayed as a visual gauge (0-100). Five levels: Critical (<25), Weak (25-49), Moderate (50-69), Strong (70-84), Secure (≥85). Three categories weighted: core protection (75%), cloud extended (25%), neutral indicators. The score updates in real-time as you change settings - a built-in guide to improving your site's security posture.

= Does the PRO version include a trial? =

No traditional trial. Instead, the free version includes the full firewall, all 9 CAPTCHA modes, anti-detect scoring, rate limiting, FCrDNS verification, 2FA, Multisite, Redis/Memcached, Telegram alerts, shortcodes, and live traffic monitoring - enough to protect most sites permanently. A limited-time Premium promo (14 days, no credit card) is available inside the plugin to try cloud features. PRO plans start at $12/month with a 30-day refund policy.

= What happens when I delete the plugin? =

Clean uninstall: all 17 database tables are dropped, 40+ WordPress options deleted, 22+ transients cleared, 12 cron hooks removed, MU-plugin files cleaned, and the uploads/botblocker/ directory deleted. On multisite, per-site cleanup runs in batches of 50. Uninstall feedback dialog collects your reason for leaving. Zero leftover data - no orphaned rows, no stale cron jobs.

== Screenshots ==

1. Dashboard with KPI cards, attack map, blocked-vs-allowed chart, hourly bar chart, distribution donuts, and social proof card
2. 8-step Setup Wizard - from welcome to test attack in under 5 minutes
3. Two-Factor Authentication setup with QR code and backup codes
4. Live traffic monitor with full request context - IP, country, ASN, device, browser, block reason
5. CAPTCHA mode selector - all 9 modes with visual preview and Hybrid Mode toggle
6. Rules manager - IP, IP range, ASN, country, User-Agent, Referer, hostname, path rules
7. Anti-detect settings - 9 weighted detection signals with individual toggles
8. Rate limiting configuration - sliding window, thresholds, subnet aggregation, floor protection
9. Telegram Bot integration (add-on) - Bot Token + Chat ID setup with connection test
10. Tools panel - 15 maintenance and diagnostic actions with one-click execution
11. Shortcode reference - 20 embeddable widgets with preview
12. PRO addon marketplace - one-click install for Security Headers, Hide Login, Speed Up, Malware Scanner, Behavioral Analysis, Truth Source, and more
13. Health Score gauge - 44 parameters, 5 security levels, real-time scoring
14. Settings panel with security presets, CAPTCHA, integrations (reCAPTCHA, Redis, Memcached), and detailed options

== Changelog ==
 
= 1.7.4 =
Speed up admin UI for faster dashboard loading
Add new security options for tighter protection
Add early-stage country blocking at the earliest interception layer
Fix minor bugs and improve stability

= 1.7.3 =
Fix cron reliability on free hosting
Minor bug fixes and improvements.

= 1.7.2 =
Fix minor bugs, improve background task management, optimize for WordPress 7.0.2

= 1.7.1 =
Fix minor bugs

= 1.7 =
Add completely redesigned admin interface with modern multipage layout, KPI cards and command palette integration
Add Toastify notification system
Add new setup wizard
Add new addon marketplace page with one-click install
Add 10 new interface languages: Arabic, Hebrew, Italian, Japanese, Korean, Dutch, Portuguese (Brazil), Swedish, Turkish, Chinese (Simplified) - 17 languages total
Add Behavioral Analysis Engine addon with real-time pattern detection and scoring
Add Cookie Alert addon 
Add Cron Jobs addon
Add Truth Source addon 
Add XMLRPC Tunnel addon 
Add rate limiting system
Add TLS fingerprinting (JA3/JA4) 
Add Telegram Bot integration 
Add hot bans system
Add core rate limit trait
Add malware scanner pre-processor
Add malware truth source verification comparing scanned files against known-good WordPress references
Add HTTPS Protocol addon 
Add short-term bans with configurable duration and automatic expiration
Add session token verification improvements for restricted hosting environments
Add KPI polling with real-time dashboard statistics updates

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

**BotBlocker Security - Complete security platform for your WordPress site.**
