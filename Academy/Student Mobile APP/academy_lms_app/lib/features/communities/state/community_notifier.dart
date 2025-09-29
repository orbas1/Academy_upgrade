import 'package:flutter/foundation.dart';

import '../data/community_repository.dart';
import '../models/community_feed_item.dart';
import '../models/community_summary.dart';

class CommunityNotifier extends ChangeNotifier {
  CommunityNotifier({CommunityRepository? repository})
      : _repository = repository ?? CommunityRepository();

  final CommunityRepository _repository;

  List<CommunitySummary> _communities = <CommunitySummary>[];
  List<CommunityFeedItem> _feed = <CommunityFeedItem>[];
  bool _loading = false;
  String? _error;

  List<CommunitySummary> get communities => _communities;
  List<CommunityFeedItem> get feed => _feed;
  bool get isLoading => _loading;
  String? get error => _error;

  Future<void> refreshCommunities() async {
    _setLoading(true);
    try {
      _communities = await _repository.loadCommunities();
      _error = null;
    } catch (err) {
      _error = err.toString();
    } finally {
      _setLoading(false);
    }
  }

  Future<void> refreshFeed(int communityId) async {
    _setLoading(true);
    try {
      _feed = await _repository.loadFeed(communityId);
      _error = null;
    } catch (err) {
      _error = err.toString();
    } finally {
      _setLoading(false);
    }
  }

  void _setLoading(bool value) {
    _loading = value;
    notifyListeners();
  }

  @override
  void dispose() {
    _repository.dispose();
    super.dispose();
  }
}
