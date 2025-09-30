# Systemd Units for Horizon Queue Workers

This directory defines hardened systemd unit templates for Academy's Horizon
supervisors. Each queue group (notifications, media, webhooks, search-index,
default) gets its own instantiated service derived from `horizon@.service`.

The templates assume the application is deployed under
`/var/www/academy/current` with environment-specific overrides stored in
`/etc/academy/horizon/<queue>.env`. The autoscaler (see the `queues:autoscale`
Artisan command) adjusts those env files before reloading the units so that
process counts track queue backlogs.

## Usage

```bash
sudo cp horizon@.service /etc/systemd/system/horizon@.service
sudo cp horizon.target /etc/systemd/system/horizon.target
sudo systemctl daemon-reload
sudo systemctl enable --now horizon.target
sudo systemctl enable --now horizon@notifications.service
sudo systemctl enable --now horizon@media.service
```

Per-queue settings (min/max processes, balancing strategy) are configured via
the environment files managed by the autoscaler. For manual overrides create or
edit `/etc/academy/horizon/<queue>.env` and then run:

```bash
sudo systemctl reload horizon@<queue>.service
```

The template issues graceful TERM signals on reload/stop so deployments can
roll without killing in-flight jobs. Health checks and metrics are exported via
Horizon for Prometheus to ingest.
