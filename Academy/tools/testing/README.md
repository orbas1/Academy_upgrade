# Testing Orchestration Utilities

The `run_full_test_suite.sh` script centralizes all automated checks that exist in the repository so you can surface issues with one command.

## Usage

```bash
./tools/testing/run_full_test_suite.sh
```

The script detects the Laravel web application and the Flutter mobile client automatically. It will:

1. Bootstrap dependencies (Composer or Flutter) when they have not been installed yet.
2. Run the Laravel PHPUnit suite, PHPStan static analysis, and Laravel Pint formatting checks.
3. Run `flutter analyze` and `flutter test` for the mobile application.

Each step streams output to your terminal and simultaneously writes a timestamped log file under `tools/testing/logs/`. The summary printed at the end includes direct paths to the captured logs.

If a required command (for example, `flutter`) is not available on your machine the step is skipped with a warning while the rest of the checks continue to run.
