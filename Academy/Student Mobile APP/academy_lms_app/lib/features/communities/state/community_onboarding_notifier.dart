import 'dart:async';

import 'package:flutter/foundation.dart';

import '../data/community_onboarding_store.dart';
import '../models/community_summary.dart';
import 'community_notifier.dart';

class CommunityOnboardingNotifier extends ChangeNotifier {
  CommunityOnboardingNotifier({CommunityOnboardingStore? store})
      : _store = store ?? CommunityOnboardingStore();

  final CommunityOnboardingStore _store;
  bool _initialized = false;
  bool _loadingRecommendations = false;
  bool _completing = false;
  bool _hasCompleted = false;
  String? _error;

  List<CommunitySummary> _recommendations = <CommunitySummary>[];
  final Set<int> _selectedCommunityIds = <int>{};

  bool get isInitialized => _initialized;
  bool get isLoadingRecommendations => _loadingRecommendations;
  bool get isCompleting => _completing;
  bool get hasCompleted => _hasCompleted;
  bool get shouldPrompt => _initialized && !_hasCompleted;
  String? get error => _error;

  List<CommunitySummary> get recommendations => _recommendations;
  Set<int> get selectedCommunityIds => Set<int>.unmodifiable(_selectedCommunityIds);

  Future<void> initialize() async {
    if (_initialized) {
      return;
    }
    _hasCompleted = await _store.hasCompleted();
    _initialized = true;
    notifyListeners();
  }

  Future<void> bootstrap(CommunityNotifier notifier) async {
    await initialize();
    if (_hasCompleted || _loadingRecommendations) {
      return;
    }

    _loadingRecommendations = true;
    _error = null;
    _recommendations = <CommunitySummary>[];
    _selectedCommunityIds.clear();
    notifyListeners();

    try {
      final candidates = await _fetchCandidates(notifier);
      _recommendations = candidates;
      if (_recommendations.isEmpty) {
        _error = 'No recommended communities are available yet. Check back soon or skip onboarding.';
      }
    } catch (error) {
      _error = error.toString();
    } finally {
      _loadingRecommendations = false;
      notifyListeners();
    }
  }

  Future<List<CommunitySummary>> _fetchCandidates(CommunityNotifier notifier) async {
    final filters = <String>['recommended', 'trending', 'all'];
    final seenIds = <int>{};
    final results = <CommunitySummary>[];

    for (final filter in filters) {
      final response = await notifier.repository.loadCommunities(
        filter: filter,
        resetCursor: true,
        pageSize: 25,
      );
      for (final summary in response.items) {
        if (summary.isMember || seenIds.contains(summary.id)) {
          continue;
        }
        seenIds.add(summary.id);
        results.add(summary);
      }
      if (results.length >= 6) {
        break;
      }
    }

    if (results.length > 12) {
      return results.sublist(0, 12);
    }
    return results;
  }

  void toggleCommunity(int communityId) {
    if (_hasCompleted) {
      return;
    }
    if (_selectedCommunityIds.contains(communityId)) {
      _selectedCommunityIds.remove(communityId);
    } else {
      _selectedCommunityIds.add(communityId);
    }
    notifyListeners();
  }

  bool isSelected(int communityId) => _selectedCommunityIds.contains(communityId);

  Future<bool> complete({
    required CommunityNotifier notifier,
    bool skip = false,
  }) async {
    if (_completing) {
      return false;
    }

    _completing = true;
    _error = null;
    notifyListeners();

    try {
      if (!skip && _selectedCommunityIds.isNotEmpty) {
        for (final communityId in _selectedCommunityIds) {
          await notifier.joinCommunity(communityId);
        }
      }
      await _store.markCompleted();
      _hasCompleted = true;
      _selectedCommunityIds.clear();
      return true;
    } catch (error) {
      _error = error.toString();
      return false;
    } finally {
      _completing = false;
      notifyListeners();
    }
  }
}
