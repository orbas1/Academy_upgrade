import 'dart:convert';

import 'package:academy_lms_app/constants.dart' as constants;
import 'package:http/http.dart' as http;

import '../models/community_feed_item.dart';
import '../models/community_summary.dart';

class CommunityApiService {
  CommunityApiService({http.Client? client}) : _client = client ?? http.Client();

  final http.Client _client;

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

    return Uri.parse('$normalizedBase$path').replace(queryParameters: cleanQuery.isEmpty ? null : cleanQuery);
  }

  Future<List<CommunitySummary>> fetchCommunities({
    String filter = 'all',
    int pageSize = 20,
    String? cursor,
  }) async {
    final response = await _client.get(
      _buildUri(
        '/api/v1/communities',
        {
          'filter': filter,
          'page_size': pageSize.toString(),
          'after': cursor,
        },
      ),
      headers: {'Accept': 'application/json'},
    );

    if (response.statusCode != 200) {
      throw http.ClientException('Unable to load communities', response.request?.url);
    }

    final body = jsonDecode(response.body) as Map<String, dynamic>;
    final data = body['data'] as List<dynamic>? ?? <dynamic>[];

    return data
        .map((dynamic item) => CommunitySummary.fromJson(item as Map<String, dynamic>))
        .toList(growable: false);
  }

  Future<List<CommunityFeedItem>> fetchFeed(
    int communityId, {
    String filter = 'new',
    String? cursor,
    int pageSize = 20,
  }) async {
    final response = await _client.get(
      _buildUri(
        '/api/v1/communities/$communityId/feed',
        {
          'filter': filter,
          'after': cursor,
          'page_size': pageSize.toString(),
        },
      ),
      headers: {'Accept': 'application/json'},
    );

    if (response.statusCode != 200) {
      throw http.ClientException('Unable to load community feed', response.request?.url);
    }

    final body = jsonDecode(response.body) as Map<String, dynamic>;
    final data = body['data'] as List<dynamic>? ?? <dynamic>[];

    return data
        .map((dynamic item) => CommunityFeedItem.fromJson(item as Map<String, dynamic>))
        .toList(growable: false);
  }

  Future<void> dispose() async {
    _client.close();
  }
}
