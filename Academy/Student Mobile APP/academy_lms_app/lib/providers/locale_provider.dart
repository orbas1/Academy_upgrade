import 'dart:ui';

import 'package:flutter/foundation.dart';

import '../l10n/app_localizations.dart';
import '../services/localization/locale_storage.dart';

class LocaleProvider with ChangeNotifier {
  LocaleProvider({LocaleStorage? storage}) : _storage = storage ?? LocaleStorage();

  final LocaleStorage _storage;
  Locale _locale = const Locale('en');
  bool _isInitialized = false;

  Locale get locale => _locale;
  bool get isInitialized => _isInitialized;
  bool get isRtl => _locale.languageCode.toLowerCase() == 'ar';

  Future<void> loadLocale() async {
    _locale = await _storage.readLocale();
    _locale = _validateLocale(_locale);
    _isInitialized = true;
    notifyListeners();
  }

  Future<void> updateLocale(Locale locale) async {
    final resolved = _validateLocale(locale);
    if (resolved == _locale && _isInitialized) {
      return;
    }

    _locale = resolved;
    await _storage.writeLocale(resolved);
    _isInitialized = true;
    notifyListeners();
  }

  Locale _validateLocale(Locale locale) {
    for (final supported in AppLocalizations.supportedLocales) {
      if (supported.languageCode == locale.languageCode) {
        return supported;
      }
    }
    return const Locale('en');
  }
}
