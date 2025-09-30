class DeviceSession {
  DeviceSession({
    required this.id,
    this.deviceName,
    this.platform,
    this.appVersion,
    this.ipAddress,
    this.lastSeenAt,
    this.trustedAt,
    this.revokedAt,
    required this.isTrusted,
    required this.isRevoked,
    required this.isCurrentDevice,
  });

  factory DeviceSession.fromJson(Map<String, dynamic> json) {
    DateTime? parseDate(dynamic value) {
      if (value is String && value.isNotEmpty) {
        return DateTime.tryParse(value)?.toLocal();
      }
      return null;
    }

    return DeviceSession(
      id: json['id'] as int,
      deviceName: json['device_name'] as String?,
      platform: json['platform'] as String?,
      appVersion: json['app_version'] as String?,
      ipAddress: json['ip_address'] as String?,
      lastSeenAt: parseDate(json['last_seen_at']),
      trustedAt: parseDate(json['trusted_at']),
      revokedAt: parseDate(json['revoked_at']),
      isTrusted: json['is_trusted'] as bool? ?? false,
      isRevoked: json['is_revoked'] as bool? ?? false,
      isCurrentDevice: json['is_current_device'] as bool? ?? false,
    );
  }

  final int id;
  final String? deviceName;
  final String? platform;
  final String? appVersion;
  final String? ipAddress;
  final DateTime? lastSeenAt;
  final DateTime? trustedAt;
  final DateTime? revokedAt;
  final bool isTrusted;
  final bool isRevoked;
  final bool isCurrentDevice;

  DeviceSession copyWith({
    bool? isTrusted,
    bool? isRevoked,
    DateTime? revokedAt,
    DateTime? trustedAt,
  }) {
    return DeviceSession(
      id: id,
      deviceName: deviceName,
      platform: platform,
      appVersion: appVersion,
      ipAddress: ipAddress,
      lastSeenAt: lastSeenAt,
      trustedAt: trustedAt ?? this.trustedAt,
      revokedAt: revokedAt ?? this.revokedAt,
      isTrusted: isTrusted ?? this.isTrusted,
      isRevoked: isRevoked ?? this.isRevoked,
      isCurrentDevice: isCurrentDevice,
    );
  }
}
