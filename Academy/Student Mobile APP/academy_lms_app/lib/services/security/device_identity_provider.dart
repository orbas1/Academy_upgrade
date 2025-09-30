import 'dart:io';

import 'package:device_info_plus/device_info_plus.dart';
import 'package:flutter/foundation.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:uuid/uuid.dart';

import '../../config/app_configuration.dart';

class DeviceIdentity {
  const DeviceIdentity({
    required this.deviceId,
    required this.deviceName,
    required this.platform,
    required this.appVersion,
  });

  final String deviceId;
  final String deviceName;
  final String platform;
  final String appVersion;

  Map<String, String> toHeaders() {
    return {
      'X-Device-Id': deviceId,
      'X-Device-Name': deviceName,
      'X-Device-Platform': platform,
      'X-App-Version': appVersion,
    };
  }
}

class DeviceIdentityProvider {
  DeviceIdentityProvider._internal({
    DeviceInfoPlugin? deviceInfo,
    FlutterSecureStorage? storage,
    Uuid? uuid,
    AppConfiguration? configuration,
  })  : _deviceInfo = deviceInfo ?? DeviceInfoPlugin(),
        _storage = storage ??
            const FlutterSecureStorage(
              aOptions: AndroidOptions(encryptedSharedPreferences: true),
              iOptions: IOSOptions(
                accessibility: KeychainAccessibility.afterFirstUnlockThisDeviceOnly,
              ),
              mOptions: MacOsOptions(
                accessibility: KeychainAccessibility.afterFirstUnlock,
              ),
            ),
        _uuid = uuid ?? const Uuid(),
        _configuration = configuration ?? AppConfiguration.instance;

  static final DeviceIdentityProvider instance = DeviceIdentityProvider._internal();

  static const String _deviceIdKey = 'security.device_id';

  final DeviceInfoPlugin _deviceInfo;
  final FlutterSecureStorage _storage;
  final Uuid _uuid;
  final AppConfiguration _configuration;

  DeviceIdentity? _cached;

  Future<DeviceIdentity> getIdentity() async {
    if (_cached != null) {
      return _cached!;
    }

    final id = await _loadOrCreateId();
    final deviceName = await _resolveDeviceName();
    final platform = _resolvePlatform();

    _cached = DeviceIdentity(
      deviceId: id,
      deviceName: deviceName,
      platform: platform,
      appVersion: _configuration.appVersion,
    );

    return _cached!;
  }

  Future<String> _loadOrCreateId() async {
    final existing = await _storage.read(key: _deviceIdKey);
    if (existing != null && existing.isNotEmpty) {
      return existing;
    }

    final generated = _uuid.v4();
    await _storage.write(key: _deviceIdKey, value: generated);
    return generated;
  }

  Future<String> _resolveDeviceName() async {
    try {
      if (Platform.isAndroid) {
        final info = await _deviceInfo.androidInfo;
        final brand = (info.brand ?? info.manufacturer ?? '').trim();
        final model = info.model?.trim() ?? '';
        return _joinNonEmpty([brand, model], fallback: 'Android Device');
      }
      if (Platform.isIOS) {
        final info = await _deviceInfo.iosInfo;
        final name = info.utsname.machine?.trim();
        return name == null || name.isEmpty ? 'iOS Device' : name;
      }
      if (Platform.isMacOS) {
        final info = await _deviceInfo.macOsInfo;
        return _joinNonEmpty([info.model, info.arch]);
      }
      if (Platform.isWindows) {
        final info = await _deviceInfo.windowsInfo;
        return _joinNonEmpty([info.computerName, info.productName], fallback: 'Windows');
      }
      if (Platform.isLinux) {
        final info = await _deviceInfo.linuxInfo;
        return _joinNonEmpty([info.prettyName, info.name], fallback: 'Linux');
      }
    } catch (error) {
      debugPrint('Failed to resolve device name: $error');
    }

    return 'Unknown Device';
  }

  String _resolvePlatform() {
    if (kIsWeb) {
      return 'web';
    }
    if (Platform.isAndroid) {
      return 'android';
    }
    if (Platform.isIOS) {
      return 'ios';
    }
    if (Platform.isMacOS) {
      return 'macos';
    }
    if (Platform.isWindows) {
      return 'windows';
    }
    if (Platform.isLinux) {
      return 'linux';
    }
    return 'unknown';
  }

  String _joinNonEmpty(List<String?> values, {String fallback = 'Device'}) {
    final filtered = values.where((value) => value != null && value.trim().isNotEmpty).map((value) => value!.trim());
    final joined = filtered.join(' ');
    return joined.isEmpty ? fallback : joined;
  }
}
