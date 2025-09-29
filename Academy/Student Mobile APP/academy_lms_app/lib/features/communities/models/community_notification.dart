class CommunityNotification {
  const CommunityNotification({
    required this.id,
    required this.communityId,
    required this.event,
    required this.subject,
    required this.message,
    required this.createdAt,
    this.actorId,
    this.data = const <String, dynamic>{},
  });

  factory CommunityNotification.fromJson(Map<String, dynamic> json) {
    return CommunityNotification(
      id: json['id'] as String? ?? '',
      communityId: json['community_id'] as int? ?? 0,
      event: json['event'] as String? ?? 'community.generic',
      subject: json['subject'] as String? ?? '',
      message: json['message'] as String? ?? '',
      createdAt: DateTime.tryParse(json['created_at'] as String? ?? '') ?? DateTime.now(),
      actorId: json['actor_id'] as int?,
      data: json['data'] as Map<String, dynamic>? ?? const <String, dynamic>{},
    );
  }

  final String id;
  final int communityId;
  final String event;
  final String subject;
  final String message;
  final DateTime createdAt;
  final int? actorId;
  final Map<String, dynamic> data;
}
