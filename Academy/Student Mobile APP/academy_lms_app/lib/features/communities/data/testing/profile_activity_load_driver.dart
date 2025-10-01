import 'dart:async';
import 'dart:math';

import 'package:academy_lms_app/features/communities/data/community_repository.dart';

class ProfileActivityLoadDriver {
  ProfileActivityLoadDriver({
    required this.repository,
    this.communityId,
    this.concurrency = 8,
    this.iterationsPerWorker = 20,
    this.pageSize = 50,
    this.delayBetweenIterations = Duration.zero,
    this.resetBetweenIterations = true,
  })  : assert(concurrency > 0, 'concurrency must be positive'),
        assert(iterationsPerWorker > 0, 'iterationsPerWorker must be positive'),
        assert(pageSize > 0, 'pageSize must be positive');

  final CommunityRepository repository;
  final int? communityId;
  final int concurrency;
  final int iterationsPerWorker;
  final int pageSize;
  final Duration delayBetweenIterations;
  final bool resetBetweenIterations;

  Future<ProfileActivityLoadSummary> run() async {
    final startedAt = DateTime.now().toUtc();
    final successes = <_IterationRecord>[];
    final failures = <ProfileActivityLoadFailure>[];

    final workers = <Future<void>>[];
    for (var workerIndex = 0; workerIndex < concurrency; workerIndex += 1) {
      workers.add(_runWorker(workerIndex, successes, failures));
    }

    await Future.wait(workers);

    final completedAt = DateTime.now().toUtc();
    successes.sort((a, b) => a.duration.compareTo(b.duration));

    return ProfileActivityLoadSummary(
      startedAt: startedAt,
      completedAt: completedAt,
      successes: successes,
      failures: failures,
    );
  }

  Future<void> _runWorker(
    int workerIndex,
    List<_IterationRecord> successes,
    List<ProfileActivityLoadFailure> failures,
  ) async {
    String? cursor;
    for (var iteration = 0; iteration < iterationsPerWorker; iteration += 1) {
      final shouldReset = resetBetweenIterations || cursor == null;
      final stopwatch = Stopwatch()..start();

      try {
        final response = await repository.loadProfileActivity(
          communityId: communityId,
          resetCursor: shouldReset,
          pageSize: pageSize,
        );
        stopwatch.stop();

        successes.add(
          _IterationRecord(
            workerIndex: workerIndex,
            iteration: iteration,
            duration: stopwatch.elapsed,
            itemCount: response.items.length,
          ),
        );

        cursor = response.nextCursor;
      } catch (error, stackTrace) {
        stopwatch.stop();
        failures.add(
          ProfileActivityLoadFailure(
            workerIndex: workerIndex,
            iteration: iteration,
            duration: stopwatch.elapsed,
            error: error,
            stackTrace: stackTrace,
          ),
        );
        cursor = null;
      }

      if (delayBetweenIterations > Duration.zero) {
        await Future<void>.delayed(delayBetweenIterations);
      }
    }
  }
}

class ProfileActivityLoadSummary {
  ProfileActivityLoadSummary({
    required this.startedAt,
    required this.completedAt,
    required List<_IterationRecord> successes,
    required this.failures,
  })  : totalRequests = successes.length + failures.length,
        successCount = successes.length,
        failureCount = failures.length,
        _successDurations = successes.map((record) => record.duration).toList(growable: false);

  final DateTime startedAt;
  final DateTime completedAt;
  final int totalRequests;
  final int successCount;
  final int failureCount;
  final List<ProfileActivityLoadFailure> failures;
  final List<Duration> _successDurations;

  Duration get totalDuration => completedAt.difference(startedAt);

  Duration get averageLatency {
    if (_successDurations.isEmpty) {
      return Duration.zero;
    }

    final totalMicros = _successDurations.fold<int>(
      0,
      (value, element) => value + element.inMicroseconds,
    );

    return Duration(microseconds: totalMicros ~/ _successDurations.length);
  }

  Duration get p95Latency => _percentile(_successDurations, 0.95);

  Duration get p99Latency => _percentile(_successDurations, 0.99);

  double get throughputPerMinute {
    final seconds = totalDuration.inMicroseconds / Duration.microsecondsPerSecond;
    if (seconds <= 0) {
      return successCount * 60.0;
    }

    return (successCount / seconds) * 60.0;
  }

  double get successRate => totalRequests == 0 ? 0 : successCount / totalRequests;

  Map<String, dynamic> toJson() => <String, dynamic>{
        'started_at': startedAt.toIso8601String(),
        'completed_at': completedAt.toIso8601String(),
        'total_requests': totalRequests,
        'success_count': successCount,
        'failure_count': failureCount,
        'average_latency_ms': averageLatency.inMicroseconds / 1000,
        'p95_latency_ms': p95Latency.inMicroseconds / 1000,
        'p99_latency_ms': p99Latency.inMicroseconds / 1000,
        'throughput_per_minute': throughputPerMinute,
        'success_rate': successRate,
        'failures': failures
            .map(
              (failure) => failure.toJson(),
            )
            .toList(),
      };
}

class ProfileActivityLoadFailure {
  ProfileActivityLoadFailure({
    required this.workerIndex,
    required this.iteration,
    required this.duration,
    required this.error,
    required this.stackTrace,
  });

  final int workerIndex;
  final int iteration;
  final Duration duration;
  final Object error;
  final StackTrace stackTrace;

  Map<String, dynamic> toJson() => <String, dynamic>{
        'worker_index': workerIndex,
        'iteration': iteration,
        'duration_ms': duration.inMicroseconds / 1000,
        'error': error.toString(),
        'stack_trace': stackTrace.toString(),
      };
}

class _IterationRecord {
  _IterationRecord({
    required this.workerIndex,
    required this.iteration,
    required this.duration,
    required this.itemCount,
  });

  final int workerIndex;
  final int iteration;
  final Duration duration;
  final int itemCount;
}

Duration _percentile(List<Duration> values, double percentile) {
  if (values.isEmpty) {
    return Duration.zero;
  }

  final sorted = values
      .map((value) => value.inMicroseconds)
      .toList(growable: false)
    ..sort();

  final rank = max(0, (percentile * (sorted.length - 1)).round());
  final index = min(rank, sorted.length - 1);

  return Duration(microseconds: sorted[index]);
}
