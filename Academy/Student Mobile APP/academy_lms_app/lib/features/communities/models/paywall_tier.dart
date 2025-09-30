import 'package:meta/meta.dart';

@immutable
class PaywallTier {
  const PaywallTier({
    required this.id,
    required this.name,
    required this.description,
    required this.priceCurrency,
    required this.priceAmount,
    required this.interval,
    required this.trialDays,
    required this.benefits,
    required this.isDefault,
  });

  factory PaywallTier.fromJson(Map<String, dynamic> json) {
    return PaywallTier(
      id: json['id'] as int? ?? 0,
      name: json['name'] as String? ?? '',
      description: json['description'] as String? ?? '',
      priceCurrency: json['price_currency'] as String? ?? 'USD',
      priceAmount: (json['price_amount'] as num?)?.toDouble() ?? 0,
      interval: json['interval'] as String? ?? 'monthly',
      trialDays: json['trial_days'] as int?,
      benefits: List<String>.from(json['benefits'] as List<dynamic>? ?? const <String>[]),
      isDefault: json['is_default'] as bool? ?? false,
    );
  }

  final int id;
  final String name;
  final String description;
  final String priceCurrency;
  final double priceAmount;
  final String interval;
  final int? trialDays;
  final List<String> benefits;
  final bool isDefault;
}
