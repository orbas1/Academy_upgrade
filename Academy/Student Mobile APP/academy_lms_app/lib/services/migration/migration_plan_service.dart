import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

import '../../config/app_configuration.dart';
import '../observability/http_client_factory.dart';
import '../security/auth_session_manager.dart';

class MigrationStepModel {
  MigrationStepModel({
    required this.key,
    required this.name,
    required this.summary,
    required this.operations,
    required this.backfill,
    required this.verification,
    required this.rollback,
    required this.owners,
    required this.dependencies,
    this.stabilityWindowDays,
  });

  factory MigrationStepModel.fromJson(Map<String, dynamic> json) {
    return MigrationStepModel(
      key: json['key'] as String? ?? '',
      name: json['name'] as String? ?? '',
      summary: json['summary'] as String? ?? '',
      operations: List<String>.from(json['operations'] as List? ?? const <String>[]),
      backfill: List<String>.from(json['backfill'] as List? ?? const <String>[]),
      verification: List<String>.from(json['verification'] as List? ?? const <String>[]),
      rollback: List<String>.from(json['rollback'] as List? ?? const <String>[]),
      owners: List<String>.from(json['owners'] as List? ?? const <String>[]),
      dependencies: List<String>.from(json['dependencies'] as List? ?? const <String>[]),
      stabilityWindowDays: json['stability_window_days'] is int
          ? json['stability_window_days'] as int
          : json['stability_window_days'] is num
              ? (json['stability_window_days'] as num).toInt()
              : null,
    );
  }

  final String key;
  final String name;
  final String summary;
  final List<String> operations;
  final List<String> backfill;
  final List<String> verification;
  final List<String> rollback;
  final List<String> owners;
  final List<String> dependencies;
  final int? stabilityWindowDays;

  Map<String, dynamic> toJson() {
    return <String, dynamic>{
      'key': key,
      'name': name,
      'summary': summary,
      'operations': operations,
      'backfill': backfill,
      'verification': verification,
      'rollback': rollback,
      'owners': owners,
      'dependencies': dependencies,
      'stability_window_days': stabilityWindowDays,
    };
  }
}

class MigrationPhaseModel {
  MigrationPhaseModel({
    required this.key,
    required this.name,
    required this.type,
    required this.steps,
    this.stabilityWindowDays,
  });

  factory MigrationPhaseModel.fromJson(Map<String, dynamic> json) {
    return MigrationPhaseModel(
      key: json['key'] as String? ?? '',
      name: json['name'] as String? ?? '',
      type: json['type'] as String? ?? '',
      steps: (json['steps'] as List<dynamic>? ?? <dynamic>[]) // ignore: implicit_dynamic_parameter
          .map((dynamic step) => MigrationStepModel.fromJson(Map<String, dynamic>.from(step as Map)))
          .toList(growable: false),
      stabilityWindowDays: json['stability_window_days'] is int
          ? json['stability_window_days'] as int
          : json['stability_window_days'] is num
              ? (json['stability_window_days'] as num).toInt()
              : null,
    );
  }

  final String key;
  final String name;
  final String type;
  final List<MigrationStepModel> steps;
  final int? stabilityWindowDays;

  Map<String, dynamic> toJson() {
    return <String, dynamic>{
      'key': key,
      'name': name,
      'type': type,
      'steps': steps.map((MigrationStepModel step) => step.toJson()).toList(growable: false),
      'stability_window_days': stabilityWindowDays,
    };
  }
}

class MigrationPlanModel {
  MigrationPlanModel({
    required this.key,
    required this.name,
    required this.description,
    required this.phases,
    required this.serviceOwner,
    required this.dependencies,
    required this.featureFlags,
    required this.minimumVersions,
    this.defaultStabilityWindowDays,
  });

  factory MigrationPlanModel.fromJson(Map<String, dynamic> json) {
    final phases = (json['phases'] as List<dynamic>? ?? <dynamic>[]) // ignore: implicit_dynamic_parameter
        .map((dynamic phase) => MigrationPhaseModel.fromJson(Map<String, dynamic>.from(phase as Map)))
        .toList(growable: false);

    return MigrationPlanModel(
      key: json['key'] as String? ?? '',
      name: json['name'] as String? ?? '',
      description: json['description'] as String? ?? '',
      phases: phases,
      serviceOwner: List<String>.from(json['service_owner'] as List? ?? const <String>[]),
      dependencies: List<String>.from(json['dependencies'] as List? ?? const <String>[]),
      featureFlags: Map<String, List<String>>.fromEntries(
        (json['feature_flags'] as Map<String, dynamic>? ?? const <String, dynamic>{})
            .entries
            .map(
              (MapEntry<String, dynamic> entry) => MapEntry<String, List<String>>(
                entry.key,
                List<String>.from(entry.value as List? ?? const <String>[]),
              ),
            ),
      ),
      minimumVersions: Map<String, String?>.from(
        (json['minimum_versions'] as Map<String, dynamic>? ?? const <String, dynamic>{})
            .map(
          (String key, dynamic value) => MapEntry<String, String?>(key, value as String?),
        ),
      ),
      defaultStabilityWindowDays: json['default_stability_window_days'] is int
          ? json['default_stability_window_days'] as int
          : json['default_stability_window_days'] is num
              ? (json['default_stability_window_days'] as num).toInt()
              : null,
    );
  }

  final String key;
  final String name;
  final String description;
  final List<MigrationPhaseModel> phases;
  final List<String> serviceOwner;
  final List<String> dependencies;
  final Map<String, List<String>> featureFlags;
  final Map<String, String?> minimumVersions;
  final int? defaultStabilityWindowDays;

  Map<String, dynamic> toJson() {
    return <String, dynamic>{
      'key': key,
      'name': name,
      'description': description,
      'phases': phases.map((MigrationPhaseModel phase) => phase.toJson()).toList(growable: false),
      'service_owner': serviceOwner,
      'dependencies': dependencies,
      'feature_flags': featureFlags,
      'minimum_versions': minimumVersions,
      'default_stability_window_days': defaultStabilityWindowDays,
    };
  }
}

class MigrationPlanCache {
  MigrationPlanCache({
    required this.fetchedAt,
    required this.plans,
    required this.defaultStabilityWindowDays,
  });

  factory MigrationPlanCache.fromJson(Map<String, dynamic> json) {
    return MigrationPlanCache(
      fetchedAt: DateTime.parse(json['fetched_at'] as String? ?? DateTime.fromMillisecondsSinceEpoch(0, isUtc: true).toIso8601String())
          .toUtc(),
      defaultStabilityWindowDays: json['default_stability_window_days'] is int
          ? json['default_stability_window_days'] as int
          : json['default_stability_window_days'] is num
              ? (json['default_stability_window_days'] as num).toInt()
              : null,
      plans: (json['plans'] as List<dynamic>? ?? <dynamic>[]) // ignore: implicit_dynamic_parameter
          .map((dynamic item) => MigrationPlanModel.fromJson(Map<String, dynamic>.from(item as Map)))
          .toList(growable: false),
    );
  }

  final DateTime fetchedAt;
  final List<MigrationPlanModel> plans;
  final int? defaultStabilityWindowDays;

  Map<String, dynamic> toJson() {
    return <String, dynamic>{
      'fetched_at': fetchedAt.toIso8601String(),
      'plans': plans.map((MigrationPlanModel plan) => plan.toJson()).toList(growable: false),
      'default_stability_window_days': defaultStabilityWindowDays,
    };
  }
}

class MigrationPlanSyncResult {
  MigrationPlanSyncResult({
    required this.cache,
    required this.wasUpdated,
    this.httpStatusCode,
  });

  final MigrationPlanCache cache;
  final bool wasUpdated;
  final int? httpStatusCode;
}

class MigrationPlanService {
  MigrationPlanService({
    http.Client? client,
    AppConfiguration? configuration,
    AuthSessionManager? sessionManager,
  })  : _client = client != null
            ? HttpClientFactory.create(inner: client)
            : HttpClientFactory.create(),
        _configuration = configuration ?? AppConfiguration.instance,
        _sessionManager = sessionManager ?? AuthSessionManager.instance;

  static const String _cacheKey = 'academy.migration.plan.cache';

  final http.Client _client;
  final AppConfiguration _configuration;
  final AuthSessionManager _sessionManager;

  Future<MigrationPlanSyncResult> synchronize({String? bearerToken}) async {
    final Uri endpoint = _configuration.resolveApiPath('/v1/ops/migration-plan');
    final Map<String, String> headers = <String, String>{
      'Accept': 'application/json',
    };

    final String? token = bearerToken ?? await _sessionManager.getValidAccessToken();
    if (token != null && token.isNotEmpty) {
      headers['Authorization'] = 'Bearer $token';
    }

    http.Response response;
    try {
      response = await _client.get(endpoint, headers: headers);
    } on Exception catch (error, stackTrace) {
      debugPrint('MigrationPlanService> network error: $error');
      debugPrint('$stackTrace');
      final cache = await loadCache();
      if (cache != null) {
        return MigrationPlanSyncResult(cache: cache, wasUpdated: false);
      }
      rethrow;
    }

    if (response.statusCode == 401) {
      // Attempt refresh once if unauthorized and we did not explicitly supply a token.
      if (bearerToken == null) {
        try {
          final refreshedToken = await _sessionManager.requireAccessToken(forceRefresh: true);
          headers['Authorization'] = 'Bearer $refreshedToken';
          response = await _client.get(endpoint, headers: headers);
        } on Exception catch (error) {
          debugPrint('MigrationPlanService> token refresh failed: $error');
        }
      }
    }

    if (response.statusCode < 200 || response.statusCode >= 300) {
      debugPrint(
          'MigrationPlanService> unexpected status ${response.statusCode}: ${response.body}');
      final cache = await loadCache();
      if (cache != null) {
        return MigrationPlanSyncResult(
          cache: cache,
          wasUpdated: false,
          httpStatusCode: response.statusCode,
        );
      }
      throw http.ClientException(
        'Failed to load migration plan (${response.statusCode})',
        endpoint,
      );
    }

    final Map<String, dynamic> payload = jsonDecode(response.body) as Map<String, dynamic>;
    final Map<String, dynamic> data = Map<String, dynamic>.from(
      (payload['data'] ?? payload) as Map,
    );

    final List<dynamic> planPayload = data['plans'] is List
        ? data['plans'] as List<dynamic>
        : data['plans'] is Map
            ? List<dynamic>.from((data['plans'] as Map).values)
            : data['plans'] == null
                ? (data['phases'] == null ? <dynamic>[] : <dynamic>[data])
                : <dynamic>[];

    final cache = MigrationPlanCache(
      fetchedAt: DateTime.now().toUtc(),
      defaultStabilityWindowDays: data['default_stability_window_days'] is int
          ? data['default_stability_window_days'] as int
          : data['default_stability_window_days'] is num
              ? (data['default_stability_window_days'] as num).toInt()
              : null,
      plans: planPayload
          .map((dynamic item) => MigrationPlanModel.fromJson(Map<String, dynamic>.from(item as Map)))
          .toList(growable: false),
    );

    await _persistCache(cache);

    return MigrationPlanSyncResult(
      cache: cache,
      wasUpdated: true,
      httpStatusCode: response.statusCode,
    );
  }

  Future<MigrationPlanCache?> loadCache() async {
    final SharedPreferences prefs = await SharedPreferences.getInstance();
    final String? jsonBlob = prefs.getString(_cacheKey);
    if (jsonBlob == null || jsonBlob.isEmpty) {
      return null;
    }

    try {
      final Map<String, dynamic> payload = jsonDecode(jsonBlob) as Map<String, dynamic>;
      return MigrationPlanCache.fromJson(payload);
    } on FormatException catch (error) {
      debugPrint('MigrationPlanService> failed to parse cache: $error');
      await prefs.remove(_cacheKey);
      return null;
    }
  }

  Future<void> _persistCache(MigrationPlanCache cache) async {
    final SharedPreferences prefs = await SharedPreferences.getInstance();
    await prefs.setString(_cacheKey, jsonEncode(cache.toJson()));
  }
}
