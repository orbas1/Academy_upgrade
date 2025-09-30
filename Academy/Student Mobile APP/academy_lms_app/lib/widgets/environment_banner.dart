import 'package:flutter/material.dart';

import '../config/app_configuration.dart';

class EnvironmentBanner extends StatelessWidget {
  const EnvironmentBanner({super.key, required this.child, this.environment, this.location});

  final Widget child;
  final String? environment;
  final BannerLocation? location;

  bool get _isProduction {
    final env = (environment ?? AppConfiguration.instance.environment).toLowerCase();
    return env == 'prod' || env == 'production';
  }

  String get _label {
    final env = (environment ?? AppConfiguration.instance.environment).trim();
    if (env.isEmpty) {
      return 'DEV';
    }
    return env.toUpperCase();
  }

  @override
  Widget build(BuildContext context) {
    if (_isProduction) {
      return child;
    }

    return Banner(
      message: _label,
      location: location ?? BannerLocation.topStart,
      color: Colors.orange.shade700,
      textStyle: const TextStyle(
        fontWeight: FontWeight.w600,
        fontSize: 10,
        letterSpacing: 0.5,
      ),
      child: child,
    );
  }

  static Widget wrapApp(Widget child) {
    return EnvironmentBanner(child: child);
  }
}
