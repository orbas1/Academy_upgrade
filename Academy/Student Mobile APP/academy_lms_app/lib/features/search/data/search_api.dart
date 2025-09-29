import 'dart:convert';

import 'package:academy_lms_app/constants.dart' as constants;
import 'package:academy_lms_app/features/search/models/search_response.dart';
import 'package:http/http.dart' as http;

class SearchApi {
  SearchApi({http.Client? client}) : _client = client ?? http.Client();

  final http.Client _client;

  Uri _buildUri(String path) {
    final base = constants.baseUrl.endsWith('/')
        ? constants.baseUrl.substring(0, constants.baseUrl.length - 1)
        : constants.baseUrl;

    return Uri.parse('$base$path');
  }

  Future<SearchResponse> query({
    required String index,
    required String query,
    required String visibilityToken,
    List<String> filters = const <String>[],
    int limit = 20,
    String? cursor,
    String? authToken,
  }) async {
    final headers = <String, String>{
      'Content-Type': 'application/json',
      'Accept': 'application/json',
    };

    if (authToken != null && authToken.isNotEmpty) {
      headers['Authorization'] = 'Bearer $authToken';
    }

    final body = <String, dynamic>{
      'index': index,
      'query': query,
      'visibility_token': visibilityToken,
      'filters': filters,
      'limit': limit,
    };

    if (cursor != null && cursor.isNotEmpty) {
      body['cursor'] = cursor;
    }

    final response = await _client.post(
      _buildUri('/api/v1/search/query'),
      headers: headers,
      body: jsonEncode(body),
    );

    if (response.statusCode != 200) {
      throw http.ClientException(
        'Search request failed with status ${response.statusCode}',
        response.request?.url,
      );
    }

    final decoded = jsonDecode(response.body) as Map<String, dynamic>;
    final data = decoded['data'] as Map<String, dynamic>? ?? <String, dynamic>{};

    return SearchResponse.fromJson(data);
  }

  Future<void> dispose() async {
    _client.close();
  }
}

