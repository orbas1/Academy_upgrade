import 'dart:convert';

import 'package:academy_lms_app/constants.dart' as constants;
import 'package:academy_lms_app/services/api_envelope.dart';
import 'package:academy_lms_app/services/observability/http_client_factory.dart';
import 'package:academy_lms_app/services/security/auth_session_manager.dart';
import 'package:academy_lms_app/services/security/client_identity.dart';
import 'package:http/http.dart' as http;

import '../models/community_comment.dart';
import '../models/community_feed_item.dart';
import '../models/community_leaderboard_entry.dart';
import '../models/community_level.dart';
import '../models/community_member.dart';
import '../models/community_notification.dart';
import '../models/community_notification_preferences.dart';
import '../models/community_summary.dart';
import '../models/geo_place.dart';
import '../models/paywall_tier.dart';
import '../models/point_event.dart';
import '../models/points_summary.dart';
import '../models/subscription_checkout.dart';
import '../models/subscription_status.dart';
import 'paginated_response.dart';

class CommunityApiService {
  CommunityApiService({
    http.Client? client,
    String? authToken,
    AuthSessionManager? sessionManager,
    Future<String?> Function({bool forceRefresh})? tokenProvider,
    Map<String, String> Function()? identityHeadersBuilder,
  })
      : _client = client != null
            ? HttpClientFactory.create(inner: client)
            : HttpClientFactory.create(),
        _authToken = authToken,
        _tokenProvider = tokenProvider ??
            ({bool forceRefresh = false}) =>
                (sessionManager ?? AuthSessionManager.instance).getValidAccessToken(forceRefresh: forceRefresh),
        _identityHeadersBuilder = identityHeadersBuilder ?? ClientIdentity.headers;

  final http.Client _client;
  String? _authToken;
  String? _baseUrlOverride;
  final Future<String?> Function({bool forceRefresh}) _tokenProvider;
  final Map<String, String> Function() _identityHeadersBuilder;

  void updateAuthToken(String? token) {
    if (token == null || token.isEmpty) {
      _authToken = null;
    } else {
      _authToken = token;
    }
  }

  void updateBaseUrl(String? baseUrl) {
    if (baseUrl == null || baseUrl.isEmpty) {
      _baseUrlOverride = null;
      return;
    }

    _baseUrlOverride = baseUrl;
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

    final baseSource = _baseUrlOverride ?? constants.baseUrl;
    final normalizedBase = baseSource.endsWith('/')
        ? baseSource.substring(0, baseSource.length - 1)
        : baseSource;

    return Uri.parse('$normalizedBase$path').replace(queryParameters: cleanQuery.isEmpty ? null : cleanQuery);
  }

  Future<PaginatedResponse<CommunitySummary>> fetchCommunities({
    String filter = 'all',
    int pageSize = 20,
    String? cursor,
  }) async {
    final response = await _sendWithAuth(
      (headers) => _client.get(
        _buildUri(
          '/api/v1/communities',
          {
            'filter': filter,
            'page_size': pageSize.toString(),
            'after': cursor,
          },
        ),
        headers: headers,
      ),
    );

    if (response.statusCode != 200) {
      throw http.ClientException('Unable to load communities', response.request?.url);
    }

    final envelope = ApiEnvelope.fromJson(response.body);

    if (!envelope.isSuccess) {
      throw http.ClientException(
        envelope.firstErrorMessage ?? 'Unable to load communities',
        response.request?.url,
      );
    }

    final data = envelope.data is List
        ? List<dynamic>.from(envelope.data as List)
        : const <dynamic>[];

    return PaginatedResponse<CommunitySummary>(
      items: data
          .map((dynamic item) => CommunitySummary.fromJson(item as Map<String, dynamic>))
          .toList(growable: false),
      nextCursor: envelope.nextCursor,
      hasMore: envelope.hasMore,
    );
  }

  Future<PaginatedResponse<CommunityFeedItem>> fetchFeed(
    int communityId, {
    String filter = 'new',
    String? cursor,
    int pageSize = 20,
  }) async {
    final response = await _sendWithAuth(
      (headers) => _client.get(
        _buildUri(
          '/api/v1/communities/$communityId/feed',
          {
            'filter': filter,
            'after': cursor,
            'page_size': pageSize.toString(),
          },
        ),
        headers: headers,
      ),
    );

    if (response.statusCode != 200) {
      throw http.ClientException('Unable to load community feed', response.request?.url);
    }

    final envelope = ApiEnvelope.fromJson(response.body);

    if (!envelope.isSuccess) {
      throw http.ClientException(
        envelope.firstErrorMessage ?? 'Unable to load community feed',
        response.request?.url,
      );
    }

    final data = envelope.data is List
        ? List<dynamic>.from(envelope.data as List)
        : const <dynamic>[];

    return PaginatedResponse<CommunityFeedItem>(
      items: data
          .map((dynamic item) => CommunityFeedItem.fromJson(item as Map<String, dynamic>))
          .toList(growable: false),
      nextCursor: envelope.nextCursor,
      hasMore: envelope.hasMore,
    );
  }

  Future<PaginatedResponse<CommunityComment>> fetchComments(
    int communityId,
    int postId, {
    String? cursor,
    int pageSize = 20,
  }) async {
    final response = await _sendWithAuth(
      (headers) => _client.get(
        _buildUri(
          '/api/v1/communities/$communityId/posts/$postId/comments',
          {
            'after': cursor,
            'page_size': pageSize.toString(),
          },
        ),
        headers: headers,
      ),
    );

    if (response.statusCode != 200) {
      throw http.ClientException('Unable to load comments', response.request?.url);
    }

    final envelope = ApiEnvelope.fromJson(response.body);

    if (!envelope.isSuccess) {
      throw http.ClientException(
        envelope.firstErrorMessage ?? 'Unable to load comments',
        response.request?.url,
      );
    }

    final data = envelope.data is List
        ? List<dynamic>.from(envelope.data as List)
        : const <dynamic>[];

    return PaginatedResponse<CommunityComment>(
      items: data
          .map((dynamic item) => CommunityComment.fromJson(item as Map<String, dynamic>))
          .toList(growable: false),
      nextCursor: envelope.nextCursor,
      hasMore: envelope.hasMore,
    );
  }

  Future<CommunityMember?> fetchMembership(int communityId) async {
    final response = await _sendWithAuth(
      (headers) => _client.get(
        _buildUri('/api/v1/communities/$communityId/membership'),
        headers: headers,
      ),
    );

    if (response.statusCode == 404) {
      return null;
    }

    if (response.statusCode != 200) {
      throw http.ClientException('Unable to load membership', response.request?.url);
    }

    final envelope = ApiEnvelope.fromJson(response.body);

    if (!envelope.isSuccess) {
      throw http.ClientException(
        envelope.firstErrorMessage ?? 'Unable to load membership',
        response.request?.url,
      );
    }

    if (envelope.data == null) {
      return null;
    }

    final map = envelope.data is Map<String, dynamic>
        ? Map<String, dynamic>.from(envelope.data as Map<String, dynamic>)
        : null;

    if (map == null) {
      return null;
    }

    return CommunityMember.fromJson(map);
  }

  Future<CommunityMember> joinCommunity(int communityId) async {
    final response = await _sendWithAuth(
      (headers) => _client.post(
        _buildUri('/api/v1/communities/$communityId/members'),
        headers: headers,
      ),
    );

    if (response.statusCode != 200 && response.statusCode != 201) {
      throw http.ClientException('Unable to join community', response.request?.url);
    }

    final envelope = ApiEnvelope.fromJson(response.body);

    if (!envelope.isSuccess) {
      throw http.ClientException(
        envelope.firstErrorMessage ?? 'Unable to join community',
        response.request?.url,
      );
    }

    final data = envelope.requireMapData();

    return CommunityMember.fromJson(data);
  }

  Future<void> leaveCommunity(int communityId) async {
    final response = await _sendWithAuth(
      (headers) => _client.delete(
        _buildUri('/api/v1/communities/$communityId/membership'),
        headers: headers,
      ),
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
    final response = await _sendWithAuth(
      (headers) => _client.post(
        _buildUri('/api/v1/communities/$communityId/posts'),
        headers: _mergeHeaders(headers, const {'Content-Type': 'application/json'}),
        body: jsonEncode(<String, dynamic>{
          'body_md': bodyMarkdown,
          'visibility': visibility,
          if (paywallTierId != null) 'paywall_tier_id': paywallTierId,
        }),
      ),
    );

    if (response.statusCode != 201) {
      throw http.ClientException('Unable to create post', response.request?.url);
    }

    final envelope = ApiEnvelope.fromJson(response.body);

    if (!envelope.isSuccess) {
      throw http.ClientException(
        envelope.firstErrorMessage ?? 'Unable to create post',
        response.request?.url,
      );
    }

    final data = envelope.requireMapData();

    return CommunityFeedItem.fromJson(data);
  }

  Future<CommunityComment> createComment(
    int communityId,
    int postId, {
    required String bodyMarkdown,
    int? parentId,
  }) async {
    final response = await _sendWithAuth(
      (headers) => _client.post(
        _buildUri('/api/v1/communities/$communityId/posts/$postId/comments'),
        headers: _mergeHeaders(headers, const {'Content-Type': 'application/json'}),
        body: jsonEncode(<String, dynamic>{
          'body_md': bodyMarkdown,
          if (parentId != null) 'parent_id': parentId,
        }),
      ),
    );

    if (response.statusCode != 201) {
      throw http.ClientException('Unable to create comment', response.request?.url);
    }

    final envelope = ApiEnvelope.fromJson(response.body);

    if (!envelope.isSuccess) {
      throw http.ClientException(
        envelope.firstErrorMessage ?? 'Unable to create comment',
        response.request?.url,
      );
    }

    final data = envelope.requireMapData();

    return CommunityComment.fromJson(data);
  }

  Future<void> togglePostReaction(int communityId, int postId, {String reaction = 'like'}) async {
    final response = await _sendWithAuth(
      (headers) => _client.post(
        _buildUri('/api/v1/communities/$communityId/posts/$postId/reactions'),
        headers: _mergeHeaders(headers, const {'Content-Type': 'application/json'}),
        body: jsonEncode({'reaction': reaction}),
      ),
    );

    if (response.statusCode != 200 && response.statusCode != 204) {
      throw http.ClientException('Unable to react to post', response.request?.url);
    }
  }

  Future<void> flagPost(
    int communityId,
    int postId, {
    required String reason,
    List<String> evidenceUrls = const <String>[],
  }) async {
    final response = await _sendWithAuth(
      (headers) => _client.post(
        _buildUri('/api/v1/communities/$communityId/posts/$postId/flags'),
        headers: _mergeHeaders(headers, const {'Content-Type': 'application/json'}),
        body: jsonEncode(<String, dynamic>{
          'reason': reason,
          'source': 'mobile_app',
          if (evidenceUrls.isNotEmpty) 'evidence_urls': evidenceUrls,
        }),
      ),
    );

    if (response.statusCode >= 400) {
      throw http.ClientException('Unable to submit moderation flag', response.request?.url);
    }

    if (response.body.isNotEmpty) {
      final envelope = ApiEnvelope.fromJson(response.body);
      if (!envelope.isSuccess) {
        throw http.ClientException(
          envelope.firstErrorMessage ?? 'Unable to submit moderation flag',
          response.request?.url,
        );
      }
    }
  }

  Future<void> moderatePost(
    int communityId,
    int postId, {
    required String action,
    String? note,
  }) async {
    final response = await _sendWithAuth(
      (headers) => _client.post(
        _buildUri('/api/v1/communities/$communityId/moderation/actions'),
        headers: _mergeHeaders(headers, const {'Content-Type': 'application/json'}),
        body: jsonEncode(<String, dynamic>{
          'post_id': postId,
          'action': action,
          if (note != null && note.isNotEmpty) 'note': note,
        }),
      ),
    );

    if (response.statusCode >= 400) {
      throw http.ClientException('Unable to perform moderation action', response.request?.url);
    }

    if (response.body.isNotEmpty) {
      final envelope = ApiEnvelope.fromJson(response.body);
      if (!envelope.isSuccess) {
        throw http.ClientException(
          envelope.firstErrorMessage ?? 'Unable to perform moderation action',
          response.request?.url,
        );
      }
    }
  }

  Future<List<CommunityLeaderboardEntry>> fetchLeaderboard(int communityId, {String period = 'weekly'}) async {
    final response = await _sendWithAuth(
      (headers) => _client.get(
        _buildUri('/api/v1/communities/$communityId/leaderboard', {'period': period}),
        headers: headers,
      ),
    );

    if (response.statusCode != 200) {
      throw http.ClientException('Unable to load leaderboard', response.request?.url);
    }

    final envelope = ApiEnvelope.fromJson(response.body);

    if (!envelope.isSuccess) {
      throw http.ClientException('Unable to load leaderboard', response.request?.url);
    }

    final data = envelope.data is List
        ? List<dynamic>.from(envelope.data as List)
        : const <dynamic>[];

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
    final response = await _sendWithAuth(
      (headers) => _client.get(
        _buildUri(
          '/api/v1/communities/$communityId/notifications',
          {
            'after': cursor,
            'page_size': pageSize.toString(),
          },
        ),
        headers: headers,
      ),
    );

    if (response.statusCode != 200) {
      throw http.ClientException('Unable to load notifications', response.request?.url);
    }

    final envelope = ApiEnvelope.fromJson(response.body);

    if (!envelope.isSuccess) {
      throw http.ClientException(
        envelope.firstErrorMessage ?? 'Unable to load notifications',
        response.request?.url,
      );
    }

    final data = envelope.data is List
        ? List<dynamic>.from(envelope.data as List)
        : const <dynamic>[];

    return PaginatedResponse<CommunityNotification>(
      items: data
          .map((dynamic item) => CommunityNotification.fromJson(item as Map<String, dynamic>))
          .toList(growable: false),
      nextCursor: envelope.nextCursor,
      hasMore: envelope.hasMore,
    );
  }

  Future<CommunityNotificationPreferences> fetchNotificationPreferences(int communityId) async {
    final response = await _sendWithAuth(
      (headers) => _client.get(
        _buildUri('/api/v1/communities/$communityId/notification-preferences'),
        headers: headers,
      ),
    );

    if (response.statusCode != 200) {
      throw http.ClientException('Unable to load notification preferences', response.request?.url);
    }

    final envelope = ApiEnvelope.fromJson(response.body);

    if (!envelope.isSuccess) {
      throw http.ClientException(
        envelope.firstErrorMessage ?? 'Unable to load notification preferences',
        response.request?.url,
      );
    }

    final data = envelope.requireMapData();

    return CommunityNotificationPreferences.fromJson(data);
  }

  Future<CommunityNotificationPreferences> updateNotificationPreferences(
    int communityId, {
    required CommunityNotificationPreferences preferences,
  }) async {
    final response = await _sendWithAuth(
      (headers) => _client.put(
        _buildUri('/api/v1/communities/$communityId/notification-preferences'),
        headers: _mergeHeaders(headers, const {'Content-Type': 'application/json'}),
        body: jsonEncode(preferences.toJson()),
      ),
    );

    if (response.statusCode != 200) {
      throw http.ClientException('Unable to update notification preferences', response.request?.url);
    }

    final envelope = ApiEnvelope.fromJson(response.body);

    if (!envelope.isSuccess) {
      throw http.ClientException(
        envelope.firstErrorMessage ?? 'Unable to update notification preferences',
        response.request?.url,
      );
    }

    final data = envelope.requireMapData();
    return CommunityNotificationPreferences.fromJson(data);
  }

  Future<void> resetNotificationPreferences(int communityId) async {
    final response = await _sendWithAuth(
      (headers) => _client.delete(
        _buildUri('/api/v1/communities/$communityId/notification-preferences'),
        headers: headers,
      ),
    );

    if (response.statusCode != 204) {
      throw http.ClientException('Unable to reset notification preferences', response.request?.url);
    }
  }

  Future<PointsSummary> fetchPointsSummary(int communityId) async {
    final response = await _sendWithAuth(
      (headers) => _client.get(
        _buildUri('/api/v1/communities/$communityId/points/summary'),
        headers: headers,
      ),
    );

    if (response.statusCode != 200) {
      throw http.ClientException('Unable to load points summary', response.request?.url);
    }

    final envelope = ApiEnvelope.fromJson(response.body);

    if (!envelope.isSuccess) {
      throw http.ClientException(
        envelope.firstErrorMessage ?? 'Unable to load points summary',
        response.request?.url,
      );
    }

    final data = envelope.requireMapData();
    return PointsSummary.fromJson(data);
  }

  Future<PaginatedResponse<PointEvent>> fetchPointHistory(
    int communityId, {
    String? cursor,
    int pageSize = 20,
  }) async {
    final response = await _sendWithAuth(
      (headers) => _client.get(
        _buildUri(
          '/api/v1/communities/$communityId/points/history',
          {
            'after': cursor,
            'page_size': pageSize.toString(),
          },
        ),
        headers: headers,
      ),
    );

    if (response.statusCode != 200) {
      throw http.ClientException('Unable to load point history', response.request?.url);
    }

    final envelope = ApiEnvelope.fromJson(response.body);

    if (!envelope.isSuccess) {
      throw http.ClientException(
        envelope.firstErrorMessage ?? 'Unable to load point history',
        response.request?.url,
      );
    }

    final data = envelope.data is List
        ? List<dynamic>.from(envelope.data as List)
        : const <dynamic>[];

    return PaginatedResponse<PointEvent>(
      items: data
          .map((dynamic item) => PointEvent.fromJson(item as Map<String, dynamic>))
          .toList(growable: false),
      nextCursor: envelope.nextCursor,
      hasMore: envelope.hasMore,
    );
  }

  Future<List<CommunityLevel>> fetchLevels(int communityId) async {
    final response = await _sendWithAuth(
      (headers) => _client.get(
        _buildUri('/api/v1/communities/$communityId/levels'),
        headers: headers,
      ),
    );

    if (response.statusCode != 200) {
      throw http.ClientException('Unable to load levels', response.request?.url);
    }

    final envelope = ApiEnvelope.fromJson(response.body);

    if (!envelope.isSuccess) {
      throw http.ClientException(
        envelope.firstErrorMessage ?? 'Unable to load levels',
        response.request?.url,
      );
    }

    final data = envelope.data is List
        ? List<dynamic>.from(envelope.data as List)
        : const <dynamic>[];

    return data
        .map((dynamic item) => CommunityLevel.fromJson(item as Map<String, dynamic>))
        .toList(growable: false);
  }

  Future<List<PaywallTier>> fetchPaywallTiers(int communityId) async {
    final response = await _sendWithAuth(
      (headers) => _client.get(
        _buildUri('/api/v1/communities/$communityId/paywall/tiers'),
        headers: headers,
      ),
    );

    if (response.statusCode != 200) {
      throw http.ClientException('Unable to load paywall tiers', response.request?.url);
    }

    final envelope = ApiEnvelope.fromJson(response.body);

    if (!envelope.isSuccess) {
      throw http.ClientException(
        envelope.firstErrorMessage ?? 'Unable to load paywall tiers',
        response.request?.url,
      );
    }

    final data = envelope.data is List
        ? List<dynamic>.from(envelope.data as List)
        : const <dynamic>[];

    return data
        .map((dynamic item) => PaywallTier.fromJson(item as Map<String, dynamic>))
        .toList(growable: false);
  }

  Future<SubscriptionCheckout> createSubscriptionCheckout(
    int communityId, {
    required int tierId,
    int quantity = 1,
    String? couponCode,
    required Uri returnUrl,
    Uri? cancelUrl,
  }) async {
    final response = await _sendWithAuth(
      (headers) => _client.post(
        _buildUri('/api/v1/communities/$communityId/subscriptions/checkout'),
        headers: _mergeHeaders(headers, const {'Content-Type': 'application/json'}),
        body: jsonEncode(<String, dynamic>{
          'tier_id': tierId,
          'quantity': quantity,
          if (couponCode != null && couponCode.isNotEmpty) 'coupon_code': couponCode,
          'return_url': returnUrl.toString(),
          if (cancelUrl != null) 'cancel_url': cancelUrl.toString(),
        }),
      ),
    );

    if (response.statusCode != 201) {
      throw http.ClientException('Unable to create checkout session', response.request?.url);
    }

    final envelope = ApiEnvelope.fromJson(response.body);

    if (!envelope.isSuccess) {
      throw http.ClientException(
        envelope.firstErrorMessage ?? 'Unable to create checkout session',
        response.request?.url,
      );
    }

    final data = envelope.requireMapData();
    return SubscriptionCheckout.fromJson(data);
  }

  Future<SubscriptionStatus> fetchSubscriptionStatus(int communityId) async {
    final response = await _sendWithAuth(
      (headers) => _client.get(
        _buildUri('/api/v1/communities/$communityId/subscriptions'),
        headers: headers,
      ),
    );

    if (response.statusCode != 200) {
      throw http.ClientException('Unable to load subscription status', response.request?.url);
    }

    final envelope = ApiEnvelope.fromJson(response.body);

    if (!envelope.isSuccess) {
      throw http.ClientException(
        envelope.firstErrorMessage ?? 'Unable to load subscription status',
        response.request?.url,
      );
    }

    final data = envelope.requireMapData();
    return SubscriptionStatus.fromJson(data);
  }

  Future<List<GeoPlace>> fetchGeoPlaces(int communityId) async {
    final response = await _sendWithAuth(
      (headers) => _client.get(
        _buildUri('/api/v1/communities/$communityId/geo/places'),
        headers: headers,
      ),
    );

    if (response.statusCode != 200) {
      throw http.ClientException('Unable to load geo places', response.request?.url);
    }

    final envelope = ApiEnvelope.fromJson(response.body);

    if (!envelope.isSuccess) {
      throw http.ClientException(
        envelope.firstErrorMessage ?? 'Unable to load geo places',
        response.request?.url,
      );
    }

    final data = envelope.data is List
        ? List<dynamic>.from(envelope.data as List)
        : const <dynamic>[];

    return data
        .map((dynamic item) => GeoPlace.fromJson(item as Map<String, dynamic>))
        .toList(growable: false);
  }

  Future<void> dispose() async {
    _client.close();
  }

  Future<Map<String, String>> _headers({Map<String, String>? extra, bool forceRefresh = false}) async {
    final headers = <String, String>{'Accept': 'application/json'};

    headers.addAll(_identityHeadersBuilder());

    String? bearer;
    try {
      bearer = await _tokenProvider(forceRefresh: forceRefresh);
    } catch (_) {
      bearer = null;
    }

    final resolved = (bearer != null && bearer.isNotEmpty) ? bearer : _authToken;
    if (resolved != null && resolved.isNotEmpty) {
      headers['Authorization'] = 'Bearer $resolved';
    }

    if (extra != null && extra.isNotEmpty) {
      headers.addAll(extra);
    }

    return headers;
  }

  Map<String, String> _mergeHeaders(Map<String, String> headers, Map<String, String>? extra) {
    if (extra == null || extra.isEmpty) {
      return headers;
    }

    return <String, String>{...headers, ...extra};
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
