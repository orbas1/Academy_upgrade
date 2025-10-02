import 'upload_quota_status.dart';

class UploadQuotaSummary {
  UploadQuotaSummary({
    required this.generatedAt,
    required this.user,
    required this.community,
  });

  final DateTime generatedAt;
  final UploadQuotaStatus? user;
  final UploadQuotaStatus? community;

  factory UploadQuotaSummary.fromMap(Map<String, dynamic> map) {
    final generatedRaw = map['generated_at'];
    DateTime generatedAt;
    if (generatedRaw is String) {
      generatedAt = DateTime.tryParse(generatedRaw) ?? DateTime.now().toUtc();
    } else {
      generatedAt = DateTime.now().toUtc();
    }

    UploadQuotaStatus? parseStatus(dynamic value) {
      if (value is Map<String, dynamic> && value.isNotEmpty) {
        return UploadQuotaStatus.fromMap(value);
      }
      return null;
    }

    return UploadQuotaSummary(
      generatedAt: generatedAt,
      user: parseStatus(map['user']),
      community: parseStatus(map['community']),
    );
  }

  bool get hasAnyQuota => user != null || community != null;
}
