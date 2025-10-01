import 'dart:math';

import 'package:academy_lms_app/features/communities/data/community_api_service.dart';
import 'package:academy_lms_app/features/communities/data/community_cache.dart';
import 'package:academy_lms_app/features/communities/data/community_repository.dart';
import 'package:academy_lms_app/features/communities/data/paginated_response.dart';
import 'package:academy_lms_app/features/communities/data/testing/profile_activity_load_driver.dart';
import 'package:academy_lms_app/features/communities/models/profile_activity.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  group('ProfileActivityLoadDriver', () {
    test('captures latency percentiles and throughput during success run', () async {
      final api = _DeterministicProfileActivityApi(
        latency: const Duration(milliseconds: 12),
      );
      final repository = CommunityRepository(api: api, cache: _NoOpCommunityCache());
      final driver = ProfileActivityLoadDriver(
        repository: repository,
        concurrency: 3,
        iterationsPerWorker: 4,
        pageSize: 5,
      );

      final summary = await driver.run();

      expect(summary.totalRequests, 12);
      expect(summary.failureCount, 0);
      expect(summary.successCount, 12);
      expect(summary.averageLatency, greaterThan(Duration.zero));
      expect(summary.p95Latency, greaterThan(Duration.zero));
      expect(summary.p99Latency, greaterThan(Duration.zero));
      expect(summary.throughputPerMinute, greaterThan(0));
      expect(summary.successRate, equals(1.0));

      final payload = summary.toJson();
      expect(payload['total_requests'], equals(summary.totalRequests));
      expect(payload['failure_count'], equals(0));
      expect(payload['failures'], isEmpty);
    });

    test('records failures with metadata when repository throws', () async {
      final api = _DeterministicProfileActivityApi(
        latency: const Duration(milliseconds: 8),
        failingIterations: {3, 7},
      );
      final repository = CommunityRepository(api: api, cache: _NoOpCommunityCache());
      final driver = ProfileActivityLoadDriver(
        repository: repository,
        concurrency: 2,
        iterationsPerWorker: 4,
        pageSize: 3,
        delayBetweenIterations: const Duration(milliseconds: 2),
      );

      final summary = await driver.run();

      expect(summary.totalRequests, 8);
      expect(summary.failureCount, 2);
      expect(summary.successCount, 6);
      expect(summary.failures, hasLength(2));
      expect(summary.failures.first.error.toString(), contains('boom'));
      expect(summary.successRate, closeTo(0.75, 0.0001));
      expect(summary.p95Latency, greaterThan(Duration.zero));
    });
  });
}

class _DeterministicProfileActivityApi extends CommunityApiService {
  _DeterministicProfileActivityApi({
    required this.latency,
    Set<int>? failingIterations,
  })  : failingIterations = failingIterations ?? <int>{},
        _iterations = 0,
        super(client: MockClient((http.Request _) async => http.Response('{}', 200)));

  final Duration latency;
  final Set<int> failingIterations;
  int _iterations;

  @override
  Future<PaginatedResponse<ProfileActivity>> fetchProfileActivity({
    int? communityId,
    String? cursor,
    int pageSize = 50,
  }) async {
    _iterations += 1;
    await Future<void>.delayed(latency);

    if (failingIterations.contains(_iterations)) {
      throw StateError('boom $_iterations');
    }

    return PaginatedResponse<ProfileActivity>(
      items: List<ProfileActivity>.generate(
        min(pageSize, 2),
        (index) => ProfileActivity.fromJson(<String, dynamic>{
          'id': (_iterations * 1000) + index,
          'activity_type': 'community_post.published',
          'subject_type': 'community_post',
          'subject_id': (_iterations * 1000) + index,
          'occurred_at': DateTime.now().toUtc().toIso8601String(),
          'context': <String, dynamic>{'post_id': (_iterations * 1000) + index},
          'community': <String, dynamic>{
            'id': communityId ?? 99,
            'name': 'Load Harness',
            'slug': 'load-harness',
          },
        }),
      ),
      nextCursor: null,
      hasMore: false,
    );
  }
}

class _NoOpCommunityCache extends CommunityCache {
  @override
  Future<void> writeCommunityList(String filter, PaginatedResponse<dynamic> response) async {}

  @override
  Future<void> writeCommunityFeed(int communityId, PaginatedResponse<dynamic> response, [String filter = 'new']) async {}

  @override
  Future<PaginatedResponse<dynamic>?> readCommunityList(String filter) async => null;

  @override
  Future<PaginatedResponse<dynamic>?> readCommunityFeed(int communityId, [String filter = 'new']) async => null;

  @override
  Future<void> clear() async {}
}
