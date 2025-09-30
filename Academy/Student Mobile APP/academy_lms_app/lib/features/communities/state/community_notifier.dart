import 'dart:async';

import 'package:flutter/foundation.dart';

import 'package:academy_lms_app/services/community_manifest_service.dart';

import '../data/community_repository.dart';
import '../data/queue_health_repository.dart';
import '../models/community_feed_item.dart';
import '../models/community_leaderboard_entry.dart';
import '../models/community_member.dart';
import '../models/community_summary.dart';

class CommunityNotifier extends ChangeNotifier {
  CommunityNotifier({
    CommunityRepository? repository,
    QueueHealthRepository? queueHealthRepository,
    CommunityManifestService? manifestService,
  })  : _repository = repository ?? CommunityRepository(),
        _queueHealthRepository =
            queueHealthRepository ?? QueueHealthRepository(),
        _manifestService = manifestService ?? CommunityManifestService();

  final CommunityRepository _repository;
  QueueHealthRepository _queueHealthRepository;
  final CommunityManifestService _manifestService;

  final Map<int, String> _activeFeedFilters = <int, String>{};
  final Map<String, bool> _feedHasMore = <String, bool>{};
  final Map<String, bool> _feedLoadingMore = <String, bool>{};

  List<CommunitySummary> _communities = <CommunitySummary>[];
  List<CommunityFeedItem> _feed = <CommunityFeedItem>[];
  List<CommunityLeaderboardEntry> _leaderboard = <CommunityLeaderboardEntry>[];
  bool _loading = false;
  bool _membershipLoading = false;
  bool _mutatingMembership = false;
  bool _loadingMoreCommunities = false;
  bool _communitiesHasMore = false;
  bool _leaderboardLoading = false;
  String _currentCommunitiesFilter = 'all';
  String? _error;
  CommunityMember? _membership;
  String? _queueWarning;
  bool _manifestApplied = false;

  List<CommunitySummary> get communities => _communities;
  List<CommunityFeedItem> get feed => _feed;
  List<CommunityLeaderboardEntry> get leaderboard => _leaderboard;
  bool get isLoading => _loading;
  bool get isMembershipLoading => _membershipLoading;
  bool get isMutatingMembership => _mutatingMembership;
  bool get isLoadingMoreCommunities => _loadingMoreCommunities;
  bool get canLoadMoreCommunities => _communitiesHasMore;
  bool get isLeaderboardLoading => _leaderboardLoading;
  String get currentCommunitiesFilter => _currentCommunitiesFilter;
  String? get error => _error;
  CommunityMember? get membership => _membership;
  bool get isMember => _membership?.isActive ?? false;
  String? get queueWarning => _queueWarning;

  CommunityRepository get repository => _repository;

  void updateAuthToken(String? token) {
    _repository.updateAuthToken(token);
    _queueHealthRepository.updateAuthToken(token);
  }

  void updateQueueHealthRepository(QueueHealthRepository repository) {
    _queueHealthRepository = repository;
  }

  String? consumeQueueWarning() {
    final warning = _queueWarning;
    _queueWarning = null;
    return warning;
  }

  Future<void> refreshCommunities({String filter = 'all', int pageSize = 20}) async {
    _setLoading(true);
    try {
      await _ensureManifest();
      _currentCommunitiesFilter = filter;
      final response = await _repository.loadCommunities(
        filter: filter,
        resetCursor: true,
        pageSize: pageSize,
      );
      _communities = response.items;
      _communitiesHasMore = response.hasMore;
      _error = null;
    } catch (err) {
      _communitiesHasMore = false;
      _error = err.toString();
    } finally {
      _setLoading(false);
    }
  }

  Future<void> loadMoreCommunities({int pageSize = 20}) async {
    if (_loadingMoreCommunities || !_repository.hasMoreCommunities(filter: _currentCommunitiesFilter)) {
      _communitiesHasMore = _repository.hasMoreCommunities(filter: _currentCommunitiesFilter);
      return;
    }

    _loadingMoreCommunities = true;
    notifyListeners();

    try {
      await _ensureManifest();
      final response = await _repository.loadMoreCommunities(
        filter: _currentCommunitiesFilter,
        pageSize: pageSize,
      );
      if (response.items.isNotEmpty) {
        _communities = <CommunitySummary>[..._communities, ...response.items];
      }
      _communitiesHasMore = response.hasMore;
      _error = null;
    } catch (err) {
      _error = err.toString();
    } finally {
      _loadingMoreCommunities = false;
      notifyListeners();
    }
  }

  Future<void> refreshFeed(
    int communityId, {
    String filter = 'new',
    int pageSize = 20,
  }) async {
    _setLoading(true);
    try {
      await _ensureManifest();
      final response = await _repository.loadFeed(
        communityId,
        filter: filter,
        resetCursor: true,
        pageSize: pageSize,
      );
      _activeFeedFilters[communityId] = filter;
      _feed = response.items;
      _feedHasMore[_feedKey(communityId, filter)] = response.hasMore;
      _error = null;
    } catch (err) {
      _feedHasMore[_feedKey(communityId, filter)] = false;
      _error = err.toString();
    } finally {
      _setLoading(false);
    }
  }

  Future<void> loadMoreFeed(
    int communityId, {
    int pageSize = 20,
  }) async {
    final filter = _activeFeedFilters[communityId] ?? 'new';
    final key = _feedKey(communityId, filter);

    if (_feedLoadingMore[key] == true || !_repository.hasMoreFeed(communityId, filter: filter)) {
      _feedHasMore[key] = _repository.hasMoreFeed(communityId, filter: filter);
      return;
    }

    _feedLoadingMore[key] = true;
    notifyListeners();

    try {
      await _ensureManifest();
      final response = await _repository.loadMoreFeed(
        communityId,
        filter: filter,
        pageSize: pageSize,
      );

      if (response.items.isNotEmpty) {
        _feed = <CommunityFeedItem>[..._feed, ...response.items];
      }

      _feedHasMore[key] = response.hasMore;
      _error = null;
    } catch (err) {
      _error = err.toString();
    } finally {
      _feedLoadingMore[key] = false;
      notifyListeners();
    }
  }

  bool canLoadMoreFeed(
    int communityId, {
    String? filter,
  }) {
    final resolvedFilter = filter ?? _activeFeedFilters[communityId] ?? 'new';
    return _feedHasMore[_feedKey(communityId, resolvedFilter)] ?? false;
  }

  bool isFeedLoadingMore(
    int communityId, {
    String? filter,
  }) {
    final resolvedFilter = filter ?? _activeFeedFilters[communityId] ?? 'new';
    return _feedLoadingMore[_feedKey(communityId, resolvedFilter)] ?? false;
  }

  Future<void> refreshMembership(int communityId) async {
    _setMembershipLoading(true);
    try {
      await _ensureManifest();
      _membership = await _repository.loadMembership(communityId);
    } finally {
      _setMembershipLoading(false);
      notifyListeners();
    }
  }

  Future<void> joinCommunity(int communityId) async {
    _setMutatingMembership(true);
    try {
      await _ensureManifest();
      _membership = await _repository.joinCommunity(communityId);
      _communities = _communities
          .map(
            (summary) => summary.id == communityId
                ? CommunitySummary(
                    id: summary.id,
                    slug: summary.slug,
                    name: summary.name,
                    tagline: summary.tagline,
                    memberCount: summary.memberCount + 1,
                    isMember: true,
                    visibility: summary.visibility,
                  )
                : summary,
          )
          .toList(growable: false);
    } finally {
      _setMutatingMembership(false);
      notifyListeners();
    }
  }

  Future<void> leaveCommunity(int communityId) async {
    _setMutatingMembership(true);
    try {
      await _ensureManifest();
      await _repository.leaveCommunity(communityId);
      _membership = null;
      _purgeFeedState(communityId);
      _communities = _communities
          .map(
            (summary) => summary.id == communityId
                ? CommunitySummary(
                    id: summary.id,
                    slug: summary.slug,
                    name: summary.name,
                    tagline: summary.tagline,
                    memberCount: summary.memberCount > 0 ? summary.memberCount - 1 : 0,
                    isMember: false,
                    visibility: summary.visibility,
                  )
                : summary,
          )
          .toList(growable: false);
    } finally {
      _setMutatingMembership(false);
      notifyListeners();
    }
  }

  Future<void> createPost(
    int communityId, {
    required String bodyMarkdown,
    String visibility = 'community',
    int? paywallTierId,
  }) async {
    await _ensureManifest();
    final item = await _repository.createPost(
      communityId,
      bodyMarkdown: bodyMarkdown,
      visibility: visibility,
      paywallTierId: paywallTierId,
    );

    _feed = <CommunityFeedItem>[item, ..._feed];
    notifyListeners();

    unawaited(_evaluateQueueHealth());
  }

  Future<void> togglePostReaction(int communityId, int postId, {String reaction = 'like'}) async {
    await _ensureManifest();
    await _repository.togglePostReaction(communityId, postId, reaction: reaction);
    _feed = _feed
        .map(
          (item) => item.id == postId
              ? CommunityFeedItem(
                  id: item.id,
                  type: item.type,
                  authorName: item.authorName,
                  body: item.body,
                  bodyMarkdown: item.bodyMarkdown,
                  createdAt: item.createdAt,
                  likeCount: item.isLiked ? item.likeCount - 1 : item.likeCount + 1,
                  commentCount: item.commentCount,
                  visibility: item.visibility,
                  isLiked: !item.isLiked,
                  paywallTierId: item.paywallTierId,
                )
              : item,
        )
        .toList(growable: false);
    notifyListeners();
  }

  Future<void> loadLeaderboard(int communityId, {String period = 'weekly'}) async {
    _leaderboardLoading = true;
    notifyListeners();

    try {
      await _ensureManifest();
      _leaderboard = await _repository.loadLeaderboard(communityId, period: period);
      _error = null;
    } catch (err) {
      _error = err.toString();
      _leaderboard = <CommunityLeaderboardEntry>[];
    } finally {
      _leaderboardLoading = false;
      notifyListeners();
    }
  }

  void _setLoading(bool value) {
    _loading = value;
    notifyListeners();
  }

  void _setMembershipLoading(bool value) {
    _membershipLoading = value;
    notifyListeners();
  }

  void _setMutatingMembership(bool value) {
    _mutatingMembership = value;
    notifyListeners();
  }

  void _purgeFeedState(int communityId) {
    _activeFeedFilters.remove(communityId);
    _feedHasMore.removeWhere((key, _) => key.startsWith('$communityId::'));
    _feedLoadingMore.removeWhere((key, _) => key.startsWith('$communityId::'));
  }

  String _feedKey(int communityId, String filter) => '$communityId::$filter';

  Future<void> _evaluateQueueHealth() async {
    try {
      final warning = await _queueHealthRepository.loadWarningForQueue('media');
      if (warning != null && warning.trim().isNotEmpty) {
        if (warning != _queueWarning) {
          _queueWarning = warning;
          notifyListeners();
        } else {
          _queueWarning = warning;
        }
      } else if (_queueWarning != null) {
        _queueWarning = null;
        notifyListeners();
      }
    } catch (err, stack) {
      debugPrint('Queue health check failed: $err');
      debugPrint('$stack');
    }
  }

  Future<void> _ensureManifest() async {
    if (_manifestApplied) {
      return;
    }

    try {
      final manifest = await _manifestService.fetch();
      _repository.applyManifest(manifest);
      _manifestApplied = true;
    } catch (err, stack) {
      debugPrint('Unable to load community manifest: $err');
      debugPrint('$stack');
    }
  }

  @override
  void dispose() {
    _queueHealthRepository.dispose();
    unawaited(_repository.dispose());
    super.dispose();
  }
}
