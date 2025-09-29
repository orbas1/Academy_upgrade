import 'package:flutter/foundation.dart';

import '../models/community/community_category.dart';
import '../models/community/community_defaults.dart';
import '../models/community/community_level.dart';
import '../models/community/community_points_rule.dart';

class CommunityDefaultsProvider with ChangeNotifier {
  List<CommunityCategoryModel> _categories = [];
  List<CommunityLevelModel> _levels = [];
  List<CommunityPointsRuleModel> _pointsRules = [];

  bool _hydrated = false;

  List<CommunityCategoryModel> get categories => List.unmodifiable(_categories);
  List<CommunityLevelModel> get levels => List.unmodifiable(_levels);
  List<CommunityPointsRuleModel> get pointsRules => List.unmodifiable(_pointsRules);
  bool get isHydrated => _hydrated;

  void hydrateFromSeed() {
    _categories = kDefaultCommunityCategories
        .map((category) => CommunityCategoryModel.fromJson(category))
        .toList();
    _levels = kDefaultCommunityLevels
        .map((level) => CommunityLevelModel.fromJson(level))
        .toList();
    _pointsRules = kDefaultCommunityPointsRules
        .map((rule) => CommunityPointsRuleModel.fromJson(rule))
        .toList();
    _hydrated = true;
    notifyListeners();
  }

  void applyRemotePayload(Map<String, dynamic> payload) {
    if (payload['categories'] is List) {
      _categories = (payload['categories'] as List<dynamic>)
          .whereType<Map<String, dynamic>>()
          .map(CommunityCategoryModel.fromJson)
          .toList();
    }

    if (payload['levels'] is List) {
      _levels = (payload['levels'] as List<dynamic>)
          .whereType<Map<String, dynamic>>()
          .map(CommunityLevelModel.fromJson)
          .toList();
    }

    if (payload['points_rules'] is List) {
      _pointsRules = (payload['points_rules'] as List<dynamic>)
          .whereType<Map<String, dynamic>>()
          .map(CommunityPointsRuleModel.fromJson)
          .toList();
    }

    _hydrated = true;
    notifyListeners();
  }
}
