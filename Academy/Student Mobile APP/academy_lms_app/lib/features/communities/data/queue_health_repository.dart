import 'dart:async';

import '../models/queue_health_metric.dart';
import 'queue_health_api_service.dart';

class QueueHealthRepository {
  QueueHealthRepository({QueueHealthApiService? api})
      : _api = api ?? QueueHealthApiService();

  final QueueHealthApiService _api;
  QueueHealthSummary? _cache;
  DateTime? _lastFetched;

  void updateAuthToken(String? token) {
    _api.updateAuthToken(token);
  }

  Future<QueueHealthSummary> loadSummary({Duration cacheDuration = const Duration(minutes: 2)}) async {
    final now = DateTime.now();
    if (_cache != null && _lastFetched != null) {
      final elapsed = now.difference(_lastFetched!);
      if (elapsed <= cacheDuration) {
        return _cache!;
      }
    }

    final summary = await _api.fetchSummary();
    _cache = summary;
    _lastFetched = now;
    return summary;
  }

  Future<String?> loadWarningForQueue(String queueName) async {
    final summary = await loadSummary();
    return summary.warningForQueue(queueName);
  }

  void dispose() {
    _api.dispose();
  }
}
