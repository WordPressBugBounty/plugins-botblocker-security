# BotBlocker — Cache Compatibility Guide

BotBlocker's verification page uses per-request tokens (nonce, challenge, hashes
bound to IP + timestamp). **This page must never be cached.** The plugin sets
`Cache-Control: no-store`, `DONOTCACHEPAGE`, and other signals automatically,
but server-level caches may need manual configuration.

---

## How It Works

| Layer | What BotBlocker does automatically |
|-------|-------------------------------------|
| PHP cache plugins | Defines `DONOTCACHEPAGE`, `DONOTCACHEOBJECT`, `DONOTCACHEDB` |
| HTTP headers | Sends `Cache-Control: no-store, no-cache`, `Pragma: no-cache`, `Expires` in the past |
| LiteSpeed | Sends `X-LiteSpeed-Cache-Control: no-cache` |
| MU-plugin phase | Defines `DONOTCACHEPAGE` when BotBlocker cookie is absent |

For most setups this is sufficient. Server-level caches that bypass PHP require
the snippets below.

---

## WordPress Cache Plugins

### WP Super Cache — PHP / Legacy mode

Works automatically. BotBlocker defines `DONOTCACHEPAGE` before the output
buffer callback runs.

### WP Super Cache — Expert (mod_rewrite) mode

**Not compatible with MU-phase protection.** In this mode, `.htaccess` rules
serve static files before PHP is loaded. Add a cookie-based exception:

```apache
# .htaccess – inside the WP Super Cache rewrite block
RewriteCond %{HTTP_COOKIE} !BotBlocker [NC]
RewriteRule .* - [E=CACHE_MISS:1]
```

Replace `BotBlocker` with your actual cookie name if changed in settings.

### W3 Total Cache — Disk (Basic / Enhanced)

Works automatically via `DONOTCACHEPAGE`. If using **Disk: Enhanced** with
rewrite rules, add the same `.htaccess` exception as above.

### W3 Total Cache — Disk (Enhanced) with Nginx rules

See the Nginx section below.

### WP Rocket

Works automatically. WP Rocket checks `DONOTCACHEPAGE` in its output buffer.

### LiteSpeed Cache (LSCWP)

Works automatically. BotBlocker sends both `DONOTCACHEPAGE` and
`X-LiteSpeed-Cache-Control: no-cache`. No extra configuration needed.

### Hummingbird / WP Fastest Cache / Cache Enabler

All check `DONOTCACHEPAGE` — works automatically.

---

## Nginx

### FastCGI Cache

```nginx
# /etc/nginx/conf.d/botblocker-cache.conf  (or inside your server block)

# Read cookie name — default "BotBlocker"
set $skip_cache 0;

# Skip cache when BotBlocker cookie is absent
if ($cookie_BotBlocker = "") {
    set $skip_cache 1;
}

# Skip cache for admin, login, and AJAX
if ($request_uri ~* "/wp-admin/|/wp-login\.php|/wp-json/|admin-ajax\.php") {
    set $skip_cache 1;
}

fastcgi_cache_bypass $skip_cache;
fastcgi_no_cache    $skip_cache;
```

> If you renamed the cookie, replace `$cookie_BotBlocker` with
> `$cookie_YourCookieName`. Nginx converts hyphens to underscores in
> cookie variable names.

### Nginx Proxy Cache

```nginx
proxy_cache_bypass  $cookie_BotBlocker;
proxy_no_cache      $cookie_BotBlocker;

# Alternatively, skip when cookie is empty:
set $bb_skip 0;
if ($cookie_BotBlocker = "") {
    set $bb_skip 1;
}
proxy_cache_bypass $bb_skip;
proxy_no_cache     $bb_skip;
```

After editing, reload Nginx:

```bash
nginx -t && systemctl reload nginx
```

---

## Apache

### mod_cache / mod_disk_cache

```apache
<IfModule mod_headers.c>
    # Prevent caching for visitors without BotBlocker cookie
    Header set Cache-Control "no-store, no-cache, must-revalidate" env=!BBCS_COOKIE
    SetEnvIf Cookie "BotBlocker=" BBCS_COOKIE
</IfModule>
```

### mod_rewrite (static page cache bypass)

```apache
RewriteEngine On
RewriteCond %{HTTP_COOKIE} !BotBlocker [NC]
RewriteRule ^(.*)$ - [E=CACHE_MISS:1]
```

---

## Varnish

```vcl
sub vcl_recv {
    # Pass (bypass cache) when BotBlocker cookie is not present
    if (req.http.Cookie !~ "BotBlocker=") {
        return (pass);
    }
}

sub vcl_backend_response {
    # Never cache responses with no-store
    if (beresp.http.Cache-Control ~ "no-store") {
        set beresp.uncacheable = true;
        set beresp.ttl = 0s;
    }
}
```

After editing:

```bash
varnishadm "ban req.url ~ ."
systemctl reload varnish
```

---

## Cloudflare

### Page Rules (Legacy)

Create a Page Rule for `*yourdomain.com/*`:
- **Cache Level:** Bypass Cache
- Condition: cookie `BotBlocker` is absent

> Cloudflare Page Rules cannot match on cookies directly. Use a
> Cloudflare Worker or Transform Rule instead.

### Cache Rules (recommended)

In **Caching → Cache Rules**, create a rule:

- **When:** `not (http.cookie contains "BotBlocker=")`
- **Then:** Cache eligibility → Bypass cache

### Cloudflare Worker (advanced)

```js
addEventListener('fetch', event => {
  event.respondWith(handleRequest(event.request));
});

async function handleRequest(request) {
  const cookie = request.headers.get('Cookie') || '';
  if (!cookie.includes('BotBlocker=')) {
    // Bypass cache for unverified visitors
    const url = new URL(request.url);
    url.searchParams.set('_bbcs_nocache', Date.now());
    return fetch(new Request(url, request));
  }
  return fetch(request);
}
```

---

## Redis / Nginx Helper / Jenga Page Cache

Redis-based full-page caches (e.g., Jenga, Jenga Page Cache, Jenga Starter)
typically check `DONOTCACHEPAGE` — works automatically.

If using **Jenga Pay-as-you-go** or a custom Redis page cache with Nginx
rules, use the Nginx FastCGI config from above.

---

## Troubleshooting

| Symptom | Cause | Fix |
|---------|-------|-----|
| Verification page shown once, then cached and broken | Cache stores check page HTML | Ensure `DONOTCACHEPAGE` is respected or add server-level bypass |
| Visitors skip verification entirely | Cache serves the normal page | Add cookie-based bypass rule at server level |
| Verified visitors see verification again | Cookie not sent back (CDN strips cookies) | Whitelist BotBlocker cookie in CDN settings |
| `403` page is cached and shown to everyone | Server caches the denied page | BotBlocker sends `Cache-Control: no-store` — check CDN settings |

---