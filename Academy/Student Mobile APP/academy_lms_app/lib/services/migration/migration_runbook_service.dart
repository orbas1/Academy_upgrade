import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

import '../../config/app_configuration.dart';
import '../observability/http_client_factory.dart';
import '../security/auth_session_manager.dart';

class MigrationRunbookStepModel {
  MigrationRunbookStepModel({
    required this.key,
    required this.name,
    required this.type,
    required this.ownerRoles,
    required this.prechecks,
    required this.execution,
    required this.verification,
    required this.rollback,
    required this.dependencies,
    required this.telemetry,
    required this.relatedMigrations,
    required this.relatedCommands,
    this.maintenanceWindowMinutes,
    this.expectedRuntimeMinutes,
    this.notes,
  });

  factory MigrationRunbookStepModel.fromJson(Map<String, dynamic> json) {
    return MigrationRunbookStepModel(
      key: json['key'] as String? ?? '',
      name: json['name'] as String? ?? '',
      type: json['type'] as String? ?? '',
      ownerRoles: List<String>.from(json['owner_roles'] as List? ?? const <String>[]),
      prechecks: List<String>.from(json['prechecks'] as List? ?? const <String>[]),
      execution: List<String>.from(json['execution'] as List? ?? const <String>[]),
      verification: List<String>.from(json['verification'] as List? ?? const <String>[]),
      rollback: List<String>.from(json['rollback'] as List? ?? const <String>[]),
      dependencies: List<String>.from(json['dependencies'] as List? ?? const <String>[]),
      telemetry: List<String>.from(json['telemetry'] as List? ?? const <String>[]),
      relatedMigrations: List<String>.from(json['related_migrations'] as List? ?? const <String>[]),
      relatedCommands: List<String>.from(json['related_commands'] as List? ?? const <String>[]),
      maintenanceWindowMinutes: _parseInt(json['maintenance_window_minutes']),
      expectedRuntimeMinutes: _parseInt(json['expected_runtime_minutes']),
      notes: json['notes'] as String?,
    );
  }

  final String key;
  final String name;
  final String type;
  final List<String> ownerRoles;
  final List<String> prechecks;
  final List<String> execution;
  final List<String> verification;
  final List<String> rollback;
  final List<String> dependencies;
  final List<String> telemetry;
  final List<String> relatedMigrations;
  final List<String> relatedCommands;
  final int? maintenanceWindowMinutes;
  final int? expectedRuntimeMinutes;
  final String? notes;

  Map<String, dynamic> toJson() {
    return <String, dynamic>{
      'key': key,
      'name': name,
      'type': type,
      'owner_roles': ownerRoles,
      'prechecks': prechecks,
      'execution': execution,
      'verification': verification,
      'rollback': rollback,
      'dependencies': dependencies,
      'telemetry': telemetry,
      'related_migrations': relatedMigrations,
      'related_commands': relatedCommands,
      'maintenance_window_minutes': maintenanceWindowMinutes,
      'expected_runtime_minutes': expectedRuntimeMinutes,
      'notes': notes,
    };
  }
}

class MigrationRunbookModel {
  MigrationRunbookModel({
    required this.key,
    required this.name,
    required this.description,
    required this.planKey,
    required this.serviceOwner,
    required this.approvers,
    required this.communicationChannels,
    required this.maintenanceWindowMinutes,
    required this.steps,
  });

  factory MigrationRunbookModel.fromJson(Map<String, dynamic> json) {
    final stepsPayload = (json['steps'] as List<dynamic>? ?? <dynamic>[]) // ignore: implicit_dynamic_parameter
        .map((dynamic step) => MigrationRunbookStepModel.fromJson(Map<String, dynamic>.from(step as Map)))
        .toList(growable: false);

    return MigrationRunbookModel(
      key: json['key'] as String? ?? '',
      name: json['name'] as String? ?? '',
      description: json['description'] as String? ?? '',
      planKey: json['plan_key'] as String? ?? '',
      serviceOwner: List<String>.from(json['service_owner'] as List? ?? const <String>[]),
      approvers: List<String>.from(json['approvers'] as List? ?? const <String>[]),
      communicationChannels: List<String>.from(json['communication_channels'] as List? ?? const <String>[]),
      maintenanceWindowMinutes: _parseInt(json['maintenance_window_minutes']),
      steps: stepsPayload,
    );
  }

  final String key;
  final String name;
  final String description;
  final String planKey;
  final List<String> serviceOwner;
  final List<String> approvers;
  final List<String> communicationChannels;
  final int? maintenanceWindowMinutes;
  final List<MigrationRunbookStepModel> steps;

  Map<String, dynamic> toJson() {
    return <String, dynamic>{
      'key': key,
      'name': name,
      'description': description,
      'plan_key': planKey,
      'service_owner': serviceOwner,
      'approvers': approvers,
      'communication_channels': communicationChannels,
      'maintenance_window_minutes': maintenanceWindowMinutes,
      'steps': steps.map((MigrationRunbookStepModel step) => step.toJson()).toList(growable: false),
    };
  }
}

class MigrationRunbookCache {
  MigrationRunbookCache({
    required this.fetchedAt,
    required this.defaultMaintenanceWindowMinutes,
    required this.runbooks,
  });

  factory MigrationRunbookCache.fromJson(Map<String, dynamic> json) {
    return MigrationRunbookCache(
      fetchedAt: DateTime.parse(
        json['fetched_at'] as String? ?? DateTime.fromMillisecondsSinceEpoch(0, isUtc: true).toIso8601String(),
      ).toUtc(),
      defaultMaintenanceWindowMinutes: _parseInt(json['default_maintenance_window_minutes']),
      runbooks: (json['runbooks'] as List<dynamic>? ?? <dynamic>[]) // ignore: implicit_dynamic_parameter
          .map((dynamic item) => MigrationRunbookModel.fromJson(Map<String, dynamic>.from(item as Map)))
          .toList(growable: false),
    );
  }

  final DateTime fetchedAt;
  final int? defaultMaintenanceWindowMinutes;
  final List<MigrationRunbookModel> runbooks;

  Map<String, dynamic> toJson() {
    return <String, dynamic>{
      'fetched_at': fetchedAt.toIso8601String(),
      'default_maintenance_window_minutes': defaultMaintenanceWindowMinutes,
      'runbooks': runbooks.map((MigrationRunbookModel plan) => plan.toJson()).toList(growable: false),
    };
  }
}

class MigrationRunbookSyncResult {
  MigrationRunbookSyncResult({
    required this.cache,
    required this.wasUpdated,
    this.httpStatusCode,
  });

  final MigrationRunbookCache cache;
  final bool wasUpdated;
  final int? httpStatusCode;
}

class MigrationRunbookService {
  MigrationRunbookService({
    http.Client? client,
    AppConfiguration? configuration,
    AuthSessionManager? sessionManager,
  })  : _client = client != null
            ? HttpClientFactory.create(inner: client)
            : HttpClientFactory.create(),
        _configuration = configuration ?? AppConfiguration.instance,
        _sessionManager = sessionManager ?? AuthSessionManager.instance;

  static const String _cacheKey = 'academy.migration.runbook.cache';

  final http.Client _client;
  final AppConfiguration _configuration;
  final AuthSessionManager _sessionManager;

  Future<MigrationRunbookSyncResult> synchronize({String? bearerToken}) async {
    final Uri endpoint = _configuration.resolveApiPath('/v1/ops/migration-runbooks');
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
      debugPrint('MigrationRunbookService> network error: $error');
      debugPrint('$stackTrace');
      final cache = await loadCache();
      if (cache != null) {
        return MigrationRunbookSyncResult(cache: cache, wasUpdated: false);
      }
      rethrow;
    }

    if (response.statusCode == 401 && bearerToken == null) {
      try {
        final refreshedToken = await _sessionManager.requireAccessToken(forceRefresh: true);
        headers['Authorization'] = 'Bearer $refreshedToken';
        response = await _client.get(endpoint, headers: headers);
      } on Exception catch (error) {
        debugPrint('MigrationRunbookService> token refresh failed: $error');
      }
    }

    if (response.statusCode < 200 || response.statusCode >= 300) {
      debugPrint(
          'MigrationRunbookService> unexpected status ${response.statusCode}: ${response.body}');
      final cache = await loadCache();
      if (cache != null) {
        return MigrationRunbookSyncResult(
          cache: cache,
          wasUpdated: false,
          httpStatusCode: response.statusCode,
        );
      }
      throw http.ClientException(
        'Failed to load migration runbooks (${response.statusCode})',
        endpoint,
      );
    }

    final Map<String, dynamic> payload = jsonDecode(response.body) as Map<String, dynamic>;
    final Map<String, dynamic> data = Map<String, dynamic>.from(
      (payload['data'] ?? payload) as Map,
    );

    final List<dynamic> runbookPayload = data['runbooks'] is List
        ? data['runbooks'] as List<dynamic>
        : data['runbooks'] is Map
            ? List<dynamic>.from((data['runbooks'] as Map).values)
            : <dynamic>[];

    final cache = MigrationRunbookCache(
      fetchedAt: DateTime.now().toUtc(),
      defaultMaintenanceWindowMinutes: _parseInt(data['default_maintenance_window_minutes']),
      runbooks: runbookPayload
          .map((dynamic item) => MigrationRunbookModel.fromJson(Map<String, dynamic>.from(item as Map)))
          .toList(growable: false),
    );

    await _persistCache(cache);

    return MigrationRunbookSyncResult(
      cache: cache,
      wasUpdated: true,
      httpStatusCode: response.statusCode,
    );
  }

  Future<MigrationRunbookCache?> loadCache() async {
    final SharedPreferences prefs = await SharedPreferences.getInstance();
    final String? jsonBlob = prefs.getString(_cacheKey);
    if (jsonBlob == null || jsonBlob.isEmpty) {
      return null;
    }

    try {
      final Map<String, dynamic> payload = jsonDecode(jsonBlob) as Map<String, dynamic>;
      return MigrationRunbookCache.fromJson(payload);
    } on FormatException catch (error) {
      debugPrint('MigrationRunbookService> failed to parse cache: $error');
      await prefs.remove(_cacheKey);
      return null;
    }
  }

  Future<void> _persistCache(MigrationRunbookCache cache) async {
    final SharedPreferences prefs = await SharedPreferences.getInstance();
    await prefs.setString(_cacheKey, jsonEncode(cache.toJson()));
  }
}

int? _parseInt(dynamic value) {
  if (value == null) {
    return null;
  }
  if (value is int) {
    return value;
  }
  if (value is num) {
    return value.toInt();
  }
  if (value is String) {
    return int.tryParse(value);
  }
  return null;
}
