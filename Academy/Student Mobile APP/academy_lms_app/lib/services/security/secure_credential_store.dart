import 'dart:async';

import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:shared_preferences/shared_preferences.dart';

/// Provides a hardened credential store backed by [FlutterSecureStorage]
/// with transparent migration from legacy [SharedPreferences] tokens.
class SecureCredentialStore {
  SecureCredentialStore._internal({FlutterSecureStorage? storage})
      : _storage = storage ??
            const FlutterSecureStorage(
              aOptions: AndroidOptions(
                encryptedSharedPreferences: true,
              ),
              iOptions: IOSOptions(
                accessibility: KeychainAccessibility.afterFirstUnlockThisDeviceOnly,
              ),
              mOptions: MacOsOptions(
                accessibility: KeychainAccessibility.afterFirstUnlock,
              ),
            );

  static final SecureCredentialStore instance = SecureCredentialStore._internal();

  static const String _accessTokenKey = 'access_token';
  static const String _refreshTokenKey = 'refresh_token';

  final FlutterSecureStorage _storage;
  String? _cachedAccessToken;
  String? _cachedRefreshToken;

  Future<void> persistAccessToken(String? token) async {
    if (token == null || token.isEmpty) {
      await deleteAccessToken();
      return;
    }

    _cachedAccessToken = token;
    await _storage.write(key: _accessTokenKey, value: token);
  }

  Future<void> persistRefreshToken(String? token) async {
    if (token == null || token.isEmpty) {
      await deleteRefreshToken();
      return;
    }

    _cachedRefreshToken = token;
    await _storage.write(key: _refreshTokenKey, value: token);
  }

  Future<String?> readAccessToken() async {
    if (_cachedAccessToken != null) {
      return _cachedAccessToken;
    }

    final migrated = await _migrateLegacyToken(_accessTokenKey);
    if (migrated != null) {
      _cachedAccessToken = migrated;
      return migrated;
    }

    _cachedAccessToken = await _storage.read(key: _accessTokenKey);
    return _cachedAccessToken;
  }

  Future<String?> readRefreshToken() async {
    if (_cachedRefreshToken != null) {
      return _cachedRefreshToken;
    }

    final migrated = await _migrateLegacyToken(_refreshTokenKey);
    if (migrated != null) {
      _cachedRefreshToken = migrated;
      return migrated;
    }

    _cachedRefreshToken = await _storage.read(key: _refreshTokenKey);
    return _cachedRefreshToken;
  }

  Future<String> requireAccessToken() async {
    final token = await readAccessToken();
    if (token == null || token.isEmpty) {
      throw const MissingTokenException('Access token is required but missing.');
    }
    return token;
  }

  Future<void> deleteAccessToken() async {
    _cachedAccessToken = null;
    await _storage.delete(key: _accessTokenKey);
  }

  Future<void> deleteRefreshToken() async {
    _cachedRefreshToken = null;
    await _storage.delete(key: _refreshTokenKey);
  }

  Future<void> clearAll() async {
    _cachedAccessToken = null;
    _cachedRefreshToken = null;
    await _storage.delete(key: _accessTokenKey);
    await _storage.delete(key: _refreshTokenKey);
  }

  Future<String?> _migrateLegacyToken(String key) async {
    final prefs = await SharedPreferences.getInstance();
    final legacy = prefs.getString(key);
    if (legacy == null || legacy.isEmpty) {
      return null;
    }

    await _storage.write(key: key, value: legacy);
    await prefs.remove(key);
    return legacy;
  }
}

class MissingTokenException implements Exception {
  const MissingTokenException(this.message);

  final String message;

  @override
  String toString() => 'MissingTokenException: $message';
}
