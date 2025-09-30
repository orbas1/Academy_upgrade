import 'package:meta/meta.dart';

@immutable
class PointsSummary {
  const PointsSummary({
    required this.totalPoints,
    required this.currentLevel,
    required this.nextLevel,
    required this.nextLevelPoints,
    required this.dailyCapRemaining,
    required this.streakDays,
  });

  factory PointsSummary.fromJson(Map<String, dynamic> json) {
    return PointsSummary(
      totalPoints: json['total_points'] as int? ?? 0,
      currentLevel: json['current_level'] as int? ?? 0,
      nextLevel: json['next_level'] as int?,
      nextLevelPoints: json['next_level_points'] as int?,
      dailyCapRemaining: json['daily_cap_remaining'] as int? ?? 0,
      streakDays: json['streak_days'] as int? ?? 0,
    );
  }

  final int totalPoints;
  final int currentLevel;
  final int? nextLevel;
  final int? nextLevelPoints;
  final int dailyCapRemaining;
  final int streakDays;

  PointsSummary copyWith({
    int? totalPoints,
    int? currentLevel,
    int? nextLevel,
    int? nextLevelPoints,
    int? dailyCapRemaining,
    int? streakDays,
  }) {
    return PointsSummary(
      totalPoints: totalPoints ?? this.totalPoints,
      currentLevel: currentLevel ?? this.currentLevel,
      nextLevel: nextLevel ?? this.nextLevel,
      nextLevelPoints: nextLevelPoints ?? this.nextLevelPoints,
      dailyCapRemaining: dailyCapRemaining ?? this.dailyCapRemaining,
      streakDays: streakDays ?? this.streakDays,
    );
  }
}
