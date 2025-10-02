class DataProtectionConfiguration {
  DataProtectionConfiguration._({
    required this.personalDataRetentionDays,
    required this.secureWipeOnLogout,
    required this.legacySensitiveKeys,
    required this.cacheDirectoryNames,
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
          legacySensitiveKeys: _parseList(
            const String.fromEnvironment(
              'ACADEMY_MOBILE_LEGACY_SENSITIVE_KEYS',
              defaultValue:
                  'password,user,password_confirmation,user_name,user_photo,school_name,email',
            ),
          ),
          cacheDirectoryNames: _parseList(
            const String.fromEnvironment(
              'ACADEMY_MOBILE_CACHE_DIRECTORIES',
              defaultValue: 'downloads,offline_cache',
            ),
          ),
        );

  static final DataProtectionConfiguration instance =
      DataProtectionConfiguration._internal();

  final int personalDataRetentionDays;
  final bool secureWipeOnLogout;
  final List<String> legacySensitiveKeys;
  final List<String> cacheDirectoryNames;

  factory DataProtectionConfiguration.override({
    required int personalDataRetentionDays,
    required bool secureWipeOnLogout,
    List<String>? legacySensitiveKeys,
    List<String>? cacheDirectoryNames,
  }) {
    return DataProtectionConfiguration._(
      personalDataRetentionDays: personalDataRetentionDays,
      secureWipeOnLogout: secureWipeOnLogout,
      legacySensitiveKeys: _normaliseList(legacySensitiveKeys ?? const <String>[]),
      cacheDirectoryNames: _normaliseList(cacheDirectoryNames ?? const <String>[]),
    );
  }

  static List<String> _parseList(String raw) {
    return _normaliseList(raw.split(','));
  }

  static List<String> _normaliseList(Iterable<String> entries) {
    return entries
        .map((entry) => entry.trim())
        .where((entry) => entry.isNotEmpty)
        .toList(growable: false);
  }
}
