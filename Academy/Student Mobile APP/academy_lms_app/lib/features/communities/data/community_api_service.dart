import 'dart:convert';

import 'package:academy_lms_app/constants.dart' as constants;
import 'package:http/http.dart' as http;

import '../models/community_feed_item.dart';
import '../models/community_leaderboard_entry.dart';
import '../models/community_member.dart';
import '../models/community_notification.dart';
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

  Future<CommunityMember?> fetchMembership(int communityId) async {
    final response = await _client.get(
      _buildUri('/api/v1/communities/$communityId/membership'),
      headers: {'Accept': 'application/json'},
    );

    if (response.statusCode == 404) {
      return null;
    }

    if (response.statusCode != 200) {
      throw http.ClientException('Unable to load membership', response.request?.url);
    }

    final body = jsonDecode(response.body) as Map<String, dynamic>;
    if (body['data'] == null) {
      return null;
    }

    return CommunityMember.fromJson(body['data'] as Map<String, dynamic>);
  }

  Future<CommunityMember> joinCommunity(int communityId) async {
    final response = await _client.post(
      _buildUri('/api/v1/communities/$communityId/members'),
      headers: {'Accept': 'application/json'},
    );

    if (response.statusCode != 200 && response.statusCode != 201) {
      throw http.ClientException('Unable to join community', response.request?.url);
    }

    final body = jsonDecode(response.body) as Map<String, dynamic>;

    return CommunityMember.fromJson(body['data'] as Map<String, dynamic>);
  }

  Future<void> leaveCommunity(int communityId) async {
    final response = await _client.delete(
      _buildUri('/api/v1/communities/$communityId/membership'),
      headers: {'Accept': 'application/json'},
    );

    if (response.statusCode != 204) {
      throw http.ClientException('Unable to leave community', response.request?.url);
    }
  }

  Future<CommunityFeedItem> createPost(
    int communityId, {
    required String bodyMarkdown,
    String visibility = 'community',
    int? paywallTierId,
  }) async {
    final response = await _client.post(
      _buildUri('/api/v1/communities/$communityId/posts'),
      headers: {'Accept': 'application/json', 'Content-Type': 'application/json'},
      body: jsonEncode(<String, dynamic>{
        'body_md': bodyMarkdown,
        'visibility': visibility,
        if (paywallTierId != null) 'paywall_tier_id': paywallTierId,
      }),
    );

    if (response.statusCode != 201) {
      throw http.ClientException('Unable to create post', response.request?.url);
    }

    final body = jsonDecode(response.body) as Map<String, dynamic>;
    return CommunityFeedItem.fromJson(body['data'] as Map<String, dynamic>);
  }

  Future<void> togglePostReaction(int communityId, int postId, {String reaction = 'like'}) async {
    final response = await _client.post(
      _buildUri('/api/v1/communities/$communityId/posts/$postId/reactions'),
      headers: {'Accept': 'application/json', 'Content-Type': 'application/json'},
      body: jsonEncode({'reaction': reaction}),
    );

    if (response.statusCode != 200 && response.statusCode != 204) {
      throw http.ClientException('Unable to react to post', response.request?.url);
    }
  }

  Future<List<CommunityLeaderboardEntry>> fetchLeaderboard(int communityId, {String period = 'weekly'}) async {
    final response = await _client.get(
      _buildUri('/api/v1/communities/$communityId/leaderboard', {'period': period}),
      headers: {'Accept': 'application/json'},
    );

    if (response.statusCode != 200) {
      throw http.ClientException('Unable to load leaderboard', response.request?.url);
    }

    final body = jsonDecode(response.body) as Map<String, dynamic>;
    final data = body['data'] as List<dynamic>? ?? <dynamic>[];

    return data
        .asMap()
        .entries
        .map((entry) => CommunityLeaderboardEntry.fromJson(entry.value as Map<String, dynamic>, entry.key))
        .toList(growable: false);
  }

  Future<List<CommunityNotification>> fetchNotifications(
    int communityId, {
    String? cursor,
    int pageSize = 20,
  }) async {
    final response = await _client.get(
      _buildUri(
        '/api/v1/communities/$communityId/notifications',
        {
          'after': cursor,
          'page_size': pageSize.toString(),
        },
      ),
      headers: {'Accept': 'application/json'},
    );

    if (response.statusCode != 200) {
      throw http.ClientException('Unable to load notifications', response.request?.url);
    }

    final body = jsonDecode(response.body) as Map<String, dynamic>;
    final data = body['data'] as List<dynamic>? ?? <dynamic>[];

    return data
        .map((dynamic item) => CommunityNotification.fromJson(item as Map<String, dynamic>))
        .toList(growable: false);
  }

  Future<void> dispose() async {
    _client.close();
  }
}
