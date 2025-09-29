import 'dart:convert';

import 'package:academy_lms_app/constants.dart' as constants;
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

class StorageRestoreResult {
  StorageRestoreResult({
    required this.accepted,
    required this.message,
    this.estimatedMinutes = 720,
  });

  final bool accepted;
  final String message;
  final int estimatedMinutes;
}

class StorageRecoveryService {
  StorageRecoveryService({http.Client? client}) : _client = client ?? http.Client();

  final http.Client _client;

  Future<StorageRestoreResult> requestRestoreFromUrl(String objectUrl,
      {String profile = 'media'}) async {
    final uri = Uri.parse(objectUrl);
    var key = uri.path;
    if (key.startsWith('/')) {
      key = key.substring(1);
    }

    if (key.isEmpty) {
      throw ArgumentError('Unable to derive storage key from URL: $objectUrl');
    }

    return requestRestore(key, profile: profile);
  }

  Future<StorageRestoreResult> requestRestore(String objectKey,
      {String profile = 'media'}) async {
    final prefs = await SharedPreferences.getInstance();
    final token = prefs.getString('access_token');

    if (token == null || token.isEmpty) {
      throw StateError('Authentication token missing; user must re-authenticate.');
    }

    final normalizedBase = constants.baseUrl.endsWith('/')
        ? constants.baseUrl.substring(0, constants.baseUrl.length - 1)
        : constants.baseUrl;

    final response = await _client.post(
      Uri.parse('$normalizedBase/api/storage/restore'),
      headers: {
        'Content-Type': 'application/json',
        'Accept': 'application/json',
        'Authorization': 'Bearer $token',
      },
      body: json.encode({
        'profile': profile,
        'key': objectKey,
      }),
    );

    if (response.statusCode >= 400) {
      throw StateError(
          'Restore request failed with status ${response.statusCode}: ${response.body}');
    }

    final payload = json.decode(response.body) as Map<String, dynamic>;

    return StorageRestoreResult(
      accepted: payload['status'] == 'accepted',
      message: payload['message'] as String? ?? 'Restore request submitted.',
      estimatedMinutes: payload['eta_minutes'] is int
          ? payload['eta_minutes'] as int
          : 720,
    );
  }
}
