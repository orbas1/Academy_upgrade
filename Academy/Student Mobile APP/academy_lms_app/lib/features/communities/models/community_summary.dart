import 'package:meta/meta.dart';

@immutable
class CommunitySummary {
  const CommunitySummary({
    required this.id,
    required this.slug,
    required this.name,
    required this.tagline,
    required this.memberCount,
    required this.isMember,
    required this.visibility,
    this.onlineCount = 0,
    this.postsPerDay = 0,
    this.commentsPerDay = 0,
    this.paywallEnabled = false,
    this.lastActivityAt,
  });

  factory CommunitySummary.fromJson(Map<String, dynamic> json) {
    return CommunitySummary(
      id: json['id'] as int,
      slug: json['slug'] as String,
      name: json['name'] as String,
      tagline: json['tagline'] as String? ?? '',
      memberCount: json['member_count'] as int? ?? 0,
      isMember: json['joined'] as bool? ?? false,
      visibility: json['visibility'] as String? ?? 'public',
      onlineCount: json['online_count'] as int? ?? json['onlineCount'] as int? ?? 0,
      postsPerDay: json['posts_per_day'] as int? ?? json['postsPerDay'] as int? ?? 0,
      commentsPerDay: json['comments_per_day'] as int? ?? json['commentsPerDay'] as int? ?? 0,
      paywallEnabled: json['paywall_enabled'] as bool? ?? json['paywallEnabled'] as bool? ?? false,
      lastActivityAt: json['last_activity_at'] as String? ?? json['lastActivityAt'] as String?,
    );
  }

  final int id;
  final String slug;
  final String name;
  final String tagline;
  final int memberCount;
  final bool isMember;
  final String visibility;
  final int onlineCount;
  final int postsPerDay;
  final int commentsPerDay;
  final bool paywallEnabled;
  final String? lastActivityAt;

  Map<String, dynamic> toJson() {
    return {
      'id': id,
      'slug': slug,
      'name': name,
      'tagline': tagline,
      'member_count': memberCount,
      'joined': isMember,
      'visibility': visibility,
      'online_count': onlineCount,
      'posts_per_day': postsPerDay,
      'comments_per_day': commentsPerDay,
      'paywall_enabled': paywallEnabled,
      'last_activity_at': lastActivityAt,
    };
  }
}
