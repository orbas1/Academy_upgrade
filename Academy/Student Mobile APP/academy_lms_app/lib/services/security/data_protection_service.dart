import 'dart:async';
import 'dart:convert';
import 'dart:io';

import 'package:shared_preferences/shared_preferences.dart';

import 'package:path/path.dart' as p;
import 'package:path_provider/path_provider.dart' as path_provider;

import '../../config/data_protection.dart';
import 'secure_credential_store.dart';

typedef CredentialWiper = Future<void> Function();

class DataProtectionService {
  DataProtectionService._internal({
    DataProtectionConfiguration? configuration,
    SharedPreferences? preferences,
    CredentialWiper? credentialWiper,
    DateTime Function()? clock,
    Future<List<Directory>> Function(List<String> names)? directoryResolver,
  })  : _configuration = configuration ?? DataProtectionConfiguration.instance,
        _preferences = preferences,
        _credentialWiper = credentialWiper,
        _clock = clock ?? DateTime.now,
        _directoryResolver =
            directoryResolver ?? _defaultDirectoryResolver;

  DataProtectionService.test({
    required DataProtectionConfiguration configuration,
    required SharedPreferences preferences,
    CredentialWiper? credentialWiper,
    DateTime Function()? clock,
    Future<List<Directory>> Function(List<String> names)? directoryResolver,
  })  : _configuration = configuration,
        _preferences = preferences,
        _credentialWiper = credentialWiper,
        _clock = clock ?? DateTime.now,
        _directoryResolver = directoryResolver ?? ((_) async => <Directory>[]);

  static final DataProtectionService instance = DataProtectionService._internal();

  final DataProtectionConfiguration _configuration;
  SharedPreferences? _preferences;
  final CredentialWiper? _credentialWiper;
  final DateTime Function() _clock;
  final Future<List<Directory>> Function(List<String> names) _directoryResolver;

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

    await _enforceCacheRetention();
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

    await _wipeCacheDirectories();
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

  Future<void> _enforceCacheRetention() async {
    final directoryNames = _configuration.cacheDirectoryNames;
    if (directoryNames.isEmpty) {
      return;
    }

    final directories = await _directoryResolver(directoryNames);
    if (directories.isEmpty) {
      return;
    }

    final retentionDays = _configuration.personalDataRetentionDays;
    final cutoff = retentionDays > 0
        ? _clock().toUtc().subtract(Duration(days: retentionDays))
        : null;

    for (final directory in directories) {
      await _cleanDirectory(directory, cutoff);
    }
  }

  Future<void> _cleanDirectory(Directory directory, DateTime? cutoff) async {
    try {
      if (!await directory.exists()) {
        return;
      }

      if (cutoff == null) {
        await directory.delete(recursive: true);
        return;
      }

      await for (final entity
          in directory.list(recursive: true, followLinks: false)) {
        try {
          final stat = await entity.stat();
          if (stat.modified.isBefore(cutoff)) {
            await entity.delete(recursive: true);
          }
        } catch (_) {
          // ignore and continue with next entity
        }
      }
    } catch (_) {
      // Swallow filesystem errors to avoid crashing the app during retention.
    }
  }

  Future<void> _wipeCacheDirectories() async {
    final directories = await _directoryResolver(_configuration.cacheDirectoryNames);
    for (final directory in directories) {
      try {
        if (await directory.exists()) {
          await directory.delete(recursive: true);
        }
      } catch (_) {
        // ignore
      }
    }
  }

  static Future<List<Directory>> _defaultDirectoryResolver(
    List<String> names,
  ) async {
    if (names.isEmpty) {
      return <Directory>[];
    }

    final resolved = <Directory>[];
    final uniqueNames = names.toSet();

    Directory? documents;
    Directory? support;
    Directory? tempDir;

    try {
      documents = await path_provider.getApplicationDocumentsDirectory();
    } catch (_) {}

    try {
      support = await path_provider.getApplicationSupportDirectory();
    } catch (_) {}

    try {
      tempDir = await path_provider.getTemporaryDirectory();
    } catch (_) {}

    final bases = <Directory?>[documents, support, tempDir];

    for (final name in uniqueNames) {
      final trimmed = name.trim();
      if (trimmed.isEmpty) {
        continue;
      }

      final candidate = Directory(trimmed);
      if (candidate.isAbsolute) {
        resolved.add(candidate);
        continue;
      }

      for (final base in bases) {
        if (base == null) {
          continue;
        }

        resolved.add(Directory(p.join(base.path, trimmed)));
      }
    }

    return resolved;
  }
}
