# Security Scan Toolkit

The `security_scan.sh` helper consolidates the recurring security checks required for the Communities upgrade program.

## Prerequisites
- PHP 8.3+, Composer 2.7+
- Node.js 20+ and npm
- Flutter 3.22+
- (Optional) [Trivy](https://aquasecurity.github.io/trivy/v0.50/) for filesystem scanning

## Usage
```bash
cd Academy/tools/security
./security_scan.sh
```

The script performs the following steps:
1. Installs PHP dependencies for the Laravel application without running framework scripts.
2. Executes `composer audit --locked` to detect known vulnerabilities.
3. Runs Larastan (PHPStan) static analysis with the hardened `phpstan.neon.dist` profile.
4. Executes `npm ci` followed by a production-scoped `npm audit --omit=dev`.
5. Refreshes Flutter dependencies and reports outdated packages that require review for CVEs.
6. Invokes Trivy (if available) to scan the repository for HIGH/CRITICAL vulnerabilities.

All commands emit ISO-8601 timestamps so the resulting logs can be ingested by centralized monitoring during scheduled security windows.
