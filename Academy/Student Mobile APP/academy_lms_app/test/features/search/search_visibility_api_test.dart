import 'dart:convert';

import 'package:academy_lms_app/features/search/data/search_visibility_api.dart';
import 'package:academy_lms_app/features/search/models/search_visibility_token.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';

void main() {
  group('SearchVisibilityApi', () {
    test('fetchVisibilityToken parses response and forwards auth header', () async {
      final api = SearchVisibilityApi(
        client: MockClient((request) async {
          expect(request.url.path, '/api/v1/search/visibility-token');
          expect(request.headers['Accept'], 'application/json');
          expect(request.headers['Authorization'], 'Bearer abc123');

          final payload = <String, dynamic>{
            'data': <String, dynamic>{
              'token': 'encoded.signature',
              'filters': <String>["visibility = 'public'"],
              'issued_at': '2024-01-01T00:00:00Z',
              'expires_at': '2024-01-01T01:00:00Z',
            },
          };

          return http.Response(jsonEncode(payload), 200, headers: {
            'content-type': 'application/json',
          });
        }),
      );

      const tokenValue = 'abc123';
      final result = await api.fetchVisibilityToken(authToken: tokenValue);

      expect(result, isA<SearchVisibilityToken>());
      expect(result.token, 'encoded.signature');
      expect(result.filters, contains("visibility = 'public'"));
      expect(result.expiresAt.isAfter(result.issuedAt), isTrue);
    });
  });
}
