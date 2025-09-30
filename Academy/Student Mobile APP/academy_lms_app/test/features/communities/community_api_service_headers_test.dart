import 'dart:convert';

import 'package:academy_lms_app/features/communities/data/community_api_service.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  test('attaches client identity headers and bearer tokens', () async {
    final capturedHeaders = <Map<String, String>>[];

    final mockClient = MockClient((http.Request request) async {
      capturedHeaders.add(request.headers);
      return http.Response(jsonEncode(<String, dynamic>{
        'success': true,
        'data': <Map<String, dynamic>>[],
      }), 200, headers: <String, String>{'Content-Type': 'application/json'});
    });

    final service = CommunityApiService(
      client: mockClient,
      authToken: 'test-token',
      tokenProvider: ({bool forceRefresh = false}) async => 'resolved-token',
      identityHeadersBuilder: () => <String, String>{
        'X-Academy-Client': 'mobile-app/test-suite; version=1.0.0; env=test',
        'User-Agent': 'AcademyLMS/1.0.0 (test-suite; test)',
      },
    );

    await service.fetchCommunities();

    expect(capturedHeaders, hasLength(1));
    final headers = capturedHeaders.single;
    expect(headers['Accept'], equals('application/json'));
    expect(headers['X-Academy-Client'], startsWith('mobile-app/test-suite'));
    expect(headers['User-Agent'], contains('AcademyLMS/1.0.0'));
    expect(headers['Authorization'], equals('Bearer resolved-token'));
  });
}
