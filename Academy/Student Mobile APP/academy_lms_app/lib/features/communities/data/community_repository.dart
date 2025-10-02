import '../models/community_comment.dart';
import '../models/community_feed_item.dart';
import '../models/community_leaderboard_entry.dart';
import '../models/community_level.dart';
import '../models/community_member.dart';
import '../models/community_notification.dart';
import '../models/community_notification_preferences.dart';
import '../models/community_summary.dart';
import '../models/geo_place.dart';
import '../models/paywall_tier.dart';
import '../models/point_event.dart';
import '../models/points_summary.dart';
import '../models/profile_activity.dart';
import '../models/subscription_checkout.dart';
import '../models/subscription_status.dart';
import 'package:academy_lms_app/services/community_manifest_service.dart';
import 'community_api_service.dart';
import 'community_cache.dart';
import 'paginated_response.dart';
import 'errors.dart';
import '../models/upload_quota_summary.dart';

class CommunityRepository {
  CommunityRepository({CommunityApiService? api, CommunityCache? cache})
      : _api = api ?? CommunityApiService(),
        _cache = cache ?? CommunityCache();

  final CommunityApiService _api;
  final CommunityCache _cache;
  final Map<String, String?> _communityCursors = <String, String?>{};
  final Map<String, String?> _feedCursors = <String, String?>{};
  final Map<int, String?> _notificationCursors = <int, String?>{};
  final Map<String, String?> _commentCursors = <String, String?>{};
  final Map<int, String?> _pointHistoryCursors = <int, String?>{};
  final Map<String, String?> _profileActivityCursors = <String, String?>{};
  bool _profileActivityAvailable = true;

  void updateAuthToken(String? token) {
    _api.updateAuthToken(token);
  }

  void applyManifest(CommunityModuleManifest manifest) {
    if (manifest.apiBaseUrl != null && manifest.apiBaseUrl!.isNotEmpty) {
      _api.updateBaseUrl(manifest.apiBaseUrl);
    }
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
    if (resetCursor) {
      await _cache.writeCommunityList(filter, response);
    }
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
    if (resetCursor) {
      await _cache.writeCommunityFeed(
        communityId,
        filter,
        response,
      );
    }
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

  Future<PointsSummary> loadPointsSummary(int communityId) {
    return _api.fetchPointsSummary(communityId);
  }

  Future<UploadQuotaSummary> loadUploadQuota({int? communityId}) {
    return _api.fetchUploadQuota(communityId: communityId);
  }

  Future<PaginatedResponse<PointEvent>> loadPointHistory(
    int communityId, {
    bool resetCursor = false,
    int pageSize = 20,
  }) async {
    if (resetCursor) {
      _pointHistoryCursors.remove(communityId);
    }

    final response = await _api.fetchPointHistory(
      communityId,
      cursor: resetCursor ? null : _pointHistoryCursors[communityId],
      pageSize: pageSize,
    );

    _pointHistoryCursors[communityId] = response.nextCursor;
    return response;
  }

  Future<PaginatedResponse<PointEvent>> loadMorePointHistory(
    int communityId, {
    int pageSize = 20,
  }) {
    if (!hasMorePointHistory(communityId)) {
      return Future.value(PaginatedResponse<PointEvent>.empty());
    }

    return loadPointHistory(
      communityId,
      pageSize: pageSize,
    );
  }

  bool hasMorePointHistory(int communityId) {
    final cursor = _pointHistoryCursors[communityId];
    return cursor != null && cursor.isNotEmpty;
  }

  void resetPointHistoryPaging(int communityId) {
    _pointHistoryCursors.remove(communityId);
  }

  Future<PaginatedResponse<ProfileActivity>> loadProfileActivity({
    int? communityId,
    bool resetCursor = false,
    int pageSize = 50,
  }) async {
    final key = _profileActivityKey(communityId);
    if (resetCursor) {
      _profileActivityCursors.remove(key);
    }

    try {
      final response = await _api.fetchProfileActivity(
        communityId: communityId,
        cursor: resetCursor ? null : _profileActivityCursors[key],
        pageSize: pageSize,
      );

      _profileActivityCursors[key] = response.nextCursor;
      _profileActivityAvailable = true;

      return response;
    } on FeatureUnavailableException {
      _profileActivityAvailable = false;
      rethrow;
    }
  }

  bool isProfileActivityAvailable() => _profileActivityAvailable;

  void resetProfileActivityPaging({int? communityId}) {
    _profileActivityCursors.remove(_profileActivityKey(communityId));
  }

  bool hasMoreProfileActivity({int? communityId}) {
    final cursor = _profileActivityCursors[_profileActivityKey(communityId)];
    return cursor != null && cursor.isNotEmpty;
  }

  Future<PaginatedResponse<CommunitySummary>?> loadCachedCommunities(String filter) {
    return _cache.readCommunityList(filter);
  }

  Future<PaginatedResponse<CommunityFeedItem>?> loadCachedFeed(
    int communityId, {
    String filter = 'new',
  }) {
    return _cache.readCommunityFeed(communityId, filter);
  }

  String _profileActivityKey(int? communityId) => communityId == null ? 'all' : 'community:$communityId';

  Future<void> saveCommunitySnapshot({
    required String filter,
    required List<CommunitySummary> items,
    String? nextCursor,
    bool hasMore = false,
  }) {
    return _cache.writeCommunityList(
      filter,
      PaginatedResponse<CommunitySummary>.fromCache(
        items: items,
        nextCursor: nextCursor,
        hasMore: hasMore,
      ),
    );
  }

  Future<void> saveFeedSnapshot({
    required int communityId,
    required String filter,
    required List<CommunityFeedItem> items,
    String? nextCursor,
    bool hasMore = false,
  }) {
    return _cache.writeCommunityFeed(
      communityId,
      filter,
      PaginatedResponse<CommunityFeedItem>.fromCache(
        items: items,
        nextCursor: nextCursor,
        hasMore: hasMore,
      ),
    );
  }

  String? communityCursorFor(String filter) => _communityCursors[filter];

  String? feedCursorFor(
    int communityId, {
    String filter = 'new',
  }) =>
      _feedCursors[_feedKey(communityId, filter)];

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

  Future<void> reportPost(
    int communityId,
    int postId, {
    required String reason,
    List<String> evidenceUrls = const <String>[],
  }) {
    return _api.flagPost(
      communityId,
      postId,
      reason: reason,
      evidenceUrls: evidenceUrls,
    );
  }

  Future<void> moderatePost(
    int communityId,
    int postId, {
    required String action,
    String? note,
  }) {
    return _api.moderatePost(
      communityId,
      postId,
      action: action,
      note: note,
    );
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

  Future<List<CommunityLevel>> loadLevels(int communityId) {
    return _api.fetchLevels(communityId);
  }

  Future<List<PaywallTier>> loadPaywallTiers(int communityId) {
    return _api.fetchPaywallTiers(communityId);
  }

  Future<SubscriptionCheckout> createSubscriptionCheckout(
    int communityId, {
    required int tierId,
    int quantity = 1,
    String? couponCode,
    required Uri returnUrl,
    Uri? cancelUrl,
  }) {
    return _api.createSubscriptionCheckout(
      communityId,
      tierId: tierId,
      quantity: quantity,
      couponCode: couponCode,
      returnUrl: returnUrl,
      cancelUrl: cancelUrl,
    );
  }

  Future<SubscriptionStatus> loadSubscriptionStatus(int communityId) {
    return _api.fetchSubscriptionStatus(communityId);
  }

  Future<List<GeoPlace>> loadGeoPlaces(int communityId) {
    return _api.fetchGeoPlaces(communityId);
  }

  Future<void> dispose() async {
    await _api.dispose();
    await _cache.close();
    _communityCursors.clear();
    _feedCursors.clear();
    _notificationCursors.clear();
    _pointHistoryCursors.clear();
  }

  String _feedKey(int communityId, String filter) => '$communityId::$filter';

  String _commentKey(int communityId, int postId) => '$communityId::comment::$postId';
}
