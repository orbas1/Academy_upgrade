import 'dart:convert';

import 'package:flutter_test/flutter_test.dart';
import 'package:http/http.dart' as http;
import 'package:http/testing.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../../../lib/services/acceptance/acceptance_report_service.dart';
import '../../../lib/config/app_configuration.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  setUp(() {
    SharedPreferences.setMockInitialValues(<String, Object?>{});
  });

  group('AcceptanceReportService', () {
    test('synchronize persists acceptance report cache', () async {
      final mockClient = MockClient((http.Request request) async {
        expect(request.url.path, '/api/v1/ops/acceptance-report');
        expect(request.headers['Authorization'], 'Bearer token');

        final payload = <String, dynamic>{
          'data': <String, dynamic>{
            'generated_at': '2024-06-01T12:00:00Z',
            'summary': <String, dynamic>{
              'requirements_total': 4,
              'requirements_passed': 4,
              'checks_total': 16,
              'checks_passed': 16,
              'completion': 100,
              'quality': 100,
            },
            'requirements': <Map<String, dynamic>>[
              <String, dynamic>{
                'id': 'AC-01',
                'title': 'Domain',
                'description': 'Domain coverage',
                'status': 'pass',
                'completion': 100,
                'quality': 100,
                'tags': <String>['backend'],
                'checks': <Map<String, dynamic>>[
                  <String, dynamic>{
                    'type': 'class',
                    'identifier': 'App\\Models\\Community\\Community',
                    'weight': 1,
                    'passed': true,
                    'metadata': <String, dynamic>{},
                  },
                ],
                'evidence': <Map<String, dynamic>>[
                  <String, dynamic>{
                    'type': 'feature-test',
                    'identifier': 'Tests\\Feature\\CommunityControllerTest',
                  },
                ],
              },
            ],
          },
        };

        return http.Response(jsonEncode(payload), 200, headers: <String, String>{'content-type': 'application/json'});
      });

      final service = AcceptanceReportService(
        client: mockClient,
        configuration: AppConfiguration.instance,
      );

      final result = await service.synchronize(bearerToken: 'token');

      expect(result.wasUpdated, isTrue);
      expect(result.cache.report.summary.requirementsTotal, 4);
      expect(result.cache.report.requirements.first.id, 'AC-01');

      final cached = await service.loadCache();
      expect(cached, isNotNull);
      expect(cached!.report.summary.completion, 100);
    });
  });
}
