import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../l10n/app_localizations.dart';
import '../../providers/locale_provider.dart';

class LanguageSelector extends StatelessWidget {
  const LanguageSelector({super.key});

  @override
  Widget build(BuildContext context) {
    return Consumer<LocaleProvider>(
      builder: (context, localeProvider, _) {
        final l10n = AppLocalizations.of(context);
        final locale = localeProvider.locale;
        return Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              l10n.translate('filter.language'),
              style: const TextStyle(
                fontSize: 16,
                fontWeight: FontWeight.w600,
              ),
            ),
            const SizedBox(height: 12),
            InputDecorator(
              decoration: const InputDecoration(
                border: OutlineInputBorder(),
                contentPadding: EdgeInsets.symmetric(horizontal: 12, vertical: 6),
              ),
              child: DropdownButtonHideUnderline(
                child: DropdownButton<Locale>(
                  value: locale,
                  isExpanded: true,
                  onChanged: (newLocale) async {
                    if (newLocale != null) {
                      await localeProvider.updateLocale(newLocale);
                      if (context.mounted) {
                        ScaffoldMessenger.of(context).showSnackBar(
                          SnackBar(
                            content: Text(
                              l10n.translate('language.updateSuccess', params: {
                                'language': _localizedName(l10n, newLocale),
                              }),
                            ),
                          ),
                        );
                      }
                    }
                  },
                  items: AppLocalizations.supportedLocales.map((supported) {
                    return DropdownMenuItem<Locale>(
                      value: supported,
                      child: Text(_localizedName(l10n, supported)),
                    );
                  }).toList(),
                ),
              ),
            ),
          ],
        );
      },
    );
  }

  String _localizedName(AppLocalizations l10n, Locale locale) {
    final key = 'language.name.${locale.languageCode}';
    return l10n.translate(key);
  }
}
