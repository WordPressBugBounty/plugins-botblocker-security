# Security Policy

## Supported Versions
We provide security fixes for the latest minor release only:
- 1.6.x (current)

Please update to the latest version before reporting an issue.

## Reporting a Vulnerability
Send a private report (do NOT open a public forum post):
- Email: security@globus.studio
- Contact form: https://botblocker.top/contacts/

Include:
1. Subject: [SECURITY] Short title
2. A clear description
3. Steps to reproduce
4. Expected vs actual result
5. Impact (what can be done / bypassed)
6. Environment (WP, PHP, plugin version, special cache / WAF)
7. Proof of concept (minimal request / snippet)
8. Suggested fix (optional)

## Response Targets (Goal)
- Acknowledgment: within 48h
- Initial assessment: within 5 business days
- Fix & internal test: depends on severity (Critical ASAP; others in next release)
- Public disclosure: after update is published

## Severity (General Guide)
- Critical: Remote code execution, full auth bypass, unrestricted SQL injection
- High: Privilege escalation, full CAPTCHA bypass, large-scale brute force bypass
- Medium: XSS, CSRF with meaningful action, partial filter bypass
- Low: Minor info leak, non-exploitable hardening gaps

## Coordinated Disclosure Flow
1. You report privately
2. We confirm & reproduce
3. We develop & test a fix
4. We release an update (changelog marks Security)
5. We optionally credit you (if you want)
6. Public details may follow after adoption window

## Safe Harbor
We will not pursue action if you:
- Act in good faith
- Avoid data exfiltration / disruption
- Do not abuse the vulnerability
- Do not publicly disclose before a fix

## In Scope
- Plugin PHP code
- JavaScript assets
- AJAX endpoints
- Detection, filtering and licensing logic

## Out of Scope
- Other plugins / themes
- Server or hosting misconfiguration
- Issues requiring social engineering / physical access
- Generic missing security headers
- Reports without reproduction steps

## Dependencies
We monitor third‑party libraries and update when security releases appear.

## Credit / Rewards
No monetary bounty program yet. Optional researcher credit (send display name).

## Escalation
No response after 5 days? Re-send with subject: [SECURITY - FOLLOW UP].

Thank you for helping keep BotBlocker Security safe.
