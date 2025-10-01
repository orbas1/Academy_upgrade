import 'dart:collection';

import 'package:academy_lms_app/features/communities/data/community_api_service.dart';
import 'package:academy_lms_app/features/communities/data/community_cache.dart';
import 'package:academy_lms_app/features/communities/data/community_repository.dart';
import 'package:academy_lms_app/features/communities/data/errors.dart';
import 'package:academy_lms_app/features/communities/data/paginated_response.dart';
import 'package:academy_lms_app/features/communities/models/profile_activity.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  group('CommunityRepository profile activity', () {
    test('tracks cursors and availability toggles on success', () async {
      final responses = Queue<PaginatedResponse<ProfileActivity>>()
        ..add(_buildPage(startId: 9001, hasMore: true, cursor: 'cursor-a'))
        ..add(_buildPage(startId: 9002, hasMore: false, cursor: null));

      final api = _FakeProfileActivityApi(responses);
      final repository = CommunityRepository(api: api, cache: _NoOpCommunityCache());

      final firstPage = await repository.loadProfileActivity(resetCursor: true, pageSize: 1);

      expect(firstPage.items, hasLength(1));
      expect(firstPage.items.first.id, equals(9001));
      expect(firstPage.hasMore, isTrue);
      expect(repository.isProfileActivityAvailable(), isTrue);
      expect(repository.hasMoreProfileActivity(), isTrue);
      expect(api.calls, equals(1));

      final secondPage = await repository.loadMoreProfileActivity(pageSize: 1);
      expect(secondPage.items.first.id, equals(9002));
      expect(repository.hasMoreProfileActivity(), isFalse);
      expect(api.calls, equals(2));

      final exhausted = await repository.loadMoreProfileActivity(pageSize: 1);
      expect(exhausted.items, isEmpty);
      expect(api.calls, equals(2));
    });

    test('marks feature unavailable and rethrows when flag disabled', () async {
      final api = _UnavailableProfileActivityApi();
      final repository = CommunityRepository(api: api, cache: _NoOpCommunityCache());

      expect(
        () => repository.loadProfileActivity(resetCursor: true),
        throwsA(isA<FeatureUnavailableException>()),
      );

      expect(repository.isProfileActivityAvailable(), isFalse);
    });
  });
}

PaginatedResponse<ProfileActivity> _buildPage({
  required int startId,
  required bool hasMore,
  required String? cursor,
}) {
  final activity = ProfileActivity.fromJson(<String, dynamic>{
    'id': startId,
    'activity_type': 'community_post.published',
    'subject_type': 'community_post',
    'subject_id': startId,
    'occurred_at': DateTime.now().toIso8601String(),
    'community': <String, dynamic>{
      'id': 12,
      'name': 'Growth Lab',
      'slug': 'growth-lab',
    },
    'context': <String, dynamic>{'post_id': startId},
  });

  return PaginatedResponse<ProfileActivity>(
    items: <ProfileActivity>[activity],
    nextCursor: cursor,
    hasMore: hasMore,
  );
}

class _FakeProfileActivityApi extends CommunityApiService {
  _FakeProfileActivityApi(this._responses)
      : calls = 0,
        super(client: MockClient((http.Request _) async => http.Response('{}', 200)));

  final Queue<PaginatedResponse<ProfileActivity>> _responses;
  int calls;

  @override
  Future<PaginatedResponse<ProfileActivity>> fetchProfileActivity({
    int? communityId,
    String? cursor,
    int pageSize = 50,
  }) async {
    calls += 1;
    if (_responses.isEmpty) {
      return PaginatedResponse<ProfileActivity>.empty();
    }

    final next = _responses.removeFirst();
    return next;
  }
}

class _NoOpCommunityCache extends CommunityCache {}

class _UnavailableProfileActivityApi extends CommunityApiService {
  _UnavailableProfileActivityApi()
      : super(client: MockClient((http.Request _) async => http.Response('{}', 200)));

  @override
  Future<PaginatedResponse<ProfileActivity>> fetchProfileActivity({
    int? communityId,
    String? cursor,
    int pageSize = 50,
  }) async {
    throw FeatureUnavailableException('disabled');
  }
}

extension on CommunityRepository {
  Future<PaginatedResponse<ProfileActivity>> loadMoreProfileActivity({
    int? communityId,
    int pageSize = 50,
  }) {
    if (!hasMoreProfileActivity(communityId: communityId)) {
      return Future.value(PaginatedResponse<ProfileActivity>.empty());
    }

    return loadProfileActivity(
      communityId: communityId,
      pageSize: pageSize,
    );
  }
}
