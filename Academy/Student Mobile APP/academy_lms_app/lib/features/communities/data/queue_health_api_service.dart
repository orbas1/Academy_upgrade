import 'dart:convert';

import 'package:academy_lms_app/constants.dart' as constants;
import 'package:http/http.dart' as http;
import 'package:academy_lms_app/services/observability/http_client_factory.dart';
import 'package:academy_lms_app/services/security/auth_session_manager.dart';

import '../models/queue_health_metric.dart';

class QueueHealthApiService {
  QueueHealthApiService({http.Client? client, String? authToken, AuthSessionManager? sessionManager})
      : _client = client != null
            ? HttpClientFactory.create(inner: client)
            : HttpClientFactory.create(),
        _authToken = authToken,
        _sessionManager = sessionManager ?? AuthSessionManager.instance;

  final http.Client _client;
  String? _authToken;
  final AuthSessionManager _sessionManager;

  void updateAuthToken(String? token) {
    if (token == null || token.isEmpty) {
      _authToken = null;
    } else {
      _authToken = token;
    }
  }

  Future<QueueHealthSummary> fetchSummary() async {
    final response = await _sendWithAuth(
      (headers) => _client.get(
        _buildUri('/api/v1/ops/queue-health'),
        headers: headers,
      ),
    );

    if (response.statusCode != 200) {
      throw http.ClientException('Unable to load queue health summary', response.request?.url);
    }

    final body = jsonDecode(response.body) as Map<String, dynamic>;
    return QueueHealthSummary.fromJson(body);
  }

  void dispose() {
    _client.close();
  }

  Uri _buildUri(String path, [Map<String, String?>? query]) {
    final cleanQuery = <String, String>{};
    if (query != null) {
      query.forEach((key, value) {
        if (value != null && value.isNotEmpty) {
          cleanQuery[key] = value;
        }
      });
    }

    final normalizedBase = constants.baseUrl.endsWith('/')
        ? constants.baseUrl.substring(0, constants.baseUrl.length - 1)
        : constants.baseUrl;

    return Uri.parse('$normalizedBase$path').replace(
      queryParameters: cleanQuery.isEmpty ? null : cleanQuery,
    );
  }

  Future<Map<String, String>> _headers({bool forceRefresh = false}) async {
    final headers = <String, String>{
      'Accept': 'application/json',
    };

    String? bearer;
    try {
      bearer = await _sessionManager.getValidAccessToken(forceRefresh: forceRefresh);
    } catch (_) {
      bearer = null;
    }

    final resolved = (bearer != null && bearer.isNotEmpty) ? bearer : _authToken;
    if (resolved != null && resolved.isNotEmpty) {
      headers['Authorization'] = 'Bearer $resolved';
    }

    return headers;
  }

  Future<http.Response> _sendWithAuth(
    Future<http.Response> Function(Map<String, String> headers) request,
  ) async {
    final initialHeaders = await _headers();
    http.Response response = await request(initialHeaders);

    if (response.statusCode == 401) {
      final retryHeaders = await _headers(forceRefresh: true);
      response = await request(retryHeaders);
    }

    return response;
  }
}
