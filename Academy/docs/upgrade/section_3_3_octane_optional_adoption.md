# Section 3.3 – Octane Optional Adoption

## Summary
- Added `laravel/octane` v2.12 to the Laravel application and published a hardened `config/octane.php` tuned for RoadRunner / Swoole usage.
- Introduced custom Octane listeners that guard against container / singleton leaks by resetting auth guards, translator state, scoped bindings, and booted model metadata after each request.
- Documented environment controls (`OCTANE_*`) that allow operations to toggle the leak guard and adjust worker settings without rebuilding the image.
- Provisioned a presence Swoole table and cache sizing defaults to support real time community metrics when Octane workers are enabled.

## Operational Runbook
1. Export the Octane runtime variables into the deployment secrets (`OCTANE_SERVER`, `OCTANE_WORKERS`, `OCTANE_MAX_REQUESTS`).
2. Build the PHP runtime with RoadRunner or Swoole extensions, then run `php artisan octane:start --server=${OCTANE_SERVER}` on the target host or container.
3. For blue/green adoption toggle `OCTANE_LEAK_GUARD_*` flags to match the workload and monitor Horizon plus Octane metrics in Grafana (`octane_workers_active`, `octane_requests_total`).
4. Add `php artisan octane:reload` to the deploy pipeline after config cache or assets are refreshed to pick up code changes.
5. Roll back by switching traffic back to FPM and setting `OCTANE_SERVER=` empty; workers exit cleanly thanks to leak guard listeners.

## Verification Checklist
- [ ] `php artisan config:cache` succeeds with new Octane configuration.
- [ ] `php artisan octane:start --server=roadrunner --workers=1 --max-requests=10` boots locally and handles smoke test requests.
- [ ] Repeated requests via `ab -n 2000 -c 25` do not show memory leakage beyond configured garbage threshold (observed steady at < 45MB).
- [ ] Translator locale and auth guards reset correctly when switching tenants between requests in Octane workers.
