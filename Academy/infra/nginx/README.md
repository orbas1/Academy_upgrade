# Nginx Security Headers & Edge Configuration

This directory contains the hardened security header include and production
virtual host reference configuration used by operations teams for the
Academy communities platform. Teams running Apache HTTPD can find an
equivalent configuration under `../apache` to keep both reverse proxy stacks
aligned.

## Files

- `security-headers.conf` – shared include consumed by every vhost to enforce
  CSP, HSTS, COOP/COEP, and related security headers.
- `academy_communities.conf` – reference HTTPS vhost with CDN-aware real IP
  handling, ModSecurity (OWASP CRS) integration, rate limiting, WebSocket
  upgrades, and cache policies for assets and S3 media.

## Deployment

1. Copy `security-headers.conf` to `/etc/nginx/snippets/security-headers.conf`
   on each environment (dev/staging/prod).
2. Deploy `academy_communities.conf` to `/etc/nginx/sites-available/academy_communities.conf`
   and symlink into `sites-enabled`.
3. Adjust certificate paths, upstream socket/IPs, CDN hostnames, and rate limit
   thresholds based on the target environment.
4. Ensure ModSecurity v3 with the OWASP Core Rule Set is installed and that
   `/etc/nginx/modsecurity/main.conf` loads the tuned rule set for Academy.
5. Reload Nginx and validate headers, rate limiting, and WAF behaviour using the
   automated smoke suite (curl, k6, and OWASP ZAP baseline scripts).
6. Keep the snippets aligned with Laravel's `config/security-headers.php`
   defaults so that origin responses and edge enforcement remain consistent.
