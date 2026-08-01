# BotBlocker - TLS Fingerprinting (JA3/JA4)

TLS fingerprinting identifies bots by analyzing TLS handshake signatures. Real
browsers (Chrome, Firefox, Safari) produce distinct JA3/JA4 hashes that differ
from headless browsers (Puppeteer, Playwright) and automation tools (curl,
python-requests).

BotBlocker cross-validates: if User-Agent says "Chrome 130" but TLS fingerprint
matches "python-requests" → bot confirmed, blocked or challenged.

---

## Requirements

PHP cannot read TLS ClientHello - the fingerprint must be injected as an HTTP
header by a reverse proxy or web server module. This is opt-in and requires
server configuration.

**TLS fingerprinting remains inactive (zero impact) until all three are true:**
1. `tls_fingerprint_check = 1` (Settings → TLS Fingerprint)
2. `tls_fingerprint_trusted_proxy` is set to your proxy's IP/CIDR
3. Fingerprint headers are actually present in the request

Without server-side setup, the feature is completely inert - no DB queries, no
network calls, no performance overhead on the detection path.

---

## Setup Options

### Option 1: Cloudflare (easiest)

Cloudflare automatically sends `Cf-JA3-Fingerprint` on **Business and
Enterprise** plans. BotBlocker reads this header automatically.

1. Settings → TLS Fingerprint → enable `tls_fingerprint_check`
2. Set Trusted Proxy to Cloudflare's IP range: `173.245.48.0/20`
3. Check Diagnostics panel - Current JA3 should show a value

No server configuration needed.

### Option 2: nginx + ngx_http_ssl_ja3

Install [nginx-ssl-ja3](https://github.com/fooinha/nginx-ssl-ja3) module, then:

```nginx
server {
    listen 443 ssl http2;
    ssl_ja3 on;
    location / {
        proxy_set_header X-TLS-JA3 $ssl_ja3_hash;
        proxy_set_header X-TLS-JA4 $ssl_ja4_hash;
        proxy_pass http://backend;
    }
}
```

BotBlocker settings:
- `tls_fingerprint_header_ja3` = `X-TLS-JA3` (default)
- `tls_fingerprint_header_ja4` = `X-TLS-JA4` (default)
- Trusted Proxy = your nginx server IP (e.g. `127.0.0.1` if on same machine)

### Option 3: HAProxy

```haproxy
frontend https
    bind :443 ssl crt /etc/ssl/cert.pem
    http-request set-header X-TLS-JA3 %[ssl_fc_ja3_hash]
    http-request set-header X-TLS-JA4 %[ssl_fc_ja4_hash]
    use_backend app
```

### Option 4: LiteSpeed

LiteSpeed exposes `$ssl_ja3_hash` and `$ssl_ja4_hash` variables natively.
Configure via WebAdmin Console → Server → External App → add as response
header or pass to PHP as environment variable.

---

## How It Works

### Detection Categories

| Category | Examples | Action |
|----------|----------|--------|
| `browser` | Chrome 120+, Firefox 130+, Safari 18+ | Pass (if UA family matches) |
| `mobile` | Chrome Mobile, Safari iOS | Pass (if UA family matches) |
| `bot_legitimate` | Googlebot, Bingbot TLS fingerprints | Pass |
| `automation` | curl, python-requests, httpx, scrapy | Block or challenge |
| `headless` | Headless Chrome/Puppeteer/Playwright | Suspicion score increased |
| `unknown` | Not in database | Suspicion score +10 (soft) |

### UA vs TLS Cross-Validation

The killer feature: even if a bot's TLS fingerprint is `unknown`, BotBlocker
compares the claimed User-Agent against the detected TLS category:

- UA = "Chrome/130" + TLS = `python-requests` → 100% bot → blocked
- UA = "Chrome/130" + TLS = `chrome` category → pass
- UA = anything + TLS = `unknown` → soft suspicion (new browser versions)

### Security: Trusted Proxy

Without `tls_fingerprint_trusted_proxy`, BotBlocker ignores all TLS headers.
This prevents trivial header spoofing - a bot could send `X-TLS-JA3:
chrome-real-hash` in its HTTP request. The trusted proxy requirement ensures
headers are only accepted from your own reverse proxy that generated them.

---

## Fingerprint Database

The fingerprint database is stored in the `{wp_}bbcs_tls_fingerprints` table
and cached to `tls_fingerprints.php` for zero-DB-query runtime reads.

### Populating the Database

1. **Cloud Sync (recommended):** Settings → TLS Fingerprint → Sync Now.
   Fetches known fingerprints from BotBlocker cloud (requires Cloud API license).
2. **Import JSON:** Import your own fingerprint DB in JSON format:
   ```json
   [{"fingerprint": "abc123", "category": "browser", "ua_family": "chrome", "description": "Chrome 130"}]
   ```
3. **Manual:** Entries can be added via the `bbcs_tls_fingerprints` DB table directly.

### Without Cloud API

The feature still works with just a `browser` / `mobile` / `bot_legitimate`
category entries. The most valuable detection - UA vs TLS mismatch - is
effective with even a small known-browser database. Unknown fingerprints get
+10 suspicion (soft signal), so false positives are unlikely.

---

## Diagnostics

Settings → TLS Fingerprint → Diagnostics shows your current JA3/JA4
fingerprints as received by the server. If both show "(not detected)":

1. Verify the web server is injecting headers (check with `curl -v`)
2. Verify `tls_fingerprint_trusted_proxy` matches your proxy IP/CIDR
3. For Cloudflare: Business+ plan required for `Cf-JA3-Fingerprint`

---

## Limitations

| Limitation | Impact |
|------------|--------|
| Requires server-side module | Not available on shared hosting |
| JA3/JA4 can be spoofed by advanced bots | JA4 is more resistant; cross-validation with HTTP/2 SETTINGS helps |
| New browser version = unknown fingerprint | Soft scoring (+10), not a block |
| Cloudflare Free/Pro = no fingerprint header | Business/Enterprise only |

### Out-of-Box Behavior

On a fresh install with default settings:
- `tls_fingerprint_check = 0` → feature disabled
- `tls_fingerprint_trusted_proxy = ''` → headers ignored even if enabled
- `check_tls_fingerprint()` in the main detection flow returns `false` immediately
- Zero performance impact, zero network calls, zero false positives
