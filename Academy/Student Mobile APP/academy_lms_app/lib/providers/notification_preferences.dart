import 'package:flutter/foundation.dart';

import '../features/communities/data/community_repository.dart';
import '../features/communities/models/community_notification_preferences.dart';

class NotificationPreferencesProvider extends ChangeNotifier {
  NotificationPreferencesProvider({CommunityRepository? repository})
      : _repository = repository ?? CommunityRepository();

  final CommunityRepository _repository;
  final Map<int, CommunityNotificationPreferences> _cache = <int, CommunityNotificationPreferences>{};
  bool _loading = false;
  String? _error;

  bool get isLoading => _loading;
  String? get error => _error;

  CommunityNotificationPreferences? preferencesFor(int communityId) => _cache[communityId];

  Future<void> hydrate(int communityId) async {
    _setLoading(true);
    try {
      final prefs = await _repository.loadNotificationPreferences(communityId);
      _cache[communityId] = prefs;
      _error = null;
    } catch (err) {
      _error = err.toString();
    } finally {
      _setLoading(false);
      notifyListeners();
    }
  }

  Future<void> update(int communityId, CommunityNotificationPreferences preferences) async {
    _setLoading(true);
    try {
      final updated = await _repository.updateNotificationPreferences(
        communityId,
        preferences: preferences,
      );
      _cache[communityId] = updated;
      _error = null;
    } catch (err) {
      _error = err.toString();
    } finally {
      _setLoading(false);
      notifyListeners();
    }
  }

  Future<void> reset(int communityId) async {
    _setLoading(true);
    try {
      await _repository.resetNotificationPreferences(communityId);
      _cache.remove(communityId);
      _error = null;
    } catch (err) {
      _error = err.toString();
    } finally {
      _setLoading(false);
      notifyListeners();
    }
  }

  void _setLoading(bool value) {
    _loading = value;
  }

  @override
  void dispose() {
    _cache.clear();
    super.dispose();
  }
}
