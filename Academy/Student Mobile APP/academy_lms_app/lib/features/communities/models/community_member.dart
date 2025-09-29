import 'package:meta/meta.dart';

@immutable
class CommunityMember {
  const CommunityMember({
    required this.id,
    required this.userId,
    required this.role,
    required this.status,
    required this.joinedAt,
    required this.points,
    required this.level,
  });

  factory CommunityMember.fromJson(Map<String, dynamic> json) {
    return CommunityMember(
      id: json['id'] as int,
      userId: json['user_id'] as int,
      role: json['role'] as String? ?? 'member',
      status: json['status'] as String? ?? 'pending',
      joinedAt: DateTime.tryParse(json['joined_at'] as String? ?? '') ?? DateTime.now(),
      points: json['points'] as int? ?? 0,
      level: json['level'] as int? ?? 1,
    );
  }

  final int id;
  final int userId;
  final String role;
  final String status;
  final DateTime joinedAt;
  final int points;
  final int level;

  bool get isActive => status == 'active';
}

