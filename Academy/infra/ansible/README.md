# Ansible Automation for Edge & Worker Infrastructure

This playbook bundle automates the DevOps scope defined for Stage 10 of the
Academy upgrade programme. It provisions hardened Nginx ingress nodes with
ModSecurity, deploys the shared security header snippets shipped in this
repository, and manages Laravel Horizon worker units with graceful rolling
updates.

## Structure

- `playbooks/nginx-edge.yml` – entry playbook configuring the edge nodes with
  ModSecurity, HTTP/2 TLS, Brotli, and CDN-aware logging.
- `roles/nginx` – role encapsulating package installation, rule deployment, and
  configuration templating for Nginx and ModSecurity.

## Usage

```bash
ansible-playbook \
  -i inventories/prod.ini \
  playbooks/nginx-edge.yml \
  --extra-vars '@vars/prod.yml'
```

Expected variables (can be supplied via inventory, vault, or `--extra-vars`):

- `nginx_domain` – primary host name for TLS certificates.
- `nginx_upstream_socket` or `nginx_upstream_servers` – location(s) of PHP-FPM
  backends.
- `modsecurity_crs_version` – OWASP Core Rule Set release to install.
- `enable_brotli` – toggles Brotli compression module.

The role copies the hardened `security-headers.conf` and `academy_communities`
virtual host templates from the repository, enabling deterministic, versioned
edge configuration across environments.
