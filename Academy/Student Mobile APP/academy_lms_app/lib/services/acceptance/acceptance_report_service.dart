import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'package:shared_preferences/shared_preferences.dart';

import '../../config/app_configuration.dart';
import '../observability/http_client_factory.dart';
import '../security/auth_session_manager.dart';

class AcceptanceCheckModel {
  const AcceptanceCheckModel({
    required this.type,
    required this.identifier,
    required this.weight,
    required this.passed,
    required this.metadata,
    this.message,
  });

  factory AcceptanceCheckModel.fromJson(Map<String, dynamic> json) {
    return AcceptanceCheckModel(
      type: json['type'] as String? ?? 'unknown',
      identifier: json['identifier'] as String? ?? '',
      weight: _parseDouble(json['weight']) ?? 1,
      passed: json['passed'] as bool? ?? false,
      metadata: Map<String, dynamic>.from(json['metadata'] as Map? ?? const <String, dynamic>{}),
      message: json['message'] as String?,
    );
  }

  final String type;
  final String identifier;
  final double weight;
  final bool passed;
  final Map<String, dynamic> metadata;
  final String? message;
}

class AcceptanceEvidenceModel {
  const AcceptanceEvidenceModel({
    required this.type,
    required this.identifier,
  });

  factory AcceptanceEvidenceModel.fromJson(Map<String, dynamic> json) {
    return AcceptanceEvidenceModel(
      type: json['type'] as String? ?? 'unspecified',
      identifier: json['identifier'] as String? ?? '',
    );
  }

  final String type;
  final String identifier;
}

class AcceptanceRequirementModel {
  AcceptanceRequirementModel({
    required this.id,
    required this.title,
    required this.description,
    required this.status,
    required this.completion,
    required this.quality,
    required this.tags,
    required this.checks,
    required this.evidence,
  });

  factory AcceptanceRequirementModel.fromJson(Map<String, dynamic> json) {
    return AcceptanceRequirementModel(
      id: json['id'] as String? ?? '',
      title: json['title'] as String? ?? '',
      description: json['description'] as String? ?? '',
      status: json['status'] as String? ?? 'unknown',
      completion: _parseDouble(json['completion']) ?? 0,
      quality: _parseDouble(json['quality']) ?? 0,
      tags: List<String>.from(json['tags'] as List? ?? const <String>[]),
      checks: (json['checks'] as List? ?? const <dynamic>[]) // ignore: implicit_dynamic_parameter
          .map((dynamic item) => AcceptanceCheckModel.fromJson(Map<String, dynamic>.from(item as Map)))
          .toList(growable: false),
      evidence: (json['evidence'] as List? ?? const <dynamic>[]) // ignore: implicit_dynamic_parameter
          .map((dynamic item) => AcceptanceEvidenceModel.fromJson(Map<String, dynamic>.from(item as Map)))
          .toList(growable: false),
    );
  }

  final String id;
  final String title;
  final String description;
  final String status;
  final double completion;
  final double quality;
  final List<String> tags;
  final List<AcceptanceCheckModel> checks;
  final List<AcceptanceEvidenceModel> evidence;
}

class AcceptanceSummaryModel {
  const AcceptanceSummaryModel({
    required this.requirementsTotal,
    required this.requirementsPassed,
    required this.checksTotal,
    required this.checksPassed,
    required this.completion,
    required this.quality,
  });

  factory AcceptanceSummaryModel.fromJson(Map<String, dynamic> json) {
    return AcceptanceSummaryModel(
      requirementsTotal: _parseDouble(json['requirements_total'])?.toInt() ?? 0,
      requirementsPassed: _parseDouble(json['requirements_passed'])?.toInt() ?? 0,
      checksTotal: _parseDouble(json['checks_total']) ?? 0,
      checksPassed: _parseDouble(json['checks_passed']) ?? 0,
      completion: _parseDouble(json['completion']) ?? 0,
      quality: _parseDouble(json['quality']) ?? 0,
    );
  }

  final int requirementsTotal;
  final int requirementsPassed;
  final double checksTotal;
  final double checksPassed;
  final double completion;
  final double quality;
}

class AcceptanceReportModel {
  AcceptanceReportModel({
    required this.generatedAt,
    required this.summary,
    required this.requirements,
  });

  factory AcceptanceReportModel.fromJson(Map<String, dynamic> json) {
    final summaryPayload = Map<String, dynamic>.from(json['summary'] as Map? ?? const <String, dynamic>{});
    final requirementsPayload = (json['requirements'] as List? ?? const <dynamic>[]) // ignore: implicit_dynamic_parameter
        .map((dynamic item) => AcceptanceRequirementModel.fromJson(Map<String, dynamic>.from(item as Map)))
        .toList(growable: false);

    return AcceptanceReportModel(
      generatedAt: DateTime.tryParse(json['generated_at'] as String? ?? '')?.toUtc() ??
          DateTime.fromMillisecondsSinceEpoch(0, isUtc: true),
      summary: AcceptanceSummaryModel.fromJson(summaryPayload),
      requirements: requirementsPayload,
    );
  }

  final DateTime generatedAt;
  final AcceptanceSummaryModel summary;
  final List<AcceptanceRequirementModel> requirements;

  Map<String, dynamic> toJson() {
    return <String, dynamic>{
      'generated_at': generatedAt.toIso8601String(),
      'summary': <String, dynamic>{
        'requirements_total': summary.requirementsTotal,
        'requirements_passed': summary.requirementsPassed,
        'checks_total': summary.checksTotal,
        'checks_passed': summary.checksPassed,
        'completion': summary.completion,
        'quality': summary.quality,
      },
      'requirements': requirements
          .map((AcceptanceRequirementModel requirement) => <String, dynamic>{
                'id': requirement.id,
                'title': requirement.title,
                'description': requirement.description,
                'status': requirement.status,
                'completion': requirement.completion,
                'quality': requirement.quality,
                'tags': requirement.tags,
                'checks': requirement.checks
                    .map((AcceptanceCheckModel check) => <String, dynamic>{
                          'type': check.type,
                          'identifier': check.identifier,
                          'weight': check.weight,
                          'passed': check.passed,
                          'metadata': check.metadata,
                          'message': check.message,
                        })
                    .toList(growable: false),
                'evidence': requirement.evidence
                    .map((AcceptanceEvidenceModel evidence) => <String, dynamic>{
                          'type': evidence.type,
                          'identifier': evidence.identifier,
                        })
                    .toList(growable: false),
              })
          .toList(growable: false),
    };
  }
}

class AcceptanceReportCache {
  AcceptanceReportCache({
    required this.fetchedAt,
    required this.report,
  });

  factory AcceptanceReportCache.fromJson(Map<String, dynamic> json) {
    return AcceptanceReportCache(
      fetchedAt: DateTime.tryParse(json['fetched_at'] as String? ?? '')?.toUtc() ??
          DateTime.fromMillisecondsSinceEpoch(0, isUtc: true),
      report: AcceptanceReportModel.fromJson(
        Map<String, dynamic>.from(json['report'] as Map? ?? const <String, dynamic>{}),
      ),
    );
  }

  final DateTime fetchedAt;
  final AcceptanceReportModel report;

  Map<String, dynamic> toJson() {
    return <String, dynamic>{
      'fetched_at': fetchedAt.toIso8601String(),
      'report': report.toJson(),
    };
  }
}

class AcceptanceReportSyncResult {
  AcceptanceReportSyncResult({
    required this.cache,
    required this.wasUpdated,
    this.httpStatusCode,
  });

  final AcceptanceReportCache cache;
  final bool wasUpdated;
  final int? httpStatusCode;
}

class AcceptanceReportService {
  AcceptanceReportService({
    http.Client? client,
    AppConfiguration? configuration,
    AuthSessionManager? sessionManager,
  })  : _client = client != null
            ? HttpClientFactory.create(inner: client)
            : HttpClientFactory.create(),
        _configuration = configuration ?? AppConfiguration.instance,
        _sessionManager = sessionManager;

  static const String _cacheKey = 'academy.acceptance.report.cache';

  final http.Client _client;
  final AppConfiguration _configuration;
  AuthSessionManager? _sessionManager;

  Future<AcceptanceReportSyncResult> synchronize({String? bearerToken}) async {
    final Uri endpoint = _configuration.resolveApiPath('/v1/ops/acceptance-report');
    final Map<String, String> headers = <String, String>{
      'Accept': 'application/json',
    };

    String? token = bearerToken;
    if (token == null) {
      token = await _resolveSessionManager().getValidAccessToken();
    }
    if (token != null && token.isNotEmpty) {
      headers['Authorization'] = 'Bearer $token';
    }

    http.Response response;
    try {
      response = await _client.get(endpoint, headers: headers);
    } on Exception catch (error, stackTrace) {
      debugPrint('AcceptanceReportService> network error: $error');
      debugPrint('$stackTrace');
      final cache = await loadCache();
      if (cache != null) {
        return AcceptanceReportSyncResult(cache: cache, wasUpdated: false);
      }
      rethrow;
    }

    if (response.statusCode == 401 && bearerToken == null) {
      try {
        final refreshedToken = await _resolveSessionManager().requireAccessToken(forceRefresh: true);
        headers['Authorization'] = 'Bearer $refreshedToken';
        response = await _client.get(endpoint, headers: headers);
      } on Exception catch (error) {
        debugPrint('AcceptanceReportService> token refresh failed: $error');
      }
    }

    if (response.statusCode < 200 || response.statusCode >= 300) {
      debugPrint('AcceptanceReportService> unexpected status ${response.statusCode}: ${response.body}');
      final cache = await loadCache();
      if (cache != null) {
        return AcceptanceReportSyncResult(
          cache: cache,
          wasUpdated: false,
          httpStatusCode: response.statusCode,
        );
      }
      throw http.ClientException(
        'Failed to load acceptance report (${response.statusCode})',
        endpoint,
      );
    }

    final Map<String, dynamic> payload = jsonDecode(response.body) as Map<String, dynamic>;
    final Map<String, dynamic> data = Map<String, dynamic>.from(
      (payload['data'] ?? payload) as Map,
    );

    final cache = AcceptanceReportCache(
      fetchedAt: DateTime.now().toUtc(),
      report: AcceptanceReportModel.fromJson(data),
    );

    await _persistCache(cache);

    return AcceptanceReportSyncResult(
      cache: cache,
      wasUpdated: true,
      httpStatusCode: response.statusCode,
    );
  }

  Future<AcceptanceReportCache?> loadCache() async {
    final SharedPreferences prefs = await SharedPreferences.getInstance();
    final String? jsonBlob = prefs.getString(_cacheKey);
    if (jsonBlob == null || jsonBlob.isEmpty) {
      return null;
    }

    try {
      final Map<String, dynamic> payload = jsonDecode(jsonBlob) as Map<String, dynamic>;
      return AcceptanceReportCache.fromJson(payload);
    } on FormatException catch (error) {
      debugPrint('AcceptanceReportService> failed to parse cache: $error');
      await prefs.remove(_cacheKey);
      return null;
    }
  }

  Future<void> clearCache() async {
    final SharedPreferences prefs = await SharedPreferences.getInstance();
    await prefs.remove(_cacheKey);
  }

  Future<void> _persistCache(AcceptanceReportCache cache) async {
    final SharedPreferences prefs = await SharedPreferences.getInstance();
    await prefs.setString(_cacheKey, jsonEncode(cache.toJson()));
  }

  AuthSessionManager _resolveSessionManager() {
    return _sessionManager ??= AuthSessionManager.instance;
  }
}

double? _parseDouble(dynamic value) {
  if (value == null) {
    return null;
  }
  if (value is int) {
    return value.toDouble();
  }
  if (value is double) {
    return value;
  }
  if (value is num) {
    return value.toDouble();
  }
  if (value is String) {
    return double.tryParse(value);
  }
  return null;
}
