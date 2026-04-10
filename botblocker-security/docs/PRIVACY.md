# Privacy Policy

**Plugin:** BotBlocker Security - Firewall & Bot Protection
**Effective date:** 2026-03-05
**Last updated:** 2026-03-05

---

## 1. Visitor Data

BotBlocker **does not** collect, store, or transmit any personally identifiable information (PII) about your website's visitors.

During request analysis the plugin processes only **technical parameters** - IP address, HTTP headers, User-Agent string, and protocol metadata. This data is evaluated locally on your server and is never sent to external services except when the optional Cloud Threat Intelligence feature is explicitly enabled by the site administrator (see Section 3).

## 2. Site Administrator Data

Upon plugin activation, BotBlocker may transmit the following data **once** to the BotBlocker cloud service (`botblocker.top`):

| Data point | Purpose |
|---|---|
| Site administrator email address | Delivery of critical security advisories, vulnerability disclosures, and plugin update notifications |
| Site URL | Identification of the installation for support and security alert context |

This transmission occurs automatically during the first administrative session after activation.

**No other personal data** of the administrator is collected or transmitted.

## 3. Cloud Threat Intelligence (Optional)

When Cloud Threat Intelligence is enabled, request fingerprints (IP address, User-Agent, HTTP headers) are sent to the BotBlocker API for real-time reputation scoring. These fingerprints contain **no visitor PII** and are used solely for threat classification.

## 4. Third-Party Services

BotBlocker may integrate with the following external services when explicitly configured by the administrator:

* **Google reCAPTCHA** (v2 / v3) - subject to the [Google Privacy Policy](https://policies.google.com/privacy)
* **BotBlocker Cloud API** (`botblocker.top`, `globus.studio`) - operated by the plugin author

No data is shared with any other third party.

## 5. Data Retention

* **Security logs** (IP, User-Agent, block reason) are stored locally in the WordPress database. Retention period is configurable by the administrator.
* **Administrator email and site URL** sent during activation are stored on the BotBlocker cloud service and retained for as long as the subscription is active.

## 6. Opt-Out & Data Removal

* The administrator may opt out of security notifications at any time by contacting **support@botblocker.top**.
* Upon plugin uninstallation (deletion via WordPress), all locally stored data - including logs, settings, and activation flags - is permanently removed.

## 7. Legal Basis (GDPR)

| Processing activity | Legal basis |
|---|---|
| Technical request analysis | Legitimate interest in website security (Art. 6(1)(f) GDPR) |
| Administrator email notification | Legitimate interest in notifying the data controller of security threats affecting their property (Art. 6(1)(f) GDPR) |
| Cloud threat intelligence | Legitimate interest, enabled by explicit administrator action |

## 8. CCPA Compliance

BotBlocker does not sell personal information. No visitor PII is collected. The administrator email is used exclusively for the purposes stated above.

## 9. Contact

For privacy-related questions or data removal requests:

* **Email:** support@botblocker.top
* **Web:** https://botblocker.top/contacts/
