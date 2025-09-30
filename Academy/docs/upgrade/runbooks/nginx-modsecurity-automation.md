# Nginx & ModSecurity Automation Runbook

This document outlines how to deploy the hardened Nginx edge configuration via
the Ansible role added for Stage 10.

## Requirements

- Ansible 2.14+
- SSH access to the edge hosts (Ubuntu 22.04 LTS)
- sudo privileges on the target hosts

## Execution

1. Populate `infra/ansible/inventories/<env>.ini` with the edge hosts under the
   `edge` group.
2. Define TLS certificate paths and upstream configuration using
   `infra/ansible/vars/<env>.yml` or the inventory `host_vars`.
3. Run the playbook:

```bash
ansible-playbook -i inventories/prod.ini playbooks/nginx-edge.yml
```

## Post-Deployment Validation

- `curl -I https://<domain>` – verify security headers, HSTS, and TLS version.
- `sudo nginx -t` – confirm syntax.
- `sudo tail -f /var/log/modsec_audit.log` – ensure ModSecurity logging.
- Use `tools/security/edge_smoke_test.sh` (see security tooling directory) to
  execute automated header and rate-limit probes.

## Updating CRS Rules

To bump the OWASP Core Rule Set version, update the `modsecurity_crs_version`
variable and re-run the playbook. The role downloads and symlinks the specified
release so rollbacks simply involve switching the variable back and re-running
Ansible.
