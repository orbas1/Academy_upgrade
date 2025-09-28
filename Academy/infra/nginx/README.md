# Nginx Security Headers Snippet

This directory contains the hardened security header include that operations
teams should reference from every Academy web property served by Nginx.

## Deployment

1. Copy `security-headers.conf` to `/etc/nginx/snippets/security-headers.conf` on
   each environment (dev/staging/prod).
2. Reference the snippet inside the relevant `server` block:

   ```nginx
   server {
       include snippets/security-headers.conf;
       # ... rest of vhost configuration
   }
   ```

3. Reload Nginx and validate headers using curl or automated smoke tests.
4. Keep the snippet aligned with Laravel's `config/security-headers.php`
   defaults so that origin responses and edge enforcement remain consistent.
