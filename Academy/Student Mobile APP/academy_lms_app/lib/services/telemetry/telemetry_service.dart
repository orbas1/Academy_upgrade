import 'dart:async';

import 'package:flutter/foundation.dart';
import 'package:flutter/widgets.dart';
import 'package:sentry_flutter/sentry_flutter.dart';

/// Centralized telemetry orchestration for crash reporting, breadcrumbs, and
/// navigation tracing. Wraps Sentry but allows the app to run without a DSN.
class TelemetryService {
  TelemetryService._();

  static final TelemetryService instance = TelemetryService._();

  final List<NavigatorObserver> _navigatorObservers = <NavigatorObserver>[];
  bool _sentryEnabled = false;
  String _environment = 'development';

  List<NavigatorObserver> get navigatorObservers =>
      List<NavigatorObserver>.unmodifiable(_navigatorObservers);

  bool get isSentryEnabled => _sentryEnabled;

  String get environment => _environment;

  set environment(String value) {
    _environment = value;
  }

  /// Initializes Sentry and runs the provided [runner] inside the configured
  /// error zone. When no DSN is supplied the runner executes without Sentry.
  Future<void> ensureInitialized({
    String? sentryDsn,
    Future<void> Function()? runner,
    double tracesSampleRate = 0.2,
  }) async {
    if (sentryDsn == null || sentryDsn.isEmpty) {
      _sentryEnabled = false;
      await runner?.call();
      return;
    }

    _sentryEnabled = true;
    await SentryFlutter.init(
      (options) {
        options.dsn = sentryDsn;
        options.tracesSampleRate = tracesSampleRate;
        options.environment = _environment;
        options.attachThreads = true;
        options.enableAutoNativeBreadcrumbs = true;
        options.enableAutoNativeCrashReporting = true;
      },
      appRunner: () async {
        _navigatorObservers.add(SentryNavigatorObserver());
        await runner?.call();
      },
    );
  }

  /// Records an exception with optional stack trace.
  Future<void> recordError(Object error, StackTrace stackTrace) async {
    if (_sentryEnabled) {
      await Sentry.captureException(error, stackTrace: stackTrace);
    } else {
      debugPrint('TelemetryService> $error');
      debugPrint('$stackTrace');
    }
  }

  /// Adds a breadcrumb for noteworthy events (network transitions, feature
  /// usage, etc.) so production triage has richer context.
  Future<void> addBreadcrumb({
    required String message,
    String category = 'app',
    SentryLevel level = SentryLevel.info,
    Map<String, Object?>? data,
  }) async {
    if (!_sentryEnabled) {
      return;
    }

    await Sentry.addBreadcrumb(
      Breadcrumb(
        message: message,
        level: level,
        category: category,
        data: data,
      ),
    );
  }

  /// Ensures graceful shutdown of telemetry resources.
  Future<void> close() async {
    if (_sentryEnabled) {
      await Sentry.close();
    }
  }
}
