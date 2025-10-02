import 'package:flutter/foundation.dart';

import '../../config/app_configuration.dart';

/// Provides HTTP headers that allow the backend to tailor security profiles
/// for the calling client. The identifiers must remain stable so middleware can
/// safely negotiate Content-Security-Policy variants.
class ClientIdentity {
  const ClientIdentity._();

  static const String clientHeader = 'X-Orbas-Client';

  /// Build the platform descriptor and attach the configured version/env data.
  static Map<String, String> headers() {
    final AppConfiguration configuration = AppConfiguration.instance;
    final String platform = _resolvePlatform();
    final String version = configuration.appVersion;
    final String environment = configuration.environment;

    final String descriptor = 'mobile-app/$platform; version=$version; env=$environment';
    final String userAgent = 'OrbasLearn/$version ($platform; $environment)';

    return <String, String>{
      clientHeader: descriptor,
      'User-Agent': userAgent,
    };
  }

  static String _resolvePlatform() {
    if (kIsWeb) {
      return 'web';
    }

    switch (defaultTargetPlatform) {
      case TargetPlatform.android:
        return 'android';
      case TargetPlatform.iOS:
        return 'ios';
      case TargetPlatform.macOS:
        return 'macos';
      case TargetPlatform.windows:
        return 'windows';
      case TargetPlatform.linux:
        return 'linux';
      case TargetPlatform.fuchsia:
        return 'fuchsia';
    }
  }
}
