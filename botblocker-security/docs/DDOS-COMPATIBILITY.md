# BotBlocker — DDoS Protection Service Compatibility

When BotBlocker runs behind an external DDoS protection service (DDoS-Guard,
Stormwall, Cloudflare Under Attack Mode, Qrator, etc.), the two systems may
conflict. This document explains the problem, the built-in mitigation, and
the recommended server-side configuration.

---

## The Problem

BotBlocker's verification page sends an AJAX `POST` request to
`wp-admin/admin-ajax.php`. External DDoS protection services intercept **all**
requests — including this internal AJAX call — and may return their own
JavaScript challenge instead of the real WordPress response.

A typical DDoS-Guard response looks like:

```html
<script>document.cookie = "humans_XXXXX=1"; document.location.reload(true)</script>
```

Because BotBlocker expects a JSON response, `JSON.parse()` fails and the
visitor gets stuck. The exact cookie name and format vary by provider:

| Service          | Typical cookie pattern          |
|------------------|---------------------------------|
| DDoS-Guard       | `humans_XXXXX=1`                |
| Stormwall        | `swp_token=…`                   |
| Cloudflare (UAM) | `cf_clearance=…`                |
| Qrator           | `qrator_jsid=…`                 |

---

## Built-in Automatic Mitigation (v1.6.13+)

Starting from version **1.6.13**, BotBlocker detects non-JSON AJAX responses
from external DDoS protection services. When detected:

1. The cookie value is extracted (both `"…"` and `'…'` patterns) and set in
   the browser automatically.
2. The AJAX request is retried with progressive delays (1s, 2s, 3s).
3. Up to **3 retries** are attempted.
4. If all retries fail, BotBlocker **redirects to the original page** instead
   of showing CAPTCHA again. This mirrors the "manual refresh" that would
   otherwise be needed — the DDoS service cookie is already set, and the
   BotBlocker cookie may already be valid from a partial success.
5. During retries, the XHR timeout is increased from 5s to 10s to accommodate
   slower DDoS service round-trips.

This handles the vast majority of real-world DDoS protection setups including
SShield, DDoS-Guard, Stormwall, and similar services.

---

## What Is NOT Handled Automatically

Complex challenges that require:

- **Proof-of-Work computation** (e.g., Cloudflare Turnstile, DDoS-Guard
  advanced mode) — the response contains JavaScript that computes a hash
  before setting the cookie.
- **Interactive CAPTCHA** from the DDoS service itself (e.g., hCaptcha
  presented by Cloudflare).
- **302 redirects** to an external challenge page — the AJAX request never
  reaches WordPress at all.

For these cases, server-side configuration is **required**.

---

## Recommended Server-Side Configuration

The most reliable solution is to **bypass DDoS challenges for
`admin-ajax.php`** so that BotBlocker's AJAX requests always reach WordPress.

### DDoS-Guard

In the DDoS-Guard control panel → Protection Rules → add a bypass rule:

- **Path:** `/wp-admin/admin-ajax.php`
- **Method:** `POST`
- **Action:** Skip JS challenge

### Stormwall

In the Stormwall dashboard → WAF Settings → Exceptions:

- **URI pattern:** `/wp-admin/admin-ajax.php`
- **Protection mode:** Disabled / Pass-through

### Cloudflare

Create a **WAF Custom Rule** (or legacy Page Rule):

- **URI Path** equals `/wp-admin/admin-ajax.php`
- **Action:** Skip → select "Browser Integrity Check" and "Under Attack Mode"

Or via Page Rule:

- **URL:** `*example.com/wp-admin/admin-ajax.php*`
- **Security Level:** Essentially Off

### Qrator

In the Qrator dashboard → Filter settings → Exceptions:

- **URL:** `/wp-admin/admin-ajax.php`
- **Action:** Bypass JS challenge

### Generic / Other Providers

Add `/wp-admin/admin-ajax.php` to the challenge bypass list (sometimes called
"allowlist", "whitelist", or "exception"). The key requirement is that `POST`
requests to this path must receive the real WordPress response without any
intermediate JS challenge or redirect.

---

## Security Implications

Bypassing the DDoS challenge for `admin-ajax.php` is safe because:

1. **BotBlocker validates every AJAX request** via WordPress nonce
   (`check_ajax_referer`), IP binding, User-Agent hash, and timestamp.
2. The AJAX endpoint **does not expose admin functionality** — it only
   processes BotBlocker's visitor verification flow.
3. The nonce is single-use and time-limited, preventing replay attacks.

An attacker cannot exploit this bypass to reach admin functions or flood
WordPress, because every request without a valid nonce is rejected immediately.

---

## Verifying the Fix

1. Open browser DevTools → Network tab.
2. Visit a page that triggers BotBlocker's verification.
3. Click the CAPTCHA / verification button.
4. Check the AJAX request to `admin-ajax.php`:
   - **Expected:** HTTP 200, JSON body with `"cookie"` field.
   - **Problem:** HTML body containing `<script>document.cookie…` or HTTP
     403/503 from the DDoS service.

If the response is still non-JSON after adding the bypass rule, clear the
DDoS service cache and retry, or contact your DDoS provider's support.

---

## Diagnostic Checklist

| Symptom | Likely cause | Fix |
|---------|-------------|-----|
| Page stuck on "Loading…" after click | DDoS service blocking AJAX | Add bypass rule (see above) |
| Raw `<script>` tag visible on page | DDoS service returning HTML instead of JSON | Same as above |
| CAPTCHA reappears after successful click | Cookie from DDoS service not persisted | Ensure `SameSite` and `Secure` flags are compatible |
| Works in Incognito but not in normal mode | Stale DDoS cookie or browser extension interference | Clear cookies, disable extensions |
