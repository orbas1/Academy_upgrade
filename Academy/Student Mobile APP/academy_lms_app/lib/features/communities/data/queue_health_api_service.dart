import 'dart:convert';

import 'package:academy_lms_app/constants.dart' as constants;
import 'package:http/http.dart' as http;

import '../models/queue_health_metric.dart';

class QueueHealthApiService {
  QueueHealthApiService({http.Client? client, String? authToken})
      : _client = client ?? http.Client(),
        _authToken = authToken;

  final http.Client _client;
  String? _authToken;

  void updateAuthToken(String? token) {
    if (token == null || token.isEmpty) {
      _authToken = null;
    } else {
      _authToken = token;
    }
  }

  Future<QueueHealthSummary> fetchSummary() async {
    final response = await _client.get(
      _buildUri('/api/v1/ops/queue-health'),
      headers: _headers(),
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

  Map<String, String> _headers() {
    final headers = <String, String>{
      'Accept': 'application/json',
    };

    if (_authToken != null && _authToken!.isNotEmpty) {
      headers['Authorization'] = 'Bearer $_authToken';
    }

    return headers;
  }
}
