import '../models/community_comment.dart';
import '../models/community_feed_item.dart';
import '../models/community_leaderboard_entry.dart';
import '../models/community_member.dart';
import '../models/community_notification.dart';
import '../models/community_notification_preferences.dart';
import '../models/community_summary.dart';
import 'community_api_service.dart';
import 'paginated_response.dart';

class CommunityRepository {
  CommunityRepository({CommunityApiService? api}) : _api = api ?? CommunityApiService();

  final CommunityApiService _api;
  final Map<String, String?> _communityCursors = <String, String?>{};
  final Map<String, String?> _feedCursors = <String, String?>{};
  final Map<int, String?> _notificationCursors = <int, String?>{};
  final Map<String, String?> _commentCursors = <String, String?>{};

  void updateAuthToken(String? token) {
    _api.updateAuthToken(token);
  }

  Future<PaginatedResponse<CommunitySummary>> loadCommunities({
    String filter = 'all',
    bool resetCursor = false,
    int pageSize = 20,
  }) async {
    if (resetCursor) {
      _communityCursors.remove(filter);
    }

    final response = await _api.fetchCommunities(
      filter: filter,
      pageSize: pageSize,
      cursor: resetCursor ? null : _communityCursors[filter],
    );

    _communityCursors[filter] = response.nextCursor;
    return response;
  }

  Future<PaginatedResponse<CommunitySummary>> loadMoreCommunities({
    String filter = 'all',
    int pageSize = 20,
  }) {
    if (!hasMoreCommunities(filter: filter)) {
      return Future.value(PaginatedResponse<CommunitySummary>.empty());
    }

    return loadCommunities(filter: filter, pageSize: pageSize);
  }

  bool hasMoreCommunities({String filter = 'all'}) {
    final cursor = _communityCursors[filter];
    return cursor != null && cursor.isNotEmpty;
  }

  void resetCommunitiesPaging({String filter = 'all'}) {
    _communityCursors.remove(filter);
  }

  Future<PaginatedResponse<CommunityFeedItem>> loadFeed(
    int communityId, {
    String filter = 'new',
    bool resetCursor = false,
    int pageSize = 20,
  }) async {
    final key = _feedKey(communityId, filter);

    if (resetCursor) {
      _feedCursors.remove(key);
    }

    final response = await _api.fetchFeed(
      communityId,
      filter: filter,
      cursor: resetCursor ? null : _feedCursors[key],
      pageSize: pageSize,
    );

    _feedCursors[key] = response.nextCursor;
    return response;
  }

  Future<PaginatedResponse<CommunityFeedItem>> loadMoreFeed(
    int communityId, {
    String filter = 'new',
    int pageSize = 20,
  }) {
    final key = _feedKey(communityId, filter);
    if (!hasMoreFeed(communityId, filter: filter)) {
      return Future.value(PaginatedResponse<CommunityFeedItem>.empty());
    }

    return loadFeed(
      communityId,
      filter: filter,
      pageSize: pageSize,
    );
  }

  bool hasMoreFeed(
    int communityId, {
    String filter = 'new',
  }) {
    final cursor = _feedCursors[_feedKey(communityId, filter)];
    return cursor != null && cursor.isNotEmpty;
  }

  void resetFeedPaging(
    int communityId, {
    String filter = 'new',
  }) {
    _feedCursors.remove(_feedKey(communityId, filter));
  }

  Future<PaginatedResponse<CommunityComment>> loadComments(
    int communityId,
    int postId, {
    bool resetCursor = false,
    int pageSize = 20,
  }) async {
    final key = _commentKey(communityId, postId);

    if (resetCursor) {
      _commentCursors.remove(key);
    }

    final response = await _api.fetchComments(
      communityId,
      postId,
      cursor: resetCursor ? null : _commentCursors[key],
      pageSize: pageSize,
    );

    _commentCursors[key] = response.nextCursor;
    return response;
  }

  Future<PaginatedResponse<CommunityComment>> loadMoreComments(
    int communityId,
    int postId, {
    int pageSize = 20,
  }) {
    if (!hasMoreComments(communityId, postId)) {
      return Future.value(PaginatedResponse<CommunityComment>.empty());
    }

    return loadComments(
      communityId,
      postId,
      pageSize: pageSize,
    );
  }

  bool hasMoreComments(int communityId, int postId) {
    final cursor = _commentCursors[_commentKey(communityId, postId)];
    return cursor != null && cursor.isNotEmpty;
  }

  void resetCommentsPaging(int communityId, int postId) {
    _commentCursors.remove(_commentKey(communityId, postId));
  }

  Future<CommunityComment> createComment(
    int communityId,
    int postId, {
    required String bodyMarkdown,
    int? parentId,
  }) async {
    return _api.createComment(
      communityId,
      postId,
      bodyMarkdown: bodyMarkdown,
      parentId: parentId,
    );
  }

  Future<CommunityMember?> loadMembership(int communityId) {
    return _api.fetchMembership(communityId);
  }

  Future<CommunityMember> joinCommunity(int communityId) {
    return _api.joinCommunity(communityId);
  }

  Future<void> leaveCommunity(int communityId) {
    resetFeedPaging(communityId);
    _notificationCursors.remove(communityId);
    return _api.leaveCommunity(communityId);
  }

  Future<CommunityFeedItem> createPost(
    int communityId, {
    required String bodyMarkdown,
    String visibility = 'community',
    int? paywallTierId,
  }) {
    return _api.createPost(
      communityId,
      bodyMarkdown: bodyMarkdown,
      visibility: visibility,
      paywallTierId: paywallTierId,
    );
  }

  Future<void> togglePostReaction(int communityId, int postId, {String reaction = 'like'}) {
    return _api.togglePostReaction(communityId, postId, reaction: reaction);
  }

  Future<List<CommunityLeaderboardEntry>> loadLeaderboard(int communityId, {String period = 'weekly'}) {
    return _api.fetchLeaderboard(communityId, period: period);
  }

  Future<PaginatedResponse<CommunityNotification>> loadNotifications(
    int communityId, {
    bool resetCursor = false,
    int pageSize = 20,
  }) async {
    if (resetCursor) {
      _notificationCursors.remove(communityId);
    }

    final response = await _api.fetchNotifications(
      communityId,
      cursor: resetCursor ? null : _notificationCursors[communityId],
      pageSize: pageSize,
    );

    _notificationCursors[communityId] = response.nextCursor;
    return response;
  }

  Future<PaginatedResponse<CommunityNotification>> loadMoreNotifications(
    int communityId, {
    int pageSize = 20,
  }) {
    if (!hasMoreNotifications(communityId)) {
      return Future.value(PaginatedResponse<CommunityNotification>.empty());
    }

    return loadNotifications(
      communityId,
      pageSize: pageSize,
    );
  }

  bool hasMoreNotifications(int communityId) {
    final cursor = _notificationCursors[communityId];
    return cursor != null && cursor.isNotEmpty;
  }

  void resetNotificationsPaging(int communityId) {
    _notificationCursors.remove(communityId);
  }

  Future<CommunityNotificationPreferences> loadNotificationPreferences(int communityId) {
    return _api.fetchNotificationPreferences(communityId);
  }

  Future<CommunityNotificationPreferences> updateNotificationPreferences(
    int communityId, {
    required CommunityNotificationPreferences preferences,
  }) {
    return _api.updateNotificationPreferences(communityId, preferences: preferences);
  }

  Future<void> resetNotificationPreferences(int communityId) {
    return _api.resetNotificationPreferences(communityId);
  }

  Future<void> dispose() async {
    await _api.dispose();
    _communityCursors.clear();
    _feedCursors.clear();
    _notificationCursors.clear();
  }

  String _feedKey(int communityId, String filter) => '$communityId::$filter';

  String _commentKey(int communityId, int postId) => '$communityId::comment::$postId';
}
