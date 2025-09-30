import 'dart:async';
import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;

import '../../config/app_configuration.dart';
import '../observability/http_client_factory.dart';
import 'auth_session.dart';
import 'secure_credential_store.dart';

class AuthSessionManager extends ChangeNotifier {
  AuthSessionManager._internal({
    http.Client? client,
    SecureCredentialStore? credentialStore,
    AppConfiguration? configuration,
  })  : _client = client != null
            ? HttpClientFactory.create(inner: client)
            : HttpClientFactory.create(),
        _store = credentialStore ?? SecureCredentialStore.instance,
        _configuration = configuration ?? AppConfiguration.instance;

  static final AuthSessionManager instance = AuthSessionManager._internal();

  final http.Client _client;
  final SecureCredentialStore _store;
  final AppConfiguration _configuration;

  AuthSession? _session;
  bool _initialized = false;
  Completer<void>? _refreshCompleter;

  AuthSession? get currentSession => _session;

  Future<AuthSession?> loadSession({bool force = false}) async {
    if (!_initialized || force) {
      await _hydrateFromStore();
      _initialized = true;
    }

    return _session;
  }

  Future<void> persistSession(AuthSession session) async {
    await _store.persistAccessToken(session.accessToken);
    await _store.persistAccessTokenExpiry(session.accessTokenExpiresAt);
    await _store.persistRefreshToken(session.refreshToken);
    await _store.persistRefreshTokenExpiry(session.refreshTokenExpiresAt);

    _session = session;
    _initialized = true;
    notifyListeners();
  }

  Future<void> clearSession() async {
    await _store.clearAll();
    _session = null;
    _initialized = true;
    notifyListeners();
  }

  Future<String?> getValidAccessToken({bool forceRefresh = false}) async {
    final session = await loadSession();
    if (session == null) {
      return null;
    }

    if (forceRefresh || session.shouldRefreshAccessToken) {
      final refreshed = await _refreshInternal(force: true);
      if (!refreshed) {
        return _session?.accessToken;
      }
    }

    return _session?.accessToken;
  }

  Future<String> requireAccessToken({bool forceRefresh = false}) async {
    final token = await getValidAccessToken(forceRefresh: forceRefresh);
    if (token == null || token.isEmpty) {
      throw const MissingTokenException('Access token is required but not available.');
    }
    return token;
  }

  Future<bool> refreshSession() {
    return _refreshInternal(force: true);
  }

  Future<void> disposeAsync() async {
    _client.close();
  }

  Future<void> _hydrateFromStore() async {
    final accessToken = await _store.readAccessToken();
    if (accessToken == null || accessToken.isEmpty) {
      _session = null;
      return;
    }

    final refreshToken = await _store.readRefreshToken();
    final accessExpiry = await _store.readAccessTokenExpiry();
    final refreshExpiry = await _store.readRefreshTokenExpiry();

    _session = AuthSession(
      accessToken: accessToken,
      refreshToken: refreshToken,
      accessTokenExpiresAt: accessExpiry,
      refreshTokenExpiresAt: refreshExpiry,
    );
  }

  Future<bool> _refreshInternal({bool force = false}) async {
    if (_refreshCompleter != null) {
      await _refreshCompleter!.future;
      return _session != null && !_session!.shouldRefreshAccessToken;
    }

    final completer = Completer<void>();
    _refreshCompleter = completer;

    try {
      final session = await loadSession();
      if (!force && (session == null || !session.shouldRefreshAccessToken)) {
        return session != null;
      }

      final refreshed = await _performRefresh();
      return refreshed;
    } finally {
      if (!completer.isCompleted) {
        completer.complete();
      }
      _refreshCompleter = null;
    }
  }

  Future<bool> _performRefresh() async {
    final refreshToken = await _store.readRefreshToken();
    if (refreshToken == null || refreshToken.isEmpty) {
      return false;
    }

    final endpoint = _configuration.oauthTokenEndpoint;
    final requestBody = <String, String>{
      'grant_type': 'refresh_token',
      'refresh_token': refreshToken,
    };

    if (_configuration.oauthClientId != null && _configuration.oauthClientId!.isNotEmpty) {
      requestBody['client_id'] = _configuration.oauthClientId!;
    }
    if (_configuration.oauthClientSecret != null && _configuration.oauthClientSecret!.isNotEmpty) {
      requestBody['client_secret'] = _configuration.oauthClientSecret!;
    }
    if (_configuration.oauthScopes != null && _configuration.oauthScopes!.isNotEmpty) {
      requestBody['scope'] = _configuration.oauthScopes!;
    }

    final response = await _client.post(
      endpoint,
      headers: const <String, String>{
        'Accept': 'application/json',
        'Content-Type': 'application/x-www-form-urlencoded',
      },
      body: requestBody,
    );

    if (response.statusCode != 200) {
      debugPrint('Token refresh failed with status ${response.statusCode}: ${response.body}');
      if (response.statusCode == 400 || response.statusCode == 401) {
        await clearSession();
      }
      return false;
    }

    final Map<String, dynamic> payload = jsonDecode(response.body) as Map<String, dynamic>;
    final session = AuthSession.fromOAuthJson(payload, fallbackRefreshToken: refreshToken);
    await persistSession(session);
    return true;
  }
}
