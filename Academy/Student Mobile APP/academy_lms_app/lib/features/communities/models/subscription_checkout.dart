import 'package:meta/meta.dart';

@immutable
class SubscriptionCheckout {
  const SubscriptionCheckout({
    required this.sessionId,
    required this.checkoutUrl,
  });

  factory SubscriptionCheckout.fromJson(Map<String, dynamic> json) {
    return SubscriptionCheckout(
      sessionId: json['session_id'] as String? ?? '',
      checkoutUrl: json['checkout_url'] as String? ?? '',
    );
  }

  final String sessionId;
  final String checkoutUrl;
}
