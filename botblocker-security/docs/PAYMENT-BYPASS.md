# BotBlocker - Payment Gateway Bypass

When a customer pays, the payment provider (Stripe, PayPal, Mollie, and 150+
others) calls your site back directly - webhooks, IPN, postbacks. These
server-to-server callbacks have no browser session, no cookies, and no
JavaScript. If BotBlocker challenged them, payments would break.

The **Payment Gateway Bypass** recognizes such callbacks and lets them skip
the JS-challenge, CAPTCHA and rate-limit checks, while still applying the
early request-safety layers (malformed request detection, proxy and country
checks). It is the layer that makes BotBlocker compatible with e-commerce
"out of the box".

---

## When It Applies

The bypass is completely inert until **both** are true:

1. `Allow Payment Gateway Callbacks` is ON (Settings → Payment Gateways), and
2. the incoming request matches one of the four recognition layers below.

Disabled state has zero effect on any request - every payment callback is
then processed like any other visitor.

---

## Recognition Layers

A request is recognized as a payment callback by **any** of these layers
(checked in this order):

| # | Layer | What matches |
|---|-------|--------------|
| 1 | Path | 198+ URL path patterns: `wc-api/*`, `wc-ajax/*`, WC REST payment routes, IPN/postback endpoints of EDD, Give, Memberpress, RCP, PMPro, Surecart and gateway-specific paths |
| 2 | Query keys | 227+ known query keys (`wc-api`, `ipn`, `payment`, `callback`, ...) plus generic markers (`webhook`, `ipn`, `callback_handler`, ...) |
| 3 | admin-ajax / admin-post action | 360+ known action names and action substrings (`stripe_*`, `paypal_*`, `mollie_*`, ...) |
| 4 | Signature header | 114+ webhook signature headers (`Stripe-Signature`, `Paypal-Transmission-Sig`, `X-Webhook-Signature`, ...) - POST requests only |

**Method whitelist.** Only `GET`, `POST` and `HEAD` requests can ever bypass.
Any other method (`PUT`, `DELETE`, `OPTIONS`, ...) is never recognized as a
payment callback.

**Header layer is format-checked only.** The signature header must be present
and at least 8 characters long, and the request must be a POST. The value is
not cryptographically verified - do not rely on it as an authentication
mechanism. Combine it with the options below on hardened sites.

---

## Bypass Modes

The effect of a recognized callback depends on which layers matched and on
the settings:

### Full bypass (default for specific matches)

A precise match (known payment path, query key or action, with
`Enforce IP / ASN Rules for Payment Callbacks` OFF):

- request is treated as a **legal payment bot** and the shield's main run
  exits early: no JS-challenge, no CAPTCHA, no rate-limit
- still applied: the early request-safety checks that run before the shield
  pipeline (malformed request detection, proxy detection, country checks)

### Partial bypass (generic markers or enforced rules)

A match on **generic** markers (`webhook`, `ipn`, `callback_handler`) or any
match while `Enforce IP / ASN Rules for Payment Callbacks` is ON:

- CAPTCHA / JS-challenge is still skipped
- but **IP blacklists, ASN rules, path rules and country blocks stay active**
- a blocked IP cannot use a payment callback to bypass its block

---

## Settings

| Setting | Default | Meaning |
|---------|---------|---------|
| `Allow Payment Gateway Callbacks` | OFF | Master switch. When OFF the bypass never applies. Recommended ON for any site with WooCommerce or another e-commerce plugin. |
| `Log Payment Bypass Events` | ON | Records every applied bypass into the traffic log (hit type `81`) for auditing. |
| `Strict Webhook Validation (POST only)` | OFF | When ON, only POST requests can match path/query/action layers. GET-based legacy callbacks will not bypass. |
| `Enforce IP / ASN Rules for Payment Callbacks` | OFF | When ON, the bypass is always partial: CAPTCHA skipped, but IP/ASN/path/country rules remain active. |

Hardened-site recommendation: `Allow Payment Gateway Callbacks` ON +
`Enforce IP / ASN Rules` ON + `Strict Webhook Validation` ON. Real webhooks
are POSTs from payment provider IPs and keep working; anything else keeps
full rule protection.

---

## E-commerce Detection

BotBlocker detects 25+ e-commerce platforms (WooCommerce, EDD, Give,
Memberpress, RCP, PMPro, Surecart, ...). When e-commerce software is present,
the Payment Gateways tab shows a recommendation banner. Enabling the bypass
is a single toggle.

---

## Troubleshooting

| Symptom | Cause | Fix |
|---------|-------|-----|
| Orders paid but status stays "pending" | Webhook is challenged/blocked | Enable `Allow Payment Gateway Callbacks` |
| Webhook comes from a blocked IP | `Enforce IP / ASN Rules` is ON and the provider's IP is blacklisted | Allowlist the provider's IP range instead of disabling enforcement |
| Legacy gateway sends GET callbacks | `Strict Webhook Validation` is ON | Turn strict mode OFF for that gateway |
| Want to audit which callbacks bypassed | - | Enable `Log Payment Bypass Events` and check the traffic log for hit type `81` |
| A bot abuses the signature-header layer | POST with a fake ≥8-char header bypasses challenge | Enable `Strict Webhook Validation` + `Enforce IP / ASN Rules`; the header layer alone is not an authentication mechanism |
