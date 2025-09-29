import 'package:logging/logging.dart';

import '../../features/communities/models/community_notification.dart';
import 'deep_link_handler.dart';

class PushNotificationRouter {
  PushNotificationRouter({required DeepLinkHandler deepLinkHandler, Logger? logger})
      : _deepLinkHandler = deepLinkHandler,
        _logger = logger ?? Logger('PushNotificationRouter');

  final DeepLinkHandler _deepLinkHandler;
  final Logger _logger;

  Future<void> handle(Map<String, dynamic> payload) async {
    final message = CommunityNotification.fromJson(payload);
    _logger.info('Handling push notification', message.event);

    final cta = message.data['cta'] as Map<String, dynamic>?;
    final deepLink = cta != null ? cta['deep_link'] as String? : null;

    if (deepLink != null && deepLink.isNotEmpty) {
      await _deepLinkHandler.open(deepLink);
      return;
    }

    final url = cta != null ? cta['url'] as String? : null;
    if (url != null && url.isNotEmpty) {
      await _deepLinkHandler.open(url);
    }
  }
}
