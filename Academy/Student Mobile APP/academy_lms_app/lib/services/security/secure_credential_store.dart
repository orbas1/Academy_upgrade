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
  static const String _accessTokenExpiryKey = 'access_token_expires_at';
  static const String _refreshTokenExpiryKey = 'refresh_token_expires_at';

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

  Future<void> persistAccessTokenExpiry(DateTime? expiresAt) async {
    if (expiresAt == null) {
      await _storage.delete(key: _accessTokenExpiryKey);
      return;
    }

    await _storage.write(
      key: _accessTokenExpiryKey,
      value: expiresAt.toUtc().toIso8601String(),
    );
  }

  Future<void> persistRefreshToken(String? token) async {
    if (token == null || token.isEmpty) {
      await deleteRefreshToken();
      return;
    }

    _cachedRefreshToken = token;
    await _storage.write(key: _refreshTokenKey, value: token);
  }

  Future<void> persistRefreshTokenExpiry(DateTime? expiresAt) async {
    if (expiresAt == null) {
      await _storage.delete(key: _refreshTokenExpiryKey);
      return;
    }

    await _storage.write(
      key: _refreshTokenExpiryKey,
      value: expiresAt.toUtc().toIso8601String(),
    );
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

  Future<DateTime?> readAccessTokenExpiry() async {
    final value = await _storage.read(key: _accessTokenExpiryKey);
    if (value == null || value.isEmpty) {
      return null;
    }

    return DateTime.tryParse(value)?.toUtc();
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

  Future<DateTime?> readRefreshTokenExpiry() async {
    final value = await _storage.read(key: _refreshTokenExpiryKey);
    if (value == null || value.isEmpty) {
      return null;
    }

    return DateTime.tryParse(value)?.toUtc();
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
    await _storage.delete(key: _accessTokenExpiryKey);
  }

  Future<void> deleteRefreshToken() async {
    _cachedRefreshToken = null;
    await _storage.delete(key: _refreshTokenKey);
    await _storage.delete(key: _refreshTokenExpiryKey);
  }

  Future<void> clearAll() async {
    _cachedAccessToken = null;
    _cachedRefreshToken = null;
    await _storage.delete(key: _accessTokenKey);
    await _storage.delete(key: _refreshTokenKey);
    await _storage.delete(key: _accessTokenExpiryKey);
    await _storage.delete(key: _refreshTokenExpiryKey);
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
