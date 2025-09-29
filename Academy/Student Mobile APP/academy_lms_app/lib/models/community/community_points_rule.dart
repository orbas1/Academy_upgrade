class CommunityPointsRuleModel {
  final int? id;
  final String action;
  final int points;
  final int cooldownSeconds;
  final bool isActive;

  const CommunityPointsRuleModel({
    this.id,
    required this.action,
    required this.points,
    this.cooldownSeconds = 0,
    this.isActive = true,
  });

  factory CommunityPointsRuleModel.fromJson(Map<String, dynamic> json) {
    return CommunityPointsRuleModel(
      id: json['id'] as int?,
      action: json['action'] as String,
      points: json['points'] as int? ?? 0,
      cooldownSeconds: json['cooldown_seconds'] as int? ?? 0,
      isActive: json['is_active'] as bool? ?? true,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'action': action,
      'points': points,
      'cooldown_seconds': cooldownSeconds,
      'is_active': isActive,
    };
  }
}
