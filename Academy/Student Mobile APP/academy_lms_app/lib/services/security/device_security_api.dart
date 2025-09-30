import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;

import '../../config/app_configuration.dart';
import '../../models/security/device_session.dart';
import '../observability/http_client_factory.dart';
import 'auth_session_manager.dart';
import 'device_identity_provider.dart';

class DeviceSecurityApi {
  DeviceSecurityApi({
    http.Client? client,
    AuthSessionManager? sessionManager,
    DeviceIdentityProvider? identityProvider,
    AppConfiguration? configuration,
  })  : _client = client != null
            ? HttpClientFactory.create(inner: client)
            : HttpClientFactory.create(),
        _sessionManager = sessionManager ?? AuthSessionManager.instance,
        _identityProvider = identityProvider ?? DeviceIdentityProvider.instance,
        _configuration = configuration ?? AppConfiguration.instance;

  final http.Client _client;
  final AuthSessionManager _sessionManager;
  final DeviceIdentityProvider _identityProvider;
  final AppConfiguration _configuration;

  Future<List<DeviceSession>> fetchSessions() async {
    final token = await _sessionManager.requireAccessToken();
    final identity = await _identityProvider.getIdentity();

    final response = await _client.get(
      _configuration.resolveApiPath('/v1/security/device-sessions'),
      headers: {
        'Authorization': 'Bearer $token',
        'Accept': 'application/json',
        ...identity.toHeaders(),
      },
    );

    if (response.statusCode != 200) {
      throw HttpException('Failed to load device sessions: ${response.statusCode}');
    }

    final Map<String, dynamic> jsonBody = jsonDecode(response.body) as Map<String, dynamic>;
    final List<dynamic> data = jsonBody['data'] as List<dynamic>? ?? <dynamic>[];

    return data.map((entry) => DeviceSession.fromJson(entry as Map<String, dynamic>)).toList();
  }

  Future<DeviceSession> toggleTrust(int id, bool trusted) async {
    final token = await _sessionManager.requireAccessToken();
    final identity = await _identityProvider.getIdentity();

    final response = await _client.patch(
      _configuration.resolveApiPath('/v1/security/device-sessions/$id'),
      headers: {
        'Authorization': 'Bearer $token',
        'Accept': 'application/json',
        'Content-Type': 'application/json',
        ...identity.toHeaders(),
      },
      body: jsonEncode({'trusted': trusted}),
    );

    if (response.statusCode != 200) {
      throw HttpException('Failed to update device trust state (${response.statusCode})');
    }

    final Map<String, dynamic> payload = jsonDecode(response.body) as Map<String, dynamic>;
    final deviceJson = payload['device'] as Map<String, dynamic>?;

    if (deviceJson == null) {
      throw const FormatException('Malformed response from device trust endpoint.');
    }

    return DeviceSession.fromJson(deviceJson);
  }

  Future<void> revoke(int id) async {
    final token = await _sessionManager.requireAccessToken();
    final identity = await _identityProvider.getIdentity();

    final response = await _client.delete(
      _configuration.resolveApiPath('/v1/security/device-sessions/$id'),
      headers: {
        'Authorization': 'Bearer $token',
        'Accept': 'application/json',
        ...identity.toHeaders(),
      },
    );

    if (response.statusCode != 200) {
      throw HttpException('Failed to revoke device session (${response.statusCode})');
    }
  }
}

class HttpException implements Exception {
  HttpException(this.message);

  final String message;

  @override
  String toString() => 'HttpException: $message';
}
