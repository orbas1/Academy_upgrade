import 'dart:convert';
import 'dart:io';

import 'package:academy_lms_app/config/data_protection.dart';
import 'package:academy_lms_app/services/security/data_protection_service.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:shared_preferences/shared_preferences.dart';
import 'package:path/path.dart' as p;

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  setUp(() {
    SharedPreferences.setMockInitialValues({});
  });

  test('enforcePolicies prunes expired entries and keeps active ones', () async {
    final prefs = await SharedPreferences.getInstance();
    var now = DateTime.utc(2024, 1, 1, 12);

    final service = DataProtectionService.test(
      configuration: DataProtectionConfiguration.override(
        personalDataRetentionDays: 30,
        secureWipeOnLogout: true,
        legacySensitiveKeys: ['password'],
      ),
      preferences: prefs,
      credentialWiper: () async {},
      clock: () => now,
    );

    await prefs.setString('stale', 'value');
    await service.registerPersonalDataKey('stale');

    now = now.add(const Duration(days: 45));
    await prefs.setString('fresh', 'value');
    await service.registerPersonalDataKey('fresh');

    await prefs.setString('password', 'legacy');

    await service.enforcePolicies();

    expect(prefs.containsKey('stale'), isFalse);
    expect(prefs.containsKey('fresh'), isTrue);
    expect(prefs.containsKey('password'), isFalse);

    final trackedRaw = prefs.getString('data_protection.tracked_keys');
    expect(trackedRaw, isNotNull);
    final tracked = Map<String, dynamic>.from(jsonDecode(trackedRaw!));
    expect(tracked.keys, contains('fresh'));
    expect(tracked.keys, isNot(contains('stale')));
  });

  test('wipeLocalFootprint clears tracked data and honours secure wipe flag', () async {
    bool wipeCalled = false;
    final prefs = await SharedPreferences.getInstance();
    final configuration = DataProtectionConfiguration.override(
      personalDataRetentionDays: 30,
      secureWipeOnLogout: true,
      legacySensitiveKeys: const [],
    );

    final service = DataProtectionService.test(
      configuration: configuration,
      preferences: prefs,
      credentialWiper: () async {
        wipeCalled = true;
      },
      clock: () => DateTime.utc(2024, 1, 1),
    );

    await prefs.setString('user', 'value');
    await service.registerPersonalDataKey('user');

    await service.wipeLocalFootprint();

    expect(prefs.containsKey('user'), isFalse);
    expect(prefs.getString('data_protection.tracked_keys'), isNull);
    expect(wipeCalled, isTrue);

    SharedPreferences.setMockInitialValues({});
    wipeCalled = false;
    final prefsDisabled = await SharedPreferences.getInstance();
    final disabledService = DataProtectionService.test(
      configuration: DataProtectionConfiguration.override(
        personalDataRetentionDays: 30,
        secureWipeOnLogout: false,
        legacySensitiveKeys: const [],
      ),
      preferences: prefsDisabled,
      credentialWiper: () async {
        wipeCalled = true;
      },
      clock: () => DateTime.utc(2024, 1, 1),
    );

    await prefsDisabled.setString('user', 'value');
    await disabledService.registerPersonalDataKey('user');

    await disabledService.wipeLocalFootprint();

    expect(prefsDisabled.containsKey('user'), isFalse);
    expect(wipeCalled, isFalse);
  });

  test('enforcePolicies prunes cache directories based on retention', () async {
    final prefs = await SharedPreferences.getInstance();
    var now = DateTime.utc(2024, 3, 1);

    final tempRoot = await Directory.systemTemp.createTemp('cache-retention');
    addTearDown(() async {
      if (await tempRoot.exists()) {
        await tempRoot.delete(recursive: true);
      }
    });

    final downloadsDir = Directory(p.join(tempRoot.path, 'downloads'))..createSync(recursive: true);
    final staleFile = File(p.join(downloadsDir.path, 'stale.json'))..writeAsStringSync('old');
    staleFile.setLastModifiedSync(now.subtract(const Duration(days: 90)));
    final freshFile = File(p.join(downloadsDir.path, 'fresh.json'))..writeAsStringSync('new');

    final configuration = DataProtectionConfiguration.override(
      personalDataRetentionDays: 30,
      secureWipeOnLogout: false,
      legacySensitiveKeys: const [],
      cacheDirectoryNames: const ['downloads'],
    );

    final service = DataProtectionService.test(
      configuration: configuration,
      preferences: prefs,
      clock: () => now,
      directoryResolver: (_) async => [downloadsDir],
    );

    await service.enforcePolicies();

    expect(staleFile.existsSync(), isFalse);
    expect(freshFile.existsSync(), isTrue);

    await service.wipeLocalFootprint();
    expect(downloadsDir.existsSync(), isFalse);
  });
}
