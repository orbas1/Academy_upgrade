import 'dart:convert';

import 'package:academy_lms_app/features/search/data/search_api.dart';
import 'package:academy_lms_app/features/search/models/search_visibility_token.dart';
import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';

void main() {
  group('SearchApi', () {
    late SearchApi api;
    late SearchVisibilityToken token;

    setUp(() {
      api = SearchApi(
        client: MockClient((request) async {
          final body = jsonDecode(request.body) as Map<String, dynamic>;
          expect(body['scope'], 'communities');
          expect(body['visibility_token'], 'token-123');

          return http.Response(
            jsonEncode({
              'data': {
                'hits': [
                  {'id': 1, 'name': 'Community One'}
                ],
                'total': 1,
              },
            }),
            200,
          );
        }),
      );

      token = SearchVisibilityToken(
        token: 'token-123',
        filters: const [],
        issuedAt: DateTime.now().toUtc(),
        expiresAt: DateTime.now().toUtc().add(const Duration(minutes: 10)),
      );
    });

    test('executes search and parses response', () async {
      final payload = await api.execute(
        visibilityToken: token,
        scope: SearchScope.communities,
        query: 'test',
      );

      expect(payload.scope, SearchScope.communities);
      final page = payload.pageForScope(SearchScope.communities);
      expect(page, isNotNull);
      expect(page!.hits.length, 1);
      expect(page.hits.first.attributes['name'], 'Community One');
    });
  });
}

