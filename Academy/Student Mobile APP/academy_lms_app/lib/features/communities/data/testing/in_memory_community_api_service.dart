import 'dart:async';

import 'package:http/http.dart' as http;

import '../community_api_service.dart';
import '../models/profile_activity.dart';
import 'paginated_response.dart';
import 'errors.dart';

class InMemoryCommunityApiService extends CommunityApiService {
  InMemoryCommunityApiService({
    Map<int?, List<ProfileActivity>>? activitiesByCommunity,
    bool featureAvailable = true,
  })  : _activitiesByCommunity = {
          for (final entry
              in (activitiesByCommunity ?? <int?, List<ProfileActivity>>{}).entries)
            entry.key: List<ProfileActivity>.unmodifiable(entry.value),
        },
        _featureAvailable = featureAvailable,
        super(client: _FailingHttpClient());

  Map<int?, List<ProfileActivity>> _activitiesByCommunity;
  bool _featureAvailable;

  void replaceActivities(List<ProfileActivity> activities, {int? communityId}) {
    _activitiesByCommunity = {
      ..._activitiesByCommunity,
      communityId: List<ProfileActivity>.unmodifiable(activities),
    };
  }

  void setFeatureAvailable(bool available) {
    _featureAvailable = available;
  }

  @override
  Future<PaginatedResponse<ProfileActivity>> fetchProfileActivity({
    int? communityId,
    String? cursor,
    int pageSize = 50,
  }) async {
    if (!_featureAvailable) {
      throw FeatureUnavailableException(
        'Profile activity disabled for in-memory API service.',
      );
    }

    final bucket = _resolveActivitiesFor(communityId);
    final start = cursor == null ? 0 : int.tryParse(cursor) ?? 0;
    if (start < 0 || start > bucket.length) {
      return PaginatedResponse<ProfileActivity>.empty();
    }

    final items = bucket.skip(start).take(pageSize).toList(growable: false);
    final nextIndex = start + items.length;
    final hasMore = nextIndex < bucket.length;

    return PaginatedResponse<ProfileActivity>(
      items: items,
      nextCursor: hasMore ? '$nextIndex' : null,
      hasMore: hasMore,
    );
  }

  List<ProfileActivity> _resolveActivitiesFor(int? communityId) {
    if (_activitiesByCommunity.containsKey(communityId)) {
      return _activitiesByCommunity[communityId]!;
    }

    return _activitiesByCommunity[null] ?? const <ProfileActivity>[];
  }
}

class _FailingHttpClient extends http.BaseClient {
  @override
  Future<http.StreamedResponse> send(http.BaseRequest request) {
    throw UnsupportedError(
      'Network access is disabled when using InMemoryCommunityApiService.',
    );
  }
}
