# Security Considerations

This document describes known security considerations that have been reviewed and intentionally left unfixed, either because they require architectural changes, have very low practical risk, or are mitigated by other factors.

## Twig `|raw` on URL Concatenation (OXID 7 only)

**Files:** `views/twig/frontend/applepay_button.html.twig` (multiple lines)
**Risk:** Medium (fragile pattern)

The Apple Pay template constructs URLs using Twig string concatenation with `|raw` to disable auto-escaping. The base URL comes from `oViewConf.getSslSelfLink()` which is framework-controlled and not user-influenced. While the practical risk is low, the pattern is fragile — if the URL source ever changes to include user input, XSS could result.

**Mitigation:** The URL source (`getSslSelfLink()`) is an internal OXID framework method that returns a server-controlled value. Moving URL construction to PHP would require significant refactoring of the Apple Pay JavaScript integration.

## MD5 Usage for Cache Keys and Session Hashing

**Files:** `src/Core/ServiceFactory.php`, `src/Core/Onboarding/Onboarding.php`
**Risk:** Low

MD5 is used for generating cache keys (`md5($sessionId . $basketId . $paymentId)`) and action hashes (`md5($sessionId)`). While MD5 is cryptographically broken, these uses are non-security-critical: cache keys only need uniqueness, not collision resistance.

**Note:** The `OrderProcessTrackingService` tracking ID and the `PartnerConfig` nonce fallback have been fixed in the latest security update.

## Weak Tracking ID Generation

**File:** `src/Service/OrderProcessTrackingService.php:31`
**Risk:** Low

`substr(md5(uniqid()), 0, 6)` generates a 6-character tracking ID for session-internal logging. This is not used for any security purpose — only for correlating log entries within a single request flow.

## Race Conditions in Order Processing

**Context:** Webhook callbacks and frontend redirects can process the same order concurrently.
**Risk:** Low-Medium

The PayPal module has a "too fresh" guard for webhooks, but no explicit database locking (SELECT FOR UPDATE) to prevent concurrent processing. In rare cases, this could lead to duplicate state updates or inconsistent order status.

**Mitigation:** PayPal's own idempotency mechanisms and the "too fresh" delay (which defers webhook processing for recently created orders) provide partial protection. Full fix would require database-level locking across the order finalization flow.

## Theoretical Open Redirect

**File:** `src/Controller/OrderController.php`
**Risk:** Low

A redirect URL from the PayPal API response stored in the session is used for redirects. The URL is not directly user-controlled — it comes from PayPal's API. Exploitation would require compromising the PayPal API response.

**Note:** A domain validation check has been added in the latest security update.

---

*Last updated: 2026-03-06*
