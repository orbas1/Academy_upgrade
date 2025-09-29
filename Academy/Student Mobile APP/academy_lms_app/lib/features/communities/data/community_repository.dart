import '../models/community_feed_item.dart';
import '../models/community_leaderboard_entry.dart';
import '../models/community_member.dart';
import '../models/community_summary.dart';
import 'community_api_service.dart';

class CommunityRepository {
  CommunityRepository({CommunityApiService? api}) : _api = api ?? CommunityApiService();

  final CommunityApiService _api;

  Future<List<CommunitySummary>> loadCommunities({String filter = 'all'}) {
    return _api.fetchCommunities(filter: filter);
  }

  Future<List<CommunityFeedItem>> loadFeed(int communityId, {String filter = 'new'}) {
    return _api.fetchFeed(communityId, filter: filter);
  }

  Future<CommunityMember?> loadMembership(int communityId) {
    return _api.fetchMembership(communityId);
  }

  Future<CommunityMember> joinCommunity(int communityId) {
    return _api.joinCommunity(communityId);
  }

  Future<void> leaveCommunity(int communityId) {
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

  Future<void> dispose() async {
    await _api.dispose();
  }
}
