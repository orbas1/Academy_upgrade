import 'dart:async';
import 'dart:convert';

import 'package:flutter/foundation.dart';
import 'package:http/http.dart' as http;
import 'package:uuid/uuid.dart';

import '../../config/app_configuration.dart';
import '../security/auth_session_manager.dart';

class MobileObservabilityClient {
  MobileObservabilityClient._internal({
    http.Client? transport,
    AppConfiguration? configuration,
    AuthSessionManager? sessionManager,
    Duration? flushInterval,
  })  : _transport = transport ?? http.Client(),
        _configuration = configuration ?? AppConfiguration.instance,
        _sessionManager = sessionManager ?? AuthSessionManager.instance,
        _flushInterval = flushInterval ?? const Duration(seconds: 10);

  static final MobileObservabilityClient instance =
      MobileObservabilityClient._internal();

  final http.Client _transport;
  AppConfiguration _configuration;
  AuthSessionManager _sessionManager;
  Duration _flushInterval;

  final List<Map<String, Object?>> _queue = <Map<String, Object?>>[];
  Timer? _flushTimer;
  bool _flushInProgress = false;
  final String _sessionId = const Uuid().v4();

  void configure({
    AppConfiguration? configuration,
    AuthSessionManager? sessionManager,
    Duration? flushInterval,
  }) {
    if (configuration != null) {
      _configuration = configuration;
    }
    if (sessionManager != null) {
      _sessionManager = sessionManager;
    }
    if (flushInterval != null) {
      _flushInterval = flushInterval;
      _flushTimer?.cancel();
      _flushTimer = null;
    }
  }

  void recordHttpTransaction({
    required Uri url,
    required String method,
    required int statusCode,
    required Duration duration,
    String? requestId,
    String? networkType,
    String? errorMessage,
  }) {
    final normalizedMethod = method.toUpperCase();
    final sanitizedRoute = _sanitizeRoute(url);

    final metric = <String, Object?>{
      'name': 'http_request',
      'timestamp': DateTime.now().toUtc().toIso8601String(),
      'method': normalizedMethod,
      'route': sanitizedRoute,
      'path': url.path,
      'duration_ms': duration.inMilliseconds +
          (duration.inMicroseconds.remainder(1000) / 1000.0),
      'status_code': statusCode,
      if (requestId != null) 'request_id': requestId,
      if (networkType != null && networkType.isNotEmpty) 'network_type': networkType,
      if (errorMessage != null && errorMessage.isNotEmpty) 'error': errorMessage,
    };

    _queue.add(metric);
    if (_queue.length > 250) {
      _queue.removeRange(0, _queue.length - 250);
    }

    if (_queue.length >= 10) {
      unawaited(flush());
    } else {
      _scheduleFlush();
    }
  }

  Future<void> flush() async {
    if (_flushInProgress || _queue.isEmpty) {
      return;
    }

    _flushInProgress = true;
    _flushTimer?.cancel();
    _flushTimer = null;

    final batch = List<Map<String, Object?>>.from(_queue);
    final endpoint = _configuration.observabilityIngestUrl;
    final headers = <String, String>{
      'Content-Type': 'application/json',
      if (_configuration.observabilityApiKey != null)
        'X-Observability-Api-Key': _configuration.observabilityApiKey!,
    };

    try {
      final token = await _sessionManager.getValidAccessToken();
      if (token != null && token.isNotEmpty) {
        headers['Authorization'] = 'Bearer $token';
      }

      final response = await _transport.post(
        endpoint,
        headers: headers,
        body: jsonEncode(<String, Object?>{
          'session_id': _sessionId,
          'environment': _configuration.environment,
          'metrics': batch,
        }),
      );

      if (response.statusCode >= 200 && response.statusCode < 300) {
        _queue.removeRange(0, batch.length);
      } else {
        debugPrint(
            'MobileObservabilityClient> failed to flush metrics: ${response.statusCode}');
      }
    } catch (error, stackTrace) {
      debugPrint('MobileObservabilityClient> $error');
      debugPrint('$stackTrace');
    } finally {
      _flushInProgress = false;
      if (_queue.isNotEmpty) {
        _scheduleFlush();
      }
    }
  }

  void dispose() {
    _flushTimer?.cancel();
    _flushTimer = null;
  }

  void _scheduleFlush() {
    _flushTimer ??= Timer(_flushInterval, () {
      _flushTimer = null;
      unawaited(flush());
    });
  }

  String _sanitizeRoute(Uri url) {
    final segments = url.pathSegments;
    if (segments.isEmpty) {
      return 'root';
    }

    final buffer = StringBuffer();
    for (final segment in segments) {
      if (segment.isEmpty) {
        continue;
      }
      if (buffer.isNotEmpty) {
        buffer.write('.');
      }
      buffer.write(segment.replaceAll(RegExp(r'[^a-zA-Z0-9_-]'), '_'));
    }

    return buffer.isEmpty ? 'root' : buffer.toString();
  }
}
