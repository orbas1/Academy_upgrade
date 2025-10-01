import 'dart:async';
import 'dart:convert';

import 'package:shared_preferences/shared_preferences.dart';

import '../../config/data_protection.dart';
import 'secure_credential_store.dart';

typedef CredentialWiper = Future<void> Function();

class DataProtectionService {
  DataProtectionService._internal({
    DataProtectionConfiguration? configuration,
    SharedPreferences? preferences,
    CredentialWiper? credentialWiper,
    DateTime Function()? clock,
  })  : _configuration = configuration ?? DataProtectionConfiguration.instance,
        _preferences = preferences,
        _credentialWiper = credentialWiper,
        _clock = clock ?? DateTime.now;

  DataProtectionService.test({
    required DataProtectionConfiguration configuration,
    required SharedPreferences preferences,
    CredentialWiper? credentialWiper,
    DateTime Function()? clock,
  })  : _configuration = configuration,
        _preferences = preferences,
        _credentialWiper = credentialWiper,
        _clock = clock ?? DateTime.now;

  static final DataProtectionService instance = DataProtectionService._internal();

  final DataProtectionConfiguration _configuration;
  SharedPreferences? _preferences;
  final CredentialWiper? _credentialWiper;
  final DateTime Function() _clock;

  static const String _trackedKeysKey = 'data_protection.tracked_keys';
  static const String _lastEnforcedKey = 'data_protection.last_enforced_at';

  bool get secureWipeEnabled => _configuration.secureWipeOnLogout;

  Future<void> enforcePolicies() async {
    final prefs = await _ensurePrefs();
    final tracked = _loadTrackedKeys(prefs);
    final now = _clock().toUtc();

    final retentionDays = _configuration.personalDataRetentionDays;
    if (retentionDays <= 0 && tracked.isNotEmpty) {
      await _removeTrackedKeys(prefs, tracked.keys);
      tracked.clear();
    } else if (retentionDays > 0) {
      final cutoff = now.subtract(Duration(days: retentionDays));
      final keysToRemove = tracked.entries
          .where((entry) => entry.value.isBefore(cutoff))
          .map((entry) => entry.key)
          .toList(growable: false);

      if (keysToRemove.isNotEmpty) {
        await _removeTrackedKeys(prefs, keysToRemove);
        tracked.removeWhere((key, storedAt) => keysToRemove.contains(key));
      }
    }

    await _removeLegacySensitiveKeys(prefs);
    await _persistTrackedKeys(prefs, tracked);
    await prefs.setString(_lastEnforcedKey, now.toIso8601String());
  }

  Future<void> registerPersonalDataKey(String key) async {
    if (key.isEmpty) {
      return;
    }

    final prefs = await _ensurePrefs();
    final tracked = _loadTrackedKeys(prefs);
    tracked[key] = _clock().toUtc();
    await _persistTrackedKeys(prefs, tracked);
  }

  Future<void> registerPersonalDataKeys(Iterable<String> keys) async {
    final filtered = keys.where((key) => key.isNotEmpty).toList(growable: false);
    if (filtered.isEmpty) {
      return;
    }

    final prefs = await _ensurePrefs();
    final tracked = _loadTrackedKeys(prefs);
    final timestamp = _clock().toUtc();
    for (final key in filtered) {
      tracked[key] = timestamp;
    }
    await _persistTrackedKeys(prefs, tracked);
  }

  Future<void> wipeLocalFootprint() async {
    final prefs = await _ensurePrefs();
    final tracked = _loadTrackedKeys(prefs);

    if (tracked.isNotEmpty) {
      await _removeTrackedKeys(prefs, tracked.keys);
    }

    await _removeLegacySensitiveKeys(prefs);
    await _persistTrackedKeys(prefs, {});
    await prefs.remove(_lastEnforcedKey);

    if (_configuration.secureWipeOnLogout) {
      final wiper =
          _credentialWiper ?? () => SecureCredentialStore.instance.clearAll();
      await wiper();
    }
  }

  Future<SharedPreferences> _ensurePrefs() async {
    _preferences ??= await SharedPreferences.getInstance();
    return _preferences!;
  }

  Map<String, DateTime> _loadTrackedKeys(SharedPreferences prefs) {
    final raw = prefs.getString(_trackedKeysKey);
    if (raw == null || raw.isEmpty) {
      return <String, DateTime>{};
    }

    try {
      final Map<String, dynamic> decoded = jsonDecode(raw) as Map<String, dynamic>;
      final entries = <String, DateTime>{};
      decoded.forEach((key, value) {
        if (value is String) {
          final parsed = DateTime.tryParse(value);
          if (parsed != null) {
            entries[key] = parsed.toUtc();
          }
        }
      });
      return entries;
    } catch (_) {
      return <String, DateTime>{};
    }
  }

  Future<void> _persistTrackedKeys(
    SharedPreferences prefs,
    Map<String, DateTime> tracked,
  ) async {
    if (tracked.isEmpty) {
      await prefs.remove(_trackedKeysKey);
      return;
    }

    final payload = <String, String>{};
    tracked.forEach((key, storedAt) {
      payload[key] = storedAt.toUtc().toIso8601String();
    });

    await prefs.setString(_trackedKeysKey, jsonEncode(payload));
  }

  Future<void> _removeTrackedKeys(SharedPreferences prefs, Iterable<String> keys) async {
    for (final key in keys) {
      await prefs.remove(key);
    }
  }

  Future<void> _removeLegacySensitiveKeys(SharedPreferences prefs) async {
    for (final key in _configuration.legacySensitiveKeys) {
      if (prefs.containsKey(key)) {
        await prefs.remove(key);
      }
    }
  }
}
