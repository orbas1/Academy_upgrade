import 'dart:async';

import 'package:firebase_analytics/firebase_analytics.dart';
import 'package:firebase_core/firebase_core.dart';
import 'package:flutter/foundation.dart';

import '../../config/app_configuration.dart';

/// Thin wrapper around Firebase Analytics to ensure the SDK is only initialised
/// when explicitly enabled for the current environment.
class MobileAnalyticsService {
  MobileAnalyticsService._();

  static final MobileAnalyticsService instance = MobileAnalyticsService._();

  FirebaseAnalytics? _analytics;
  bool _initialised = false;

  bool get isEnabled => _analytics != null;

  Future<void> ensureInitialised() async {
    if (_initialised) {
      return;
    }
    _initialised = true;

    final config = AppConfiguration.instance;
    if (!config.analyticsEnabled) {
      debugPrint('Analytics disabled for this build.');
      return;
    }

    try {
      if (Firebase.apps.isEmpty) {
        await Firebase.initializeApp();
      }
      _analytics = FirebaseAnalytics.instance;
      await _analytics?.setAnalyticsCollectionEnabled(true);
    } catch (error, stackTrace) {
      debugPrint('Failed to initialise Firebase Analytics: $error');
      debugPrint('$stackTrace');
      _analytics = null;
    }
  }

  Future<void> identifyUser(String userId, {Map<String, String>? properties}) async {
    await ensureInitialised();
    if (_analytics == null) {
      return;
    }

    await _analytics!.setUserId(id: userId);
    if (properties != null) {
      for (final entry in properties.entries) {
        await _analytics!.setUserProperty(name: entry.key, value: entry.value);
      }
    }
  }

  Future<void> clearUser() async {
    await ensureInitialised();
    if (_analytics == null) {
      return;
    }

    await _analytics!.setUserId(id: null);
  }

  Future<void> logEvent(String name, Map<String, Object?> parameters) async {
    await ensureInitialised();
    if (_analytics == null) {
      return;
    }

    await _analytics!.logEvent(name: name, parameters: parameters);
  }
}
