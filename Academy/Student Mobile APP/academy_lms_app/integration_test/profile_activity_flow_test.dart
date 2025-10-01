import 'dart:convert';

import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:integration_test/integration_test.dart';
import 'package:provider/provider.dart';
import 'package:shared_preferences/shared_preferences.dart';

import 'package:academy_lms_app/features/communities/data/community_repository.dart';
import 'package:academy_lms_app/features/communities/data/testing/in_memory_community_api_service.dart';
import 'package:academy_lms_app/features/communities/models/profile_activity.dart';
import 'package:academy_lms_app/features/communities/state/community_notifier.dart';
import 'package:academy_lms_app/screens/account.dart';
import 'package:academy_lms_app/features/communities/data/community_cache.dart';
import 'package:academy_lms_app/features/communities/data/queue_health_repository.dart';
import 'package:academy_lms_app/features/communities/data/offline_action_queue.dart';
import 'package:academy_lms_app/services/community_manifest_service.dart';
import 'package:academy_lms_app/features/communities/data/paginated_response.dart';
import 'package:academy_lms_app/features/communities/models/community_summary.dart';
import 'package:academy_lms_app/features/communities/models/community_feed_item.dart';
import 'package:academy_lms_app/features/communities/data/queue_health_api_service.dart';
import 'package:academy_lms_app/features/communities/models/queue_health_metric.dart';
import 'package:http/http.dart' as http;

void main() {
  IntegrationTestWidgetsFlutterBinding.ensureInitialized();

  group('Profile activity end-to-end smoke test', () {
    late CommunityNotifier notifier;

    setUp(() async {
      SharedPreferences.setMockInitialValues({
        'user': jsonEncode(<String, Object?>{
          'name': 'Harness Member',
          'phone': '+1 555 0100',
          'photo': null,
        }),
      });

      final activities = <ProfileActivity>[
        ProfileActivity.fromJson({
          'id': 1,
          'activity_type': 'community_post.published',
          'subject_type': 'community_post',
          'subject_id': 501,
          'occurred_at': '2025-03-01T12:00:00Z',
          'community': {
            'id': 77,
            'name': 'Flow Harness Guild',
            'slug': 'flow-harness-guild',
          },
          'context': {'title': 'We are live'},
        }),
        ProfileActivity.fromJson({
          'id': 2,
          'activity_type': 'community_comment.posted',
          'subject_type': 'community_comment',
          'subject_id': 845,
          'occurred_at': '2025-03-02T09:20:00Z',
          'community': {
            'id': 77,
            'name': 'Flow Harness Guild',
            'slug': 'flow-harness-guild',
          },
          'context': {'excerpt': 'Congrats on the launch!'},
        }),
        ProfileActivity.fromJson({
          'id': 3,
          'activity_type': 'course.completed',
          'subject_type': 'course',
          'subject_id': 1201,
          'occurred_at': '2025-03-03T08:00:00Z',
          'context': {'course_title': 'Community Launchpad'},
        }),
        ProfileActivity.fromJson({
          'id': 4,
          'activity_type': 'community_post.published',
          'subject_type': 'community_post',
          'subject_id': 902,
          'occurred_at': '2025-03-04T16:45:00Z',
          'community': {
            'id': 88,
            'name': 'Product Strategy Circle',
            'slug': 'product-strategy-circle',
          },
          'context': {'title': 'Paywall preview'},
        }),
        ProfileActivity.fromJson({
          'id': 5,
          'activity_type': 'community_comment.posted',
          'subject_type': 'community_comment',
          'subject_id': 903,
          'occurred_at': '2025-03-04T17:15:00Z',
          'community': {
            'id': 88,
            'name': 'Product Strategy Circle',
            'slug': 'product-strategy-circle',
          },
          'context': {'excerpt': 'Shared the leaderboard snapshot'},
        }),
      ];

      final api = InMemoryCommunityApiService(
        activitiesByCommunity: <int?, List<ProfileActivity>>{
          null: activities,
          77: activities.take(3).toList(growable: false),
        },
      );

      notifier = CommunityNotifier(
        repository: CommunityRepository(
          api: api,
          cache: _NullCommunityCache(),
        ),
        queueHealthRepository: _StubQueueHealthRepository(),
        manifestService: _StaticManifestService(),
        offlineQueue: _InMemoryOfflineQueue(),
      );
    });

    tearDown(() {
      notifier.dispose();
    });

    testWidgets('renders and paginates recent contributions', (WidgetTester tester) async {
      await tester.pumpWidget(
        ChangeNotifierProvider<CommunityNotifier>.value(
          value: notifier,
          child: const MaterialApp(home: AccountScreen()),
        ),
      );

      await tester.pumpAndSettle(const Duration(seconds: 1));

      expect(find.text('Recent contributions'), findsOneWidget);
      expect(find.textContaining('Published a post'), findsWidgets);
      expect(find.textContaining('Replied to a discussion'), findsWidgets);
      expect(find.textContaining('Completed a course'), findsOneWidget);
      expect(find.text('Load more'), findsOneWidget);
      expect(find.textContaining('2 more recorded'), findsOneWidget);

      await tester.tap(find.text('Load more'));
      await tester.pump();
      await tester.pumpAndSettle(const Duration(milliseconds: 400));

      expect(find.text('Load more'), findsNothing);
      expect(find.textContaining('2 more recorded'), findsNothing);
      expect(notifier.profileActivity.length, equals(5));
      expect(notifier.canLoadMoreProfileActivity, isFalse);
    });
  });
}

class _NullCommunityCache extends CommunityCache {
  _NullCommunityCache()
      : super(
          databaseBuilder: () async =>
              throw UnsupportedError('Cache not available in integration tests.'),
        );

  @override
  Future<void> writeCommunityList(
    String filter,
    PaginatedResponse<CommunitySummary> response,
  ) async {}

  @override
  Future<void> writeCommunityFeed(
    int communityId,
    String filter,
    PaginatedResponse<CommunityFeedItem> response,
  ) async {}

  @override
  Future<PaginatedResponse<CommunitySummary>?> readCommunityList(String filter) async {
    return null;
  }

  @override
  Future<PaginatedResponse<CommunityFeedItem>?> readCommunityFeed(
    int communityId,
    String filter,
  ) async {
    return null;
  }

  @override
  Future<void> clear() async {}

  @override
  Future<void> close() async {}
}

class _StubQueueHealthRepository extends QueueHealthRepository {
  _StubQueueHealthRepository() : super(api: _StubQueueHealthApi());
}

class _StubQueueHealthApi extends QueueHealthApiService {
  _StubQueueHealthApi() : super(client: _FailingHttpClient());

  @override
  Future<QueueHealthSummary> fetchSummary() async {
    return const QueueHealthSummary(metrics: <QueueHealthMetric>[]);
  }
}

class _StaticManifestService extends CommunityManifestService {
  _StaticManifestService() : super(client: _FailingHttpClient());

  @override
  Future<CommunityModuleManifest> fetch({String? bearerToken}) async {
    return CommunityModuleManifest(
      version: '2025.03',
      generatedAt: DateTime.now().toIso8601String(),
      modules: const <CommunityModuleDescriptor>[],
      apiBaseUrl: 'https://example.test',
    );
  }
}

class _InMemoryOfflineQueue extends OfflineCommunityActionQueue {
  _InMemoryOfflineQueue() : super(databaseBuilder: () async => throw UnsupportedError('Offline queue disabled.'));

  @override
  Future<int> enqueue(CommunityOfflineAction action) async => 1;

  @override
  Future<int> pendingCount() async => 0;

  @override
  Future<CommunityOfflineProcessReport> process({
    required Future<void> Function(QueuedCommunityAction action) handler,
    int maxAttempts = 5,
    int batchSize = 10,
  }) async {
    return const CommunityOfflineProcessReport.empty();
  }
}

class _FailingHttpClient extends http.BaseClient {
  @override
  Future<http.StreamedResponse> send(http.BaseRequest request) {
    throw UnsupportedError('Network calls are disabled in integration harness.');
  }
}
