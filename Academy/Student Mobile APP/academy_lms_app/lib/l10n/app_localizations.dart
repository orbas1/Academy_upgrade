import 'dart:async';
import 'dart:convert';

import 'package:flutter/services.dart';
import 'package:flutter/widgets.dart';
import 'package:flutter_localizations/flutter_localizations.dart';

class AppLocalizations {
  AppLocalizations(this.locale, this._localizedStrings);

  final Locale locale;
  final Map<String, String> _localizedStrings;

  static const supportedLocales = [
    Locale('en'),
    Locale('es'),
    Locale('ar'),
  ];

  static const _assetPrefix = 'assets/l10n';

  static AppLocalizations of(BuildContext context) {
    final result = maybeOf(context);
    assert(result != null, 'No AppLocalizations found in context');
    return result!;
  }

  static AppLocalizations? maybeOf(BuildContext context) {
    return Localizations.of<AppLocalizations>(context, AppLocalizations);
  }

  static Future<AppLocalizations> load(Locale locale) async {
    final normalizedLocale = _normalizeLocale(locale);
    final fallbackData = await _loadJsonFile(const Locale('en'));
    final localeData = normalizedLocale == 'en'
        ? fallbackData
        : await _loadJsonFile(locale, fallback: fallbackData);

    return AppLocalizations(locale, localeData);
  }

  static String _normalizeLocale(Locale locale) {
    return locale.languageCode.toLowerCase();
  }

  static Future<Map<String, String>> _loadJsonFile(Locale locale,
      {Map<String, String>? fallback}) async {
    final languageCode = _normalizeLocale(locale);
    final assetPath = '$_assetPrefix/$languageCode.json';

    try {
      final jsonString = await rootBundle.loadString(assetPath);
      final Map<String, dynamic> mapped = jsonDecode(jsonString);
      final Map<String, String> values =
          mapped.map((key, value) => MapEntry(key, value.toString()));
      if (fallback == null) {
        return values;
      }
      return {...fallback, ...values};
    } catch (error) {
      if (fallback != null) {
        return fallback;
      }
      rethrow;
    }
  }

  String translate(String key, {Map<String, String>? params}) {
    final template = _localizedStrings[key];
    if (template == null) {
      return key;
    }

    if (params == null || params.isEmpty) {
      return template;
    }

    return params.entries.fold(template, (acc, entry) {
      return acc.replaceAll('{${entry.key}}', entry.value);
    });
  }

  bool get isRtl => locale.languageCode.toLowerCase() == 'ar';

  static const delegate = _AppLocalizationsDelegate();

  static List<LocalizationsDelegate<dynamic>> localizationsDelegates = const [
    delegate,
    DefaultWidgetsLocalizations.delegate,
    DefaultMaterialLocalizations.delegate,
    DefaultCupertinoLocalizations.delegate,
  ];
}

class _AppLocalizationsDelegate extends LocalizationsDelegate<AppLocalizations> {
  const _AppLocalizationsDelegate();

  @override
  bool isSupported(Locale locale) {
    return AppLocalizations.supportedLocales
        .any((supported) => supported.languageCode == locale.languageCode);
  }

  @override
  Future<AppLocalizations> load(Locale locale) {
    return AppLocalizations.load(locale);
  }

  @override
  bool shouldReload(covariant LocalizationsDelegate<AppLocalizations> old) => false;
}
