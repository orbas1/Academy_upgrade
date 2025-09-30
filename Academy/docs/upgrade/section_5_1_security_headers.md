# Section 5.1 — HTTP Security Headers Deep Dive

## Overview
The platform now negotiates HTTP security headers automatically between web and API/mobile clients. A central builder generates matching Nginx and Laravel policy values so Content-Security-Policy (CSP), Referrer-Policy, and Permissions-Policy stay aligned across deployment targets.

## Key Changes
- **Deterministic header builder:** `App\Support\Security\SecurityHeaderValueBuilder` normalizes directive lists, merges environment overrides, and emits Strict-Transport-Security, CSP, and Permissions-Policy values with canonical ordering.
- **Profile auto-detection:** `EnsureSecurityHeaders` infers profiles by inspecting request paths, `Accept` types, AJAX hints, and the new `X-Academy-Client` header. HTML responses keep the baseline policy, JSON APIs receive a locked-down profile, and native mobile calls get blob/media allowances.
- **Mobile identity handshake:** Flutter clients send `X-Academy-Client` and `User-Agent` metadata describing platform, version, and environment. Middleware consumes this signal to activate the `mobile-api` policy variant.
- **Config-driven directives:** `config/security-headers.php` exposes env-tunable source lists (script/style/font/media/connect) plus explicit mobile allowances (blob URLs, deep-link schemes). Profiles can be extended without editing middleware.

## Operational Notes
- Nginx continues to include `infra/nginx/security-headers.conf` for HTML routes; API workers rely on Laravel for dynamic profile negotiation.
- Mobile API requests inherit `Cross-Origin-Resource-Policy: cross-origin` so native clients can safely consume resources while HTML surfaces remain `same-site`.
- Permissions-Policy keeps cameras/microphones disabled by default; geolocation is limited to self plus optionally configured deep-link schemes.

## Validation
- **Laravel tests:** `EnsureSecurityHeadersTest` validates default headers, profile overrides, auto-detected API behavior, and mobile header negotiation.
- **Flutter tests:** `community_api_service_headers_test.dart` ensures the client identity headers and bearer refresh flow are attached to outbound requests.
- **Manual verification:** curl examples using `-H "X-Academy-Client: mobile-app/android; version=1.2.3; env=staging"` confirm the `mobile-api` CSP (blob allowances, cross-origin resource policy) is returned.

## Next Steps
- Track adoption in logs by monitoring `security_headers.profile` context (add structured logging in future work).
- Extend the builder with nonce/hash helpers when inline scripts are phased out of Blade templates.
