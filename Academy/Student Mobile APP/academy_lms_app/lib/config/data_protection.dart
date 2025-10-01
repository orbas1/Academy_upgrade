class DataProtectionConfiguration {
  DataProtectionConfiguration._({
    required this.personalDataRetentionDays,
    required this.secureWipeOnLogout,
    required this.legacySensitiveKeys,
  });

  DataProtectionConfiguration._internal()
      : this._(
          personalDataRetentionDays: const int.fromEnvironment(
            'ACADEMY_MOBILE_PERSONAL_DATA_RETENTION_DAYS',
            defaultValue: 30,
          ),
          secureWipeOnLogout: const bool.fromEnvironment(
            'ACADEMY_MOBILE_SECURE_WIPE_ON_LOGOUT',
            defaultValue: true,
          ),
          legacySensitiveKeys: _parseLegacyKeys(
            const String.fromEnvironment(
              'ACADEMY_MOBILE_LEGACY_SENSITIVE_KEYS',
              defaultValue:
                  'password,user,password_confirmation,user_name,user_photo,school_name,email',
            ),
          ),
        );

  static final DataProtectionConfiguration instance =
      DataProtectionConfiguration._internal();

  final int personalDataRetentionDays;
  final bool secureWipeOnLogout;
  final List<String> legacySensitiveKeys;

  factory DataProtectionConfiguration.override({
    required int personalDataRetentionDays,
    required bool secureWipeOnLogout,
    List<String>? legacySensitiveKeys,
  }) {
    return DataProtectionConfiguration._(
      personalDataRetentionDays: personalDataRetentionDays,
      secureWipeOnLogout: secureWipeOnLogout,
      legacySensitiveKeys: _normaliseKeys(legacySensitiveKeys ?? const <String>[]),
    );
  }

  static List<String> _parseLegacyKeys(String raw) {
    return _normaliseKeys(raw.split(','));
  }

  static List<String> _normaliseKeys(Iterable<String> keys) {
    return keys
        .map((key) => key.trim())
        .where((key) => key.isNotEmpty)
        .toList(growable: false);
  }
}
