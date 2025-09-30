import 'package:flutter_test/flutter_test.dart';

import 'package:academy_lms_app/services/realtime/realtime_presence_service.dart';

void main() {
  group('RealtimePresenceConfig', () {
    test('applies exponential backoff with upper bound', () {
      const config = RealtimePresenceConfig(
        socketUrl: Uri.parse('wss://example.com/realtime'),
        reconnectBaseDelay: Duration(seconds: 1),
        reconnectMaxDelay: Duration(seconds: 10),
      );

      expect(config.backoffForAttempt(1), const Duration(seconds: 1));
      expect(config.backoffForAttempt(2), const Duration(seconds: 2));
      expect(config.backoffForAttempt(3), const Duration(seconds: 4));
      expect(config.backoffForAttempt(4), const Duration(seconds: 8));
      expect(config.backoffForAttempt(5), const Duration(seconds: 10));
      expect(config.backoffForAttempt(10), const Duration(seconds: 10));
    });
  });
}
