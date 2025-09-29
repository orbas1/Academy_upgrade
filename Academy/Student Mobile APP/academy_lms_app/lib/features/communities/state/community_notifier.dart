import 'dart:async';

import 'package:flutter/foundation.dart';

import '../data/community_repository.dart';
import '../models/community_feed_item.dart';
import '../models/community_leaderboard_entry.dart';
import '../models/community_member.dart';
import '../models/community_summary.dart';

class CommunityNotifier extends ChangeNotifier {
  CommunityNotifier({CommunityRepository? repository})
      : _repository = repository ?? CommunityRepository();

  final CommunityRepository _repository;

  List<CommunitySummary> _communities = <CommunitySummary>[];
  List<CommunityFeedItem> _feed = <CommunityFeedItem>[];
  List<CommunityLeaderboardEntry> _leaderboard = <CommunityLeaderboardEntry>[];
  bool _loading = false;
  bool _membershipLoading = false;
  bool _mutatingMembership = false;
  String? _error;
  CommunityMember? _membership;

  List<CommunitySummary> get communities => _communities;
  List<CommunityFeedItem> get feed => _feed;
  List<CommunityLeaderboardEntry> get leaderboard => _leaderboard;
  bool get isLoading => _loading;
  bool get isMembershipLoading => _membershipLoading;
  bool get isMutatingMembership => _mutatingMembership;
  String? get error => _error;
  CommunityMember? get membership => _membership;
  bool get isMember => _membership?.isActive ?? false;

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

  Future<void> refreshMembership(int communityId) async {
    _setMembershipLoading(true);
    try {
      _membership = await _repository.loadMembership(communityId);
    } finally {
      _setMembershipLoading(false);
      notifyListeners();
    }
  }

  Future<void> joinCommunity(int communityId) async {
    _setMutatingMembership(true);
    try {
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
      await _repository.leaveCommunity(communityId);
      _membership = null;
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
    final item = await _repository.createPost(
      communityId,
      bodyMarkdown: bodyMarkdown,
      visibility: visibility,
      paywallTierId: paywallTierId,
    );

    _feed = <CommunityFeedItem>[item, ..._feed];
    notifyListeners();
  }

  Future<void> togglePostReaction(int communityId, int postId, {String reaction = 'like'}) async {
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
    _leaderboard = await _repository.loadLeaderboard(communityId, period: period);
    notifyListeners();
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

  @override
  void dispose() {
    unawaited(_repository.dispose());
    super.dispose();
  }
}
