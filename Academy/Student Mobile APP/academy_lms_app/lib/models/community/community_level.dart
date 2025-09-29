class CommunityLevelModel {
  final int? id;
  final int level;
  final String name;
  final String? description;
  final int pointsRequired;
  final Map<String, dynamic>? rewards;

  const CommunityLevelModel({
    this.id,
    required this.level,
    required this.name,
    this.description,
    required this.pointsRequired,
    this.rewards,
  });

  factory CommunityLevelModel.fromJson(Map<String, dynamic> json) {
    return CommunityLevelModel(
      id: json['id'] as int?,
      level: json['level'] as int,
      name: json['name'] as String,
      description: json['description'] as String?,
      pointsRequired: json['points_required'] as int? ?? 0,
      rewards: json['rewards'] is Map<String, dynamic>
          ? (json['rewards'] as Map<String, dynamic>)
          : null,
    );
  }

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'level': level,
      'name': name,
      'description': description,
      'points_required': pointsRequired,
      'rewards': rewards,
    };
  }
}
