import 'package:meta/meta.dart';

import 'paywall_tier.dart';

@immutable
class SubscriptionStatus {
  const SubscriptionStatus({
    required this.status,
    required this.currentPeriodEnd,
    required this.tier,
    required this.entitlements,
  });

  factory SubscriptionStatus.fromJson(Map<String, dynamic> json) {
    return SubscriptionStatus(
      status: json['status'] as String? ?? 'none',
      currentPeriodEnd: DateTime.tryParse(json['current_period_end'] as String? ?? ''),
      tier: json['tier'] is Map<String, dynamic>
          ? PaywallTier.fromJson(json['tier'] as Map<String, dynamic>)
          : null,
      entitlements: List<String>.from(json['entitlements'] as List<dynamic>? ?? const <String>[]),
    );
  }

  final String status;
  final DateTime? currentPeriodEnd;
  final PaywallTier? tier;
  final List<String> entitlements;

  bool get isActive => status == 'active' || status == 'trialing';
}
