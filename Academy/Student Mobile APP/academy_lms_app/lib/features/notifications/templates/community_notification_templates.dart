import 'package:flutter/foundation.dart';

class RenderedCommunityNotification {
  const RenderedCommunityNotification({
    required this.event,
    required this.locale,
    required this.title,
    required this.body,
    required this.data,
  });

  final String event;
  final String locale;
  final String title;
  final String body;
  final Map<String, String> data;

  Map<String, String> toMap() => <String, String>{
        'event': event,
        'locale': locale,
        'title': title,
        'body': body,
        ...data,
      };
}

class CommunityNotificationTemplates {
  CommunityNotificationTemplates._();

  static const String _fallbackLocale = 'en';

  static final Map<String, Map<String, Map<String, String>>> _definitions =
      <String, Map<String, Map<String, String>>>{
    'invite': <String, Map<String, String>>{
      'en': <String, String>{
        'title': 'Invitation to {{community}}',
        'body': '{{inviter}} invited you to join {{community}}.',
      },
      'es': <String, String>{
        'title': 'Invitación a {{community}}',
        'body': '{{inviter}} te invitó a unirte a {{community}}.',
      },
    },
    'approval': <String, Map<String, String>>{
      'en': <String, String>{
        'title': 'Access granted to {{community}}',
        'body': 'Your request was approved. Jump back in and say hi.',
      },
      'es': <String, String>{
        'title': 'Acceso concedido a {{community}}',
        'body': 'Tu solicitud fue aprobada. Vuelve y saluda.',
      },
    },
    'new_reply': <String, Map<String, String>>{
      'en': <String, String>{
        'title': '{{actor}} replied in {{community}}',
        'body': 'They responded to "{{post}}".',
      },
      'es': <String, String>{
        'title': '{{actor}} respondió en {{community}}',
        'body': 'Respondió a "{{post}}".',
      },
    },
    'mention': <String, Map<String, String>>{
      'en': <String, String>{
        'title': '{{actor}} mentioned you',
        'body': 'Jump into the conversation in {{community}}.',
      },
      'es': <String, String>{
        'title': '{{actor}} te mencionó',
        'body': 'Únete a la conversación en {{community}}.',
      },
    },
    'purchase_receipt': <String, Map<String, String>>{
      'en': <String, String>{
        'title': 'Payment received',
        'body': 'Thanks for supporting {{community}}.',
      },
      'es': <String, String>{
        'title': 'Pago recibido',
        'body': 'Gracias por apoyar a {{community}}.',
      },
    },
    'reminder': <String, Map<String, String>>{
      'en': <String, String>{
        'title': '{{event}} starts soon',
        'body': 'Happening in {{community}} on {{date}}.',
      },
      'es': <String, String>{
        'title': '{{event}} comienza pronto',
        'body': 'Sucede en {{community}} el {{date}}.',
      },
    },
    'digest': <String, Map<String, String>>{
      'en': <String, String>{
        'title': '{{community}} digest',
        'body': 'Your {{period}} highlights are ready.',
      },
      'es': <String, String>{
        'title': 'Resumen de {{community}}',
        'body': 'Tus destacados de {{period}} están listos.',
      },
    },
  };

  static Set<String> get supportedEvents => _definitions.keys.toSet();

  static RenderedCommunityNotification render(
    String event, {
    String locale = _fallbackLocale,
    Map<String, String> variables = const <String, String>{},
    Uri? deepLink,
  }) {
    final Map<String, Map<String, String>>? eventLocales =
        _definitions[event];

    if (eventLocales == null) {
      throw ArgumentError.value(event, 'event', 'Unsupported notification event');
    }

    final String resolvedLocale =
        eventLocales.containsKey(locale) ? locale : _fallbackLocale;
    final Map<String, String> template =
        eventLocales[resolvedLocale] ?? eventLocales[_fallbackLocale]!;

    final String title = _render(template['title']!, variables);
    final String body = _render(template['body']!, variables);

    final Map<String, String> payload = <String, String>{
      'category': event,
      if (deepLink != null) 'deeplink': deepLink.toString(),
      ...variables,
    };

    return RenderedCommunityNotification(
      event: event,
      locale: resolvedLocale,
      title: title,
      body: body,
      data: payload,
    );
  }

  static String _render(String template, Map<String, String> variables) {
    return variables.entries.fold<String>(template, (String result, entry) {
      return result.replaceAll('{{${entry.key}}}', entry.value);
    });
  }
}

class CommunityNotificationTemplateRegistry extends ChangeNotifier {
  CommunityNotificationTemplateRegistry({String locale = 'en'}) : _locale = locale;

  String _locale;

  String get locale => _locale;

  set locale(String value) {
    if (value == _locale) {
      return;
    }
    _locale = value;
    notifyListeners();
  }

  RenderedCommunityNotification resolve(
    String event, {
    Map<String, String> variables = const <String, String>{},
    Uri? deepLink,
  }) {
    return CommunityNotificationTemplates.render(
      event,
      locale: _locale,
      variables: variables,
      deepLink: deepLink,
    );
  }
}
