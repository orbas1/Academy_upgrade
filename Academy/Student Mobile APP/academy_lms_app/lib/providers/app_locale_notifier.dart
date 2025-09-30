import 'package:flutter/material.dart';
import 'package:shared_preferences/shared_preferences.dart';

import '../l10n/app_localizations.dart';

class AppLocaleNotifier extends ChangeNotifier {
  AppLocaleNotifier() {
    _hydrate();
  }

  static const _storageKey = 'preferred_locale_code';
  Locale _locale = AppLocalizations.supportedLocales.first;
  bool _hydrated = false;

  Locale get locale => _locale;
  bool get isHydrated => _hydrated;

  Future<void> setLocale(Locale locale) async {
    if (!AppLocalizations.isSupported(locale)) {
      return;
    }

    _locale = Locale(locale.languageCode);
    notifyListeners();

    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_storageKey, _locale.languageCode);
  }

  Future<void> _hydrate() async {
    final prefs = await SharedPreferences.getInstance();
    final storedCode = prefs.getString(_storageKey);

    if (storedCode != null) {
      final storedLocale = Locale(storedCode);
      if (AppLocalizations.isSupported(storedLocale)) {
        _locale = storedLocale;
      }
    }

    _hydrated = true;
    notifyListeners();
  }
}
