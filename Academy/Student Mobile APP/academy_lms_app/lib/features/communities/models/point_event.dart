import 'package:meta/meta.dart';

@immutable
class PointEvent {
  const PointEvent({
    required this.id,
    required this.occurredAt,
    required this.amount,
    required this.reason,
    required this.metadata,
  });

  factory PointEvent.fromJson(Map<String, dynamic> json) {
    final rawMetadata = json['metadata'];
    final metadata = <String, dynamic>{};
    if (rawMetadata is Map<String, dynamic>) {
      metadata.addAll(rawMetadata);
    }

    return PointEvent(
      id: json['id'] as String? ?? '',
      occurredAt: DateTime.tryParse(json['occurred_at'] as String? ?? '') ?? DateTime.now(),
      amount: json['amount'] as int? ?? 0,
      reason: json['reason'] as String? ?? 'points.awarded',
      metadata: metadata,
    );
  }

  final String id;
  final DateTime occurredAt;
  final int amount;
  final String reason;
  final Map<String, dynamic> metadata;
}
