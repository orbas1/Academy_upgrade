import 'dart:convert';

import 'package:academy_lms_app/constants.dart' as constants;
import 'package:academy_lms_app/features/search/models/search_visibility_token.dart';
import 'package:academy_lms_app/services/api_envelope.dart';
import 'package:http/http.dart' as http;

class SearchVisibilityApi {
  SearchVisibilityApi({http.Client? client})
      : _client = client ?? http.Client();

  final http.Client _client;

  Uri _buildUri(String path) {
    final normalizedBase = constants.baseUrl.endsWith('/')
        ? constants.baseUrl.substring(0, constants.baseUrl.length - 1)
        : constants.baseUrl;

    return Uri.parse('$normalizedBase$path');
  }

  Future<SearchVisibilityToken> fetchVisibilityToken({String? authToken}) async {
    final headers = <String, String>{'Accept': 'application/json'};

    if (authToken != null && authToken.isNotEmpty) {
      headers['Authorization'] = 'Bearer $authToken';
    }

    final response = await _client.get(
      _buildUri('/api/v1/search/visibility-token'),
      headers: headers,
    );

    if (response.statusCode != 200) {
      throw http.ClientException(
        'Failed to fetch search visibility token',
        response.request?.url,
      );
    }

    final envelope = ApiEnvelope.fromJson(response.body);

    if (!envelope.isSuccess) {
      throw http.ClientException(
        envelope.firstErrorMessage ?? 'Failed to fetch search visibility token',
        response.request?.url,
      );
    }

    final data = envelope.requireMapData();

    return SearchVisibilityToken.fromJson(data);
  }

  Future<void> dispose() async {
    _client.close();
  }
}
