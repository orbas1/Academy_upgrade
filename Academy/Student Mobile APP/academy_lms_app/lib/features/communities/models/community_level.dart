import 'package:meta/meta.dart';

@immutable
class CommunityLevel {
  const CommunityLevel({
    required this.id,
    required this.name,
    required this.minPoints,
    required this.color,
    required this.icon,
  });

  factory CommunityLevel.fromJson(Map<String, dynamic> json) {
    return CommunityLevel(
      id: json['id'] as int? ?? 0,
      name: json['name'] as String? ?? 'Level',
      minPoints: json['min_points'] as int? ?? 0,
      color: json['color'] as String? ?? '#5851EF',
      icon: json['icon'] as String?,
    );
  }

  final int id;
  final String name;
  final int minPoints;
  final String color;
  final String? icon;
}
