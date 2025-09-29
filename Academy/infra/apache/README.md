# Apache HTTPD Security Headers & Edge Configuration

This directory mirrors the hardened edge posture delivered for Nginx but for
teams standardising on Apache HTTP Server 2.4. The virtual host and header
snippet align with the Laravel middleware defaults so that origin responses and
edge enforcement stay in sync across both stacks.

## Files

- `security-headers.conf` – reusable header include applied from
  `conf-available/security-headers.conf` (symlink into `conf-enabled`) to enforce
  CSP, HSTS, COOP/COEP, CORP, and privacy controls.
- `academy_communities.conf` – production-grade HTTPS virtual host with HTTP/2,
  real IP restoration, ModSecurity (OWASP CRS), CDN-aware caching, WebSocket
  proxying, rate limiting hooks, and PHP-FPM integration over unix sockets.

## Prerequisites

Enable the following Apache modules prior to deployment (examples shown for
Debian/Ubuntu via `a2enmod`):

```
a2enmod ssl http2 proxy proxy_fcgi proxy_wstunnel headers rewrite remoteip \
       security2 deflate env setenvif ratelimit expires
```

Install and tune complementary packages:

- **ModSecurity v3** with the OWASP Core Rule Set located under
  `/etc/modsecurity/owasp-crs/` plus organisation-specific overrides at
  `/etc/modsecurity/academy/`.
- **mod_evasive** or an equivalent DoS mitigation helper to back the
  rate-limiting hooks exposed in the vhost.
- **certbot** (or ACM/other certificate automation) to manage TLS assets at
  `/etc/letsencrypt/live/...`.

## Deployment

1. Copy `security-headers.conf` to `/etc/apache2/conf-available/security-headers.conf`
   and enable it with `a2enconf security-headers`.
2. Deploy `academy_communities.conf` to `/etc/apache2/sites-available/` and enable
   it with `a2ensite academy_communities`.
3. Update paths for the Laravel release (`DocumentRoot`), PHP-FPM socket,
   certificate files, CDN hostnames, and trusted proxy CIDRs to match the target
   environment.
4. Ensure `mod_remoteip` trusted proxy networks mirror the CDN/WAF estate so log
   pipelines and rate controls operate on true client addresses.
5. Reload Apache (`systemctl reload apache2`) and validate headers, ModSecurity
   rule execution, rate limiting behaviour, and WebSocket upgrades using the
   same smoke test suite maintained for the Nginx edge.

Keeping Nginx and Apache configurations aligned gives operations flexibility in
regions or managed services where one reverse proxy is mandated over the other
without sacrificing security posture or observability.
