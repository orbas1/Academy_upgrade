import 'dart:convert';

import 'package:academy_lms_app/constants.dart' as constants;
import 'package:http/http.dart' as http;

import '../models/community_comment.dart';
import '../models/community_feed_item.dart';
import '../models/community_leaderboard_entry.dart';
import '../models/community_member.dart';
import '../models/community_notification.dart';
import '../models/community_notification_preferences.dart';
import '../models/community_summary.dart';
import 'paginated_response.dart';

class CommunityApiService {
  CommunityApiService({http.Client? client, String? authToken})
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

  Future<PaginatedResponse<CommunitySummary>> fetchCommunities({
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
      headers: _headers(),
    );

    if (response.statusCode != 200) {
      throw http.ClientException('Unable to load communities', response.request?.url);
    }

    final body = jsonDecode(response.body) as Map<String, dynamic>;
    final data = body['data'] as List<dynamic>? ?? <dynamic>[];
    final meta = body['meta'] as Map<String, dynamic>?;

    return PaginatedResponse<CommunitySummary>(
      items: data
          .map((dynamic item) => CommunitySummary.fromJson(item as Map<String, dynamic>))
          .toList(growable: false),
      nextCursor: meta?['next_cursor'] as String?,
    );
  }

  Future<PaginatedResponse<CommunityFeedItem>> fetchFeed(
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
      headers: _headers(),
    );

    if (response.statusCode != 200) {
      throw http.ClientException('Unable to load community feed', response.request?.url);
    }

    final body = jsonDecode(response.body) as Map<String, dynamic>;
    final data = body['data'] as List<dynamic>? ?? <dynamic>[];
    final meta = body['meta'] as Map<String, dynamic>?;

    return PaginatedResponse<CommunityFeedItem>(
      items: data
          .map((dynamic item) => CommunityFeedItem.fromJson(item as Map<String, dynamic>))
          .toList(growable: false),
      nextCursor: meta?['next_cursor'] as String?,
    );
  }

  Future<PaginatedResponse<CommunityComment>> fetchComments(
    int communityId,
    int postId, {
    String? cursor,
    int pageSize = 20,
  }) async {
    final response = await _client.get(
      _buildUri(
        '/api/v1/communities/$communityId/posts/$postId/comments',
        {
          'after': cursor,
          'page_size': pageSize.toString(),
        },
      ),
      headers: _headers(),
    );

    if (response.statusCode != 200) {
      throw http.ClientException('Unable to load comments', response.request?.url);
    }

    final body = jsonDecode(response.body) as Map<String, dynamic>;
    final data = body['data'] as List<dynamic>? ?? <dynamic>[];
    final meta = body['meta'] as Map<String, dynamic>?;

    return PaginatedResponse<CommunityComment>(
      items: data
          .map((dynamic item) => CommunityComment.fromJson(item as Map<String, dynamic>))
          .toList(growable: false),
      nextCursor: meta?['next_cursor'] as String?,
    );
  }

  Future<CommunityMember?> fetchMembership(int communityId) async {
    final response = await _client.get(
      _buildUri('/api/v1/communities/$communityId/membership'),
      headers: _headers(),
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
      headers: _headers(),
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
      headers: _headers(),
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
      headers: _headers(extra: const {'Content-Type': 'application/json'}),
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

  Future<CommunityComment> createComment(
    int communityId,
    int postId, {
    required String bodyMarkdown,
    int? parentId,
  }) async {
    final response = await _client.post(
      _buildUri('/api/v1/communities/$communityId/posts/$postId/comments'),
      headers: _headers(extra: const {'Content-Type': 'application/json'}),
      body: jsonEncode(<String, dynamic>{
        'body_md': bodyMarkdown,
        if (parentId != null) 'parent_id': parentId,
      }),
    );

    if (response.statusCode != 201) {
      throw http.ClientException('Unable to create comment', response.request?.url);
    }

    final body = jsonDecode(response.body) as Map<String, dynamic>;
    return CommunityComment.fromJson(body['data'] as Map<String, dynamic>);
  }

  Future<void> togglePostReaction(int communityId, int postId, {String reaction = 'like'}) async {
    final response = await _client.post(
      _buildUri('/api/v1/communities/$communityId/posts/$postId/reactions'),
      headers: _headers(extra: const {'Content-Type': 'application/json'}),
      body: jsonEncode({'reaction': reaction}),
    );

    if (response.statusCode != 200 && response.statusCode != 204) {
      throw http.ClientException('Unable to react to post', response.request?.url);
    }
  }

  Future<List<CommunityLeaderboardEntry>> fetchLeaderboard(int communityId, {String period = 'weekly'}) async {
    final response = await _client.get(
      _buildUri('/api/v1/communities/$communityId/leaderboard', {'period': period}),
      headers: _headers(),
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

  Future<PaginatedResponse<CommunityNotification>> fetchNotifications(
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
      headers: _headers(),
    );

    if (response.statusCode != 200) {
      throw http.ClientException('Unable to load notifications', response.request?.url);
    }

    final body = jsonDecode(response.body) as Map<String, dynamic>;
    final data = body['data'] as List<dynamic>? ?? <dynamic>[];
    final meta = body['meta'] as Map<String, dynamic>?;

    return PaginatedResponse<CommunityNotification>(
      items: data
          .map((dynamic item) => CommunityNotification.fromJson(item as Map<String, dynamic>))
          .toList(growable: false),
      nextCursor: meta?['next_cursor'] as String?,
    );
  }

  Future<CommunityNotificationPreferences> fetchNotificationPreferences(int communityId) async {
    final response = await _client.get(
      _buildUri('/api/v1/communities/$communityId/notification-preferences'),
      headers: _headers(),
    );

    if (response.statusCode != 200) {
      throw http.ClientException('Unable to load notification preferences', response.request?.url);
    }

    final body = jsonDecode(response.body) as Map<String, dynamic>;
    return CommunityNotificationPreferences.fromJson(body['data'] as Map<String, dynamic>);
  }

  Future<CommunityNotificationPreferences> updateNotificationPreferences(
    int communityId, {
    required CommunityNotificationPreferences preferences,
  }) async {
    final response = await _client.put(
      _buildUri('/api/v1/communities/$communityId/notification-preferences'),
      headers: _headers(extra: const {'Content-Type': 'application/json'}),
      body: jsonEncode(preferences.toJson()),
    );

    if (response.statusCode != 200) {
      throw http.ClientException('Unable to update notification preferences', response.request?.url);
    }

    final body = jsonDecode(response.body) as Map<String, dynamic>;
    return CommunityNotificationPreferences.fromJson(body['data'] as Map<String, dynamic>);
  }

  Future<void> resetNotificationPreferences(int communityId) async {
    final response = await _client.delete(
      _buildUri('/api/v1/communities/$communityId/notification-preferences'),
      headers: _headers(),
    );

    if (response.statusCode != 204) {
      throw http.ClientException('Unable to reset notification preferences', response.request?.url);
    }
  }

  Future<void> dispose() async {
    _client.close();
  }

  Map<String, String> _headers({Map<String, String>? extra}) {
    final headers = <String, String>{'Accept': 'application/json'};

    if (_authToken != null) {
      headers['Authorization'] = 'Bearer $_authToken';
    }

    if (extra != null) {
      headers.addAll(extra);
    }

    return headers;
  }
}
