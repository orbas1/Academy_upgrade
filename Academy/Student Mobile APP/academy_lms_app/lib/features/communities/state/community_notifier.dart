import 'dart:async';
import 'dart:io';

import 'package:connectivity_plus/connectivity_plus.dart';
import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'package:uuid/uuid.dart';

import 'package:academy_lms_app/services/community_manifest_service.dart';

import '../data/community_cache.dart';
import '../data/community_repository.dart';
import '../data/offline_action_queue.dart';
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
    CommunityCache? cache,
    OfflineCommunityActionQueue? offlineQueue,
    Connectivity? connectivity,
  })  : _repository = repository ?? CommunityRepository(cache: cache),
        _queueHealthRepository =
            queueHealthRepository ?? QueueHealthRepository(),
        _manifestService = manifestService ?? CommunityManifestService(),
        _offlineQueue = offlineQueue ?? OfflineCommunityActionQueue(),
        _connectivity = connectivity ?? Connectivity() {
    _connectivitySubscription = _connectivity.onConnectivityChanged.listen((result) {
      if (result != ConnectivityResult.none) {
        unawaited(processOfflineQueue());
      }
    });
  }

  final CommunityRepository _repository;
  QueueHealthRepository _queueHealthRepository;
  final CommunityManifestService _manifestService;
  OfflineCommunityActionQueue _offlineQueue;
  final Connectivity _connectivity;
  StreamSubscription<ConnectivityResult>? _connectivitySubscription;
  final Uuid _uuid = const Uuid();
  bool _queueProcessing = false;

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
  bool get canModerate {
    final role = _membership?.role;
    if (role == null) {
      return false;
    }
    return role == 'owner' || role == 'admin' || role == 'moderator';
  }
  String? get queueWarning => _queueWarning;

  CommunityRepository get repository => _repository;

  String feedFilterFor(int communityId) => _activeFeedFilters[communityId] ?? 'new';

  void updateAuthToken(String? token) {
    _repository.updateAuthToken(token);
    _queueHealthRepository.updateAuthToken(token);
  }

  void updateQueueHealthRepository(QueueHealthRepository repository) {
    _queueHealthRepository = repository;
  }

  void updateOfflineQueue(OfflineCommunityActionQueue queue) {
    if (!identical(_offlineQueue, queue)) {
      _offlineQueue = queue;
      unawaited(processOfflineQueue(force: true));
    }
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
      await _repository.saveCommunitySnapshot(
        filter: filter,
        items: _communities,
        nextCursor: _repository.communityCursorFor(filter),
        hasMore: _communitiesHasMore,
      );
    } catch (err) {
      final cached = await _repository.loadCachedCommunities(filter);
      if (cached != null && cached.items.isNotEmpty) {
        _communities = cached.items;
        _communitiesHasMore = cached.hasMore;
        _error = _offlineFallbackMessage(err);
      } else {
        _communities = <CommunitySummary>[];
        _communitiesHasMore = false;
        _error = err.toString();
      }
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
      await _repository.saveCommunitySnapshot(
        filter: _currentCommunitiesFilter,
        items: _communities,
        nextCursor: _repository.communityCursorFor(_currentCommunitiesFilter),
        hasMore: _repository.hasMoreCommunities(filter: _currentCommunitiesFilter),
      );
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
      _activeFeedFilters[communityId] = filter;
      final response = await _repository.loadFeed(
        communityId,
        filter: filter,
        resetCursor: true,
        pageSize: pageSize,
      );
      _feed = response.items;
      _feedHasMore[_feedKey(communityId, filter)] = response.hasMore;
      _error = null;
      await _persistFeedState(communityId, filter: filter);
      unawaited(processOfflineQueue());
    } catch (err) {
      final cached = await _repository.loadCachedFeed(communityId, filter: filter);
      if (cached != null && cached.items.isNotEmpty) {
        _feed = cached.items;
        _feedHasMore[_feedKey(communityId, filter)] = cached.hasMore;
        _error = _offlineFallbackMessage(err);
      } else {
        _feed = <CommunityFeedItem>[];
        _feedHasMore[_feedKey(communityId, filter)] = false;
        _error = err.toString();
      }
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
      await _persistFeedState(communityId, filter: filter);
      unawaited(processOfflineQueue());
    } catch (err) {
      _error = err.toString();
    } finally {
      _feedLoadingMore[key] = false;
      notifyListeners();
    }
  }

  Future<void> changeFeedFilter(
    int communityId, {
    required String filter,
    int pageSize = 20,
  }) async {
    if (_activeFeedFilters[communityId] == filter && _feed.isNotEmpty) {
      return;
    }
    await refreshFeed(
      communityId,
      filter: filter,
      pageSize: pageSize,
    );
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
    final filter = feedFilterFor(communityId);
    try {
      final item = await _repository.createPost(
        communityId,
        bodyMarkdown: bodyMarkdown,
        visibility: visibility,
        paywallTierId: paywallTierId,
      );

      _feed = <CommunityFeedItem>[item, ..._feed];
      await _persistFeedState(communityId, filter: filter);
      notifyListeners();

      unawaited(_evaluateQueueHealth());
      unawaited(processOfflineQueue());
    } catch (err) {
      if (_isConnectivityException(err)) {
        final clientReference = _uuid.v4();
        final placeholder = _buildPendingFeedItem(
          bodyMarkdown: bodyMarkdown,
          visibility: visibility,
          paywallTierId: paywallTierId,
          clientReference: clientReference,
        );

        _feed = <CommunityFeedItem>[placeholder, ..._feed];
        await _persistFeedState(communityId, filter: filter);

        try {
          await _offlineQueue.enqueue(
            CommunityOfflineAction(
              type: CommunityOfflineActionType.createPost,
              communityId: communityId,
              payload: <String, dynamic>{
                'body_md': bodyMarkdown,
                'visibility': visibility,
                'paywall_tier_id': paywallTierId,
              },
              clientReference: clientReference,
            ),
          );
          _queueWarning =
              'Offline mode — your post will sync automatically when a connection is available.';
        } catch (queueError, stackTrace) {
          debugPrint('Unable to enqueue offline post: $queueError');
          debugPrint('$stackTrace');
          _feed = _feed
              .map(
                (item) => item.clientReference == clientReference
                    ? item.copyWith(
                        isPending: false,
                        isFailed: true,
                        failureReason: queueError.toString(),
                      )
                    : item,
              )
              .toList(growable: false);
          await _persistFeedState(communityId, filter: filter);
          _queueWarning =
              'We could not queue your post for sync. Please retry when you are back online.';
        }

        notifyListeners();
      } else {
        rethrow;
      }
    }
  }

  Future<void> togglePostReaction(
    int communityId,
    int postId, {
    String reaction = 'like',
    String? clientReference,
  }) async {
    await _ensureManifest();
    final index = _feed.indexWhere(
      (item) => item.id == postId || (clientReference != null && item.clientReference == clientReference),
    );

    if (index == -1) {
      return;
    }

    final current = _feed[index];
    if (current.isPending) {
      return;
    }

    final toggled = current.copyWith(
      isLiked: !current.isLiked,
      likeCount: current.isLiked
          ? (current.likeCount > 0 ? current.likeCount - 1 : 0)
          : current.likeCount + 1,
    );

    _feed = List<CommunityFeedItem>.of(_feed)..[index] = toggled;
    notifyListeners();

    try {
      await _repository.togglePostReaction(communityId, postId, reaction: reaction);
    } catch (err) {
      _feed = List<CommunityFeedItem>.of(_feed)..[index] = current;
      notifyListeners();

      if (_isConnectivityException(err)) {
        _queueWarning = 'Reaction could not sync. Please try again when you are back online.';
        notifyListeners();
      } else {
        rethrow;
      }
    }
  }

  Future<void> reportPost(
    int communityId,
    int postId, {
    required String reason,
    List<String> evidenceUrls = const <String>[],
  }) async {
    await _ensureManifest();
    await _repository.reportPost(
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
  }) async {
    await _ensureManifest();
    await _repository.moderatePost(
      communityId,
      postId,
      action: action,
      note: note,
    );
    if (action == 'hide' || action == 'remove') {
      removePostFromFeed(postId);
    }
  }

  void removePostFromFeed(int postId) {
    final beforeLength = _feed.length;
    _feed = _feed.where((item) => item.id != postId).toList(growable: false);
    if (beforeLength != _feed.length) {
      notifyListeners();
    }
  }

  Future<void> processOfflineQueue({bool force = false}) async {
    if (_queueProcessing) {
      return;
    }

    if (!force) {
      final connectivityStatus = await _connectivity.checkConnectivity();
      if (connectivityStatus == ConnectivityResult.none) {
        return;
      }
    }

    _queueProcessing = true;

    try {
      var queueMutatedFeed = false;
      final report = await _offlineQueue.process(
        handler: (action) async {
          switch (action.type) {
            case CommunityOfflineActionType.createPost:
              final payload = action.payload;
              final item = await _repository.createPost(
                action.communityId,
                bodyMarkdown: payload['body_md'] as String? ?? '',
                visibility: payload['visibility'] as String? ?? 'community',
                paywallTierId: payload['paywall_tier_id'] as int?,
              );
              final changed = await _replacePendingFeedItem(
                communityId: action.communityId,
                clientReference: action.clientReference,
                replacement: item,
              );
              queueMutatedFeed = queueMutatedFeed || changed;
              break;
          }
        },
      );

      bool shouldNotify = report.hasChanges || queueMutatedFeed;
      if (report.successes.isNotEmpty) {
        _queueWarning =
            'Synced ${report.successes.length} offline post${report.successes.length == 1 ? '' : 's'} successfully.';
        shouldNotify = true;
      }

      if (report.permanentlyFailed.isNotEmpty) {
        for (final action in report.permanentlyFailed) {
          final changed = await _markPendingFeedItemFailed(
            action.communityId,
            action.clientReference,
            action.lastError ?? 'Failed to sync post.',
          );
          shouldNotify = shouldNotify || changed;
        }
        _queueWarning =
            'Some offline posts could not be synced. Edit and retry once your connection is stable.';
        shouldNotify = true;
      }

      if (shouldNotify) {
        notifyListeners();
      }
    } catch (err, stack) {
      debugPrint('Offline queue processing failed: $err');
      debugPrint('$stack');
    } finally {
      _queueProcessing = false;
    }
  }

  Future<void> _persistFeedState(int communityId, {String? filter}) {
    final resolvedFilter = filter ?? feedFilterFor(communityId);
    return _repository.saveFeedSnapshot(
      communityId: communityId,
      filter: resolvedFilter,
      items: _feed,
      nextCursor: _repository.feedCursorFor(communityId, filter: resolvedFilter),
      hasMore: _repository.hasMoreFeed(communityId, filter: resolvedFilter),
    );
  }

  CommunityFeedItem _buildPendingFeedItem({
    required String bodyMarkdown,
    required String visibility,
    int? paywallTierId,
    required String clientReference,
  }) {
    final now = DateTime.now();
    return CommunityFeedItem(
      id: -now.millisecondsSinceEpoch,
      type: 'text',
      authorName: 'You',
      body: bodyMarkdown,
      bodyMarkdown: bodyMarkdown,
      createdAt: now,
      likeCount: 0,
      commentCount: 0,
      visibility: visibility,
      isLiked: false,
      paywallTierId: paywallTierId,
      isPending: true,
      clientReference: clientReference,
    );
  }

  Future<bool> _replacePendingFeedItem({
    required int communityId,
    required String? clientReference,
    required CommunityFeedItem replacement,
  }) async {
    bool changed = false;
    if (clientReference != null) {
      _feed = _feed
          .map((item) {
            if (item.clientReference == clientReference) {
              changed = true;
              return replacement;
            }
            return item;
          })
          .toList(growable: false);
    }

    if (!changed) {
      _feed = <CommunityFeedItem>[replacement, ..._feed];
      changed = true;
    }

    if (changed) {
      await _persistFeedState(communityId);
    }

    return changed;
  }

  Future<bool> _markPendingFeedItemFailed(
    int communityId,
    String? clientReference,
    String error,
  ) async {
    if (clientReference == null) {
      return false;
    }

    bool changed = false;
    _feed = _feed
        .map((item) {
          if (item.clientReference == clientReference) {
            changed = true;
            return item.copyWith(
              isPending: false,
              isFailed: true,
              failureReason: error,
            );
          }
          return item;
        })
        .toList(growable: false);

    if (changed) {
      await _persistFeedState(communityId);
    }

    return changed;
  }

  bool _isConnectivityException(Object error) {
    if (error is SocketException || error is TimeoutException) {
      return true;
    }

    if (error is http.ClientException) {
      final message = error.message.toLowerCase();
      return message.contains('failed host lookup') ||
          message.contains('network is unreachable') ||
          message.contains('timed out') ||
          message.contains('connection closed');
    }

    return false;
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

  String _offlineFallbackMessage(Object error) {
    final description = error.toString();
    return 'Offline mode — displaying cached data. Last error: $description';
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
    _connectivitySubscription?.cancel();
    _queueHealthRepository.dispose();
    unawaited(_repository.dispose());
    super.dispose();
  }
}
