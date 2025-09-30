import 'package:meta/meta.dart';

@immutable
class CommunityFeedItem {
  const CommunityFeedItem({
    required this.id,
    required this.type,
    required this.authorName,
    required this.body,
    required this.bodyMarkdown,
    required this.createdAt,
    required this.likeCount,
    required this.commentCount,
    required this.visibility,
    required this.isLiked,
    required this.paywallTierId,
  });

  factory CommunityFeedItem.fromJson(Map<String, dynamic> json) {
    return CommunityFeedItem(
      id: json['id'] as int,
      type: json['type'] as String? ?? 'text',
      authorName: json['author_name'] as String? ?? 'Unknown',
      body: json['body'] as String? ?? '',
      bodyMarkdown: json['body_md'] as String? ?? json['body'] as String? ?? '',
      createdAt: DateTime.tryParse(json['created_at'] as String? ?? '') ?? DateTime.now(),
      likeCount: json['like_count'] as int? ?? 0,
      commentCount: json['comment_count'] as int? ?? 0,
      visibility: json['visibility'] as String? ?? 'community',
      isLiked: json['liked'] as bool? ?? false,
      paywallTierId: json['paywall_tier_id'] as int?,
    );
  }

  final int id;
  final String type;
  final String authorName;
  final String body;
  final String bodyMarkdown;
  final DateTime createdAt;
  final int likeCount;
  final int commentCount;
  final String visibility;
  final bool isLiked;
  final int? paywallTierId;

  Map<String, dynamic> toJson() {
    return <String, dynamic>{
      'id': id,
      'type': type,
      'author_name': authorName,
      'body': body,
      'body_md': bodyMarkdown,
      'created_at': createdAt.toIso8601String(),
      'like_count': likeCount,
      'comment_count': commentCount,
      'visibility': visibility,
      'liked': isLiked,
      'paywall_tier_id': paywallTierId,
    };
  }
}
