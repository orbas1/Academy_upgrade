import 'package:meta/meta.dart';

@immutable
class CommunityFeedItem {
  const CommunityFeedItem({
    required this.id,
    required this.type,
    required this.authorName,
    required this.body,
    required this.createdAt,
    required this.likeCount,
    required this.commentCount,
    required this.visibility,
  });

  factory CommunityFeedItem.fromJson(Map<String, dynamic> json) {
    return CommunityFeedItem(
      id: json['id'] as int,
      type: json['type'] as String? ?? 'text',
      authorName: json['author_name'] as String? ?? 'Unknown',
      body: json['body'] as String? ?? '',
      createdAt: DateTime.tryParse(json['created_at'] as String? ?? '') ?? DateTime.now(),
      likeCount: json['like_count'] as int? ?? 0,
      commentCount: json['comment_count'] as int? ?? 0,
      visibility: json['visibility'] as String? ?? 'community',
    );
  }

  final int id;
  final String type;
  final String authorName;
  final String body;
  final DateTime createdAt;
  final int likeCount;
  final int commentCount;
  final String visibility;
}
