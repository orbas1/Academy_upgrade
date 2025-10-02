class UploadQuotaStatus {
  UploadQuotaStatus({
    required this.scope,
    required this.scopeId,
    required this.limitBytes,
    required this.usedBytes,
    required this.remainingBytes,
    required this.windowDurationDays,
    required this.windowStartedAt,
  });

  final String scope;
  final int? scopeId;
  final int? limitBytes;
  final int usedBytes;
  final int? remainingBytes;
  final int windowDurationDays;
  final DateTime? windowStartedAt;

  factory UploadQuotaStatus.fromMap(Map<String, dynamic> map) {
    final scope = map['scope'] as String? ?? 'unknown';
    final scopeIdDynamic = map['scope_id'];
    final scopeId = scopeIdDynamic is int
        ? scopeIdDynamic
        : scopeIdDynamic is num
            ? scopeIdDynamic.toInt()
            : null;

    final limitDynamic = map['limit_bytes'];
    final limitBytes = limitDynamic is int
        ? limitDynamic
        : limitDynamic is num
            ? limitDynamic.toInt()
            : null;

    final usedDynamic = map['used_bytes'];
    final usedBytes = usedDynamic is int
        ? usedDynamic
        : usedDynamic is num
            ? usedDynamic.toInt()
            : 0;

    final remainingDynamic = map['remaining_bytes'];
    final remainingBytes = remainingDynamic is int
        ? remainingDynamic
        : remainingDynamic is num
            ? remainingDynamic.toInt()
            : null;

    final windowDynamic = map['window_duration_days'];
    final windowDays = windowDynamic is int
        ? windowDynamic
        : windowDynamic is num
            ? windowDynamic.toInt()
            : 0;

    final windowStartedAt = map['window_started_at'];
    DateTime? started;
    if (windowStartedAt is String && windowStartedAt.isNotEmpty) {
      started = DateTime.tryParse(windowStartedAt);
    }

    return UploadQuotaStatus(
      scope: scope,
      scopeId: scopeId,
      limitBytes: limitBytes,
      usedBytes: usedBytes,
      remainingBytes: remainingBytes,
      windowDurationDays: windowDays,
      windowStartedAt: started,
    );
  }

  double get usedMegabytes => usedBytes / (1024 * 1024);

  double? get limitMegabytes =>
      limitBytes != null ? limitBytes! / (1024 * 1024) : null;

  double? get remainingMegabytes => remainingBytes != null
      ? remainingBytes! / (1024 * 1024)
      : null;

  bool get isUnlimited => limitBytes == null || limitBytes == 0;
}
