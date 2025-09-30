import 'package:academy_lms_app/widgets/environment_banner.dart';
import 'package:flutter/material.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  testWidgets('shows banner for non-production environments', (tester) async {
    await tester.pumpWidget(
      const MaterialApp(
        home: EnvironmentBanner(
          environment: 'staging',
          child: Text('Hello'),
        ),
      ),
    );

    expect(find.byType(Banner), findsOneWidget);
    expect(find.text('STAGING'), findsOneWidget);
  });

  testWidgets('hides banner for production', (tester) async {
    await tester.pumpWidget(
      const MaterialApp(
        home: EnvironmentBanner(
          environment: 'production',
          child: Text('Hello'),
        ),
      ),
    );

    expect(find.byType(Banner), findsNothing);
    expect(find.text('Hello'), findsOneWidget);
  });
}
