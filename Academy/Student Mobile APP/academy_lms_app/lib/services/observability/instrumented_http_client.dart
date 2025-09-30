import 'dart:async';

import 'package:http/http.dart' as http;
import 'package:uuid/uuid.dart';

import 'mobile_observability_client.dart';

class InstrumentedHttpClient extends http.BaseClient {
  InstrumentedHttpClient({
    http.Client? inner,
    MobileObservabilityClient? observability,
  })  : _inner = inner ?? http.Client(),
        _observability = observability ?? MobileObservabilityClient.instance,
        _uuid = const Uuid();

  final http.Client _inner;
  final MobileObservabilityClient _observability;
  final Uuid _uuid;

  @override
  Future<http.StreamedResponse> send(http.BaseRequest request) async {
    final String requestId = request.headers['X-Request-Id'] ?? _uuid.v4();
    request.headers['X-Request-Id'] = requestId;
    final DateTime started = DateTime.now();

    try {
      final response = await _inner.send(request);
      final duration = DateTime.now().difference(started);
      _observability.recordHttpTransaction(
        url: response.request?.url ?? request.url,
        method: request.method,
        statusCode: response.statusCode,
        duration: duration,
        requestId: requestId,
      );

      return response;
    } on Exception catch (error) {
      final duration = DateTime.now().difference(started);
      _observability.recordHttpTransaction(
        url: request.url,
        method: request.method,
        statusCode: 0,
        duration: duration,
        requestId: requestId,
        errorMessage: error.toString(),
      );

      rethrow;
    }
  }

  @override
  void close() {
    _inner.close();
  }
}
