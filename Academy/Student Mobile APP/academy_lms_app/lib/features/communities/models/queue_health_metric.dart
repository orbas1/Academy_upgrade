class QueueHealthMetric {
  const QueueHealthMetric({
    required this.queue,
    required this.status,
    this.publicMessage,
    this.alerts = const <String>[],
    this.metrics,
  });

  factory QueueHealthMetric.fromJson(Map<String, dynamic> json) {
    final alerts = (json['alerts'] as List<dynamic>?)
            ?.map((dynamic value) => value.toString())
            .toList(growable: false) ??
        const <String>[];

    final metrics = json['metrics'] as Map<String, dynamic>?;

    return QueueHealthMetric(
      queue: json['queue']?.toString() ?? 'default',
      status: json['status']?.toString() ?? 'unknown',
      publicMessage: json['public_message']?.toString(),
      alerts: alerts,
      metrics: metrics == null ? null : Map<String, dynamic>.from(metrics),
    );
  }

  final String queue;
  final String status;
  final String? publicMessage;
  final List<String> alerts;
  final Map<String, dynamic>? metrics;

  bool get isDegraded => status.toLowerCase() == 'degraded';

  String? get warningCopy {
    if (!isDegraded) {
      return null;
    }

    if (publicMessage != null && publicMessage!.trim().isNotEmpty) {
      return publicMessage;
    }

    if (alerts.isNotEmpty) {
      return alerts.first;
    }

    return 'Background processing is currently delayed. Some updates may take longer than usual.';
  }
}

class QueueHealthSummary {
  const QueueHealthSummary({required this.metrics});

  factory QueueHealthSummary.fromJson(Map<String, dynamic> json) {
    final data = json['data'] as List<dynamic>? ?? const <dynamic>[];

    return QueueHealthSummary(
      metrics: data
          .map((dynamic item) => QueueHealthMetric.fromJson(
                Map<String, dynamic>.from(item as Map),
              ))
          .toList(growable: false),
    );
  }

  final List<QueueHealthMetric> metrics;

  QueueHealthMetric? metricForQueue(String queueName) {
    for (final metric in metrics) {
      if (metric.queue == queueName) {
        return metric;
      }
    }
    return null;
  }

  String? warningForQueue(String queueName) {
    final metric = metricForQueue(queueName);
    return metric?.warningCopy;
  }
}
