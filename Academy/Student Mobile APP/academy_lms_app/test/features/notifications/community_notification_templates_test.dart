import 'package:academy_lms_app/features/notifications/templates/community_notification_templates.dart';
import 'package:flutter_test/flutter_test.dart';

void main() {
  test('renders localized notification template with variables', () {
    final RenderedCommunityNotification notification =
        CommunityNotificationTemplates.render(
      'invite',
      locale: 'es',
      variables: <String, String>{
        'community': 'Makers Guild',
        'inviter': 'Grace',
      },
      deepLink: Uri.parse('app://academy/communities/makers'),
    );

    expect(notification.locale, 'es');
    expect(notification.title, 'Invitación a Makers Guild');
    expect(notification.body, 'Grace te invitó a unirte a Makers Guild.');
    expect(notification.data['deeplink'], 'app://academy/communities/makers');
    expect(notification.data['community'], 'Makers Guild');
  });

  test('falls back to english when locale is unsupported', () {
    final RenderedCommunityNotification notification =
        CommunityNotificationTemplates.render(
      'reminder',
      locale: 'fr',
      variables: <String, String>{
        'community': 'Makers Guild',
        'event': 'Town Hall',
        'date': '24 May',
      },
    );

    expect(notification.locale, 'en');
    expect(notification.title, 'Town Hall starts soon');
    expect(notification.body, 'Happening in Makers Guild on 24 May.');
  });

  test('registry notifies listeners when locale changes', () {
    final CommunityNotificationTemplateRegistry registry =
        CommunityNotificationTemplateRegistry(locale: 'en');

    int notifications = 0;
    registry.addListener(() => notifications++);

    registry.locale = 'es';
    expect(notifications, 1);

    final RenderedCommunityNotification rendered = registry.resolve(
      'digest',
      variables: <String, String>{
        'community': 'Makers Guild',
        'period': 'week',
      },
    );

    expect(rendered.locale, 'es');
    expect(rendered.title, 'Resumen de Makers Guild');
  });
}
