import 'package:shared_preferences/shared_preferences.dart';

class CommunityOnboardingStore {
  CommunityOnboardingStore({SharedPreferences? preferences})
      : _preferences = preferences;

  static const String _completedKey = 'communities.onboarding.completed.v1';

  SharedPreferences? _preferences;

  Future<SharedPreferences> _ensurePrefs() async {
    if (_preferences != null) {
      return _preferences!;
    }
    _preferences = await SharedPreferences.getInstance();
    return _preferences!;
  }

  Future<bool> hasCompleted() async {
    final prefs = await _ensurePrefs();
    return prefs.getBool(_completedKey) ?? false;
  }

  Future<void> markCompleted() async {
    final prefs = await _ensurePrefs();
    await prefs.setBool(_completedKey, true);
  }

  Future<void> reset() async {
    final prefs = await _ensurePrefs();
    await prefs.remove(_completedKey);
  }
}
