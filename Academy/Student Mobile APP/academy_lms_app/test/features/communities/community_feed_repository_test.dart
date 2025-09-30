import 'dart:collection';

import 'package:academy_lms_app/features/communities/data/community_api_service.dart';
import 'package:academy_lms_app/features/communities/data/community_cache.dart';
import 'package:academy_lms_app/features/communities/data/community_repository.dart';
import 'package:academy_lms_app/features/communities/data/paginated_response.dart';
import 'package:academy_lms_app/features/communities/models/community_feed_item.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  group('CommunityRepository feed caching', () {
    test('priming cache on reset and respecting hasMore cursors', () async {
      final responses = Queue<PaginatedResponse<CommunityFeedItem>>()
        ..add(_buildPage(communityId: 7, startId: 1, hasMore: true, cursor: 'cursor-1'))
        ..add(_buildPage(communityId: 7, startId: 2, hasMore: false, cursor: null));

      final api = _FakeCommunityApiService(responses);
      final cache = _InMemoryCommunityCache();
      final repository = CommunityRepository(api: api, cache: cache);

      final firstPage = await repository.loadFeed(7, resetCursor: true, pageSize: 1);

      expect(firstPage.items, hasLength(1));
      expect(firstPage.hasMore, isTrue);
      expect(cache.feedEntries.containsKey('7:new'), isTrue);
      expect(api.calls, equals(1));

      final cached = await repository.loadCachedFeed(7);
      expect(cached?.items.first.id, equals(firstPage.items.first.id));

      final secondPage = await repository.loadMoreFeed(7, pageSize: 1);
      expect(secondPage.items, hasLength(1));
      expect(repository.hasMoreFeed(7), isFalse);
      expect(api.calls, equals(2));

      final exhausted = await repository.loadMoreFeed(7, pageSize: 1);
      expect(exhausted.items, isEmpty);
      expect(api.calls, equals(2), reason: 'No additional API call once hasMore is false');
    });
  });
}

PaginatedResponse<CommunityFeedItem> _buildPage({
  required int communityId,
  required int startId,
  required bool hasMore,
  required String? cursor,
}) {
  final item = CommunityFeedItem.fromJson(<String, dynamic>{
    'id': startId,
    'community_id': communityId,
    'type': 'text',
    'author_name': 'Test Member',
    'author_id': 77,
    'body': 'Hello world',
    'body_md': 'Hello world',
    'body_html': '<p>Hello world</p>',
    'created_at': DateTime.now().toIso8601String(),
    'like_count': 0,
    'comment_count': 0,
    'visibility': 'community',
    'liked': false,
    'attachments': <Map<String, dynamic>>[],
    'is_archived': false,
  });

  return PaginatedResponse<CommunityFeedItem>(
    items: <CommunityFeedItem>[item],
    nextCursor: cursor,
    hasMore: hasMore,
  );
}

class _FakeCommunityApiService extends CommunityApiService {
  _FakeCommunityApiService(this._responses)
      : calls = 0,
        super(client: MockClient((http.Request _) async => http.Response('{}', 200)));

  final Queue<PaginatedResponse<CommunityFeedItem>> _responses;
  int calls;

  @override
  Future<PaginatedResponse<CommunityFeedItem>> fetchFeed(
    int communityId, {
    String filter = 'new',
    String? cursor,
    int pageSize = 20,
  }) async {
    calls += 1;
    if (_responses.isEmpty) {
      return PaginatedResponse<CommunityFeedItem>.empty();
    }
    return _responses.removeFirst();
  }
}

class _InMemoryCommunityCache extends CommunityCache {
  _InMemoryCommunityCache()
      : feedEntries = <String, PaginatedResponse<CommunityFeedItem>>{},
        communityEntries = <String, PaginatedResponse<dynamic>>{};

  final Map<String, PaginatedResponse<CommunityFeedItem>> feedEntries;
  final Map<String, PaginatedResponse<dynamic>> communityEntries;

  @override
  Future<void> writeCommunityFeed(
    int communityId,
    String filter,
    PaginatedResponse<CommunityFeedItem> response,
  ) async {
    feedEntries['$communityId:$filter'] = response;
  }

  @override
  Future<PaginatedResponse<CommunityFeedItem>?> readCommunityFeed(
    int communityId,
    String filter,
  ) async {
    return feedEntries['$communityId:$filter'];
  }

  @override
  Future<void> writeCommunityList(
    String filter,
    PaginatedResponse response,
  ) async {
    communityEntries[filter] = response;
  }

  @override
  Future<PaginatedResponse?> readCommunityList(String filter) async {
    return communityEntries[filter];
  }
}
