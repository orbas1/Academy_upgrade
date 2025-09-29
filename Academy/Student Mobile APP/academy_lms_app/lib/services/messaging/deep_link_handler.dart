import 'package:flutter/widgets.dart';
import 'package:logging/logging.dart';

class DeepLinkHandler {
  DeepLinkHandler({required GlobalKey<NavigatorState> navigatorKey})
      : _navigatorKey = navigatorKey;

  final GlobalKey<NavigatorState> _navigatorKey;
  final Logger _logger = Logger('DeepLinkHandler');

  Future<void> open(String url) async {
    final navigator = _navigatorKey.currentState;
    if (navigator == null) {
      _logger.warning('Navigator not ready for deep link', url);
      return;
    }

    if (url.startsWith('http')) {
      // Fallback to web view or external browser – placeholder.
      _logger.info('Received web URL for deep link', url);
      return;
    }

    if (url.contains('/communities/')) {
      final segments = url.split('/');
      if (segments.length >= 3) {
        final communityId = int.tryParse(segments[2]);
        if (communityId != null) {
          _logger.info('Deep link to community', communityId);
          navigator.pushNamed(
            '/home',
            arguments: <String, dynamic>{'communityId': communityId},
          );
        }
      }
    }
  }
}
