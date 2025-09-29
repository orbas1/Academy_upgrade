import '../models/community_feed_item.dart';
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

  Future<void> dispose() async {
    await _api.dispose();
  }
}
