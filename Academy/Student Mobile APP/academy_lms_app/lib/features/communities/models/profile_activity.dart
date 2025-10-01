class ProfileActivity {
  ProfileActivity({
    required this.id,
    required this.activityType,
    required this.subjectType,
    required this.subjectId,
    required this.occurredAt,
    this.community,
    this.context = const <String, dynamic>{},
  });

  factory ProfileActivity.fromJson(Map<String, dynamic> json) {
    final communityJson = json['community'];
    return ProfileActivity(
      id: json['id'] as int,
      activityType: json['activity_type'] as String? ?? json['activityType'] as String? ?? '',
      subjectType: json['subject_type'] as String? ?? json['subjectType'] as String? ?? '',
      subjectId: json['subject_id'] as int? ?? json['subjectId'] as int? ?? 0,
      occurredAt: json['occurred_at'] as String? ?? json['occurredAt'] as String? ?? '',
      community: communityJson is Map<String, dynamic>
          ? _ProfileActivityCommunity.fromJson(communityJson)
          : null,
      context: Map<String, dynamic>.from(json['context'] as Map? ?? const <String, dynamic>{}),
    );
  }

  final int id;
  final String activityType;
  final String subjectType;
  final int subjectId;
  final String occurredAt;
  final _ProfileActivityCommunity? community;
  final Map<String, dynamic> context;
}

class _ProfileActivityCommunity {
  _ProfileActivityCommunity({
    required this.id,
    required this.name,
    required this.slug,
  });

  factory _ProfileActivityCommunity.fromJson(Map<String, dynamic> json) {
    return _ProfileActivityCommunity(
      id: json['id'] as int? ?? 0,
      name: json['name'] as String? ?? '',
      slug: json['slug'] as String? ?? '',
    );
  }

  final int id;
  final String name;
  final String slug;
}
