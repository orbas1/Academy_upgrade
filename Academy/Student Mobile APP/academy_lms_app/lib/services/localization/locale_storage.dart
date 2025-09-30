import 'dart:ui';

import 'package:shared_preferences/shared_preferences.dart';

import '../../l10n/app_localizations.dart';

class LocaleStorage {
  static const _storageKey = 'preferred_locale';

  Future<Locale> readLocale({Locale fallback = const Locale('en')}) async {
    final prefs = await SharedPreferences.getInstance();
    final stored = prefs.getString(_storageKey);
    if (stored == null) {
      return fallback;
    }

    return _resolveLocale(stored) ?? fallback;
  }

  Future<void> writeLocale(Locale locale) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_storageKey, locale.languageCode);
  }

  Locale? _resolveLocale(String languageCode) {
    final normalized = languageCode.toLowerCase();
    for (final supported in AppLocalizations.supportedLocales) {
      if (supported.languageCode.toLowerCase() == normalized) {
        return supported;
      }
    }
    return null;
  }
}
