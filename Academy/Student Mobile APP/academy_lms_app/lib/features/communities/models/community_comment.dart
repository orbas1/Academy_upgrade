import 'package:meta/meta.dart';

@immutable
class CommunityComment {
  const CommunityComment({
    required this.id,
    required this.postId,
    required this.authorName,
    required this.body,
    required this.bodyMarkdown,
    required this.createdAt,
    required this.likeCount,
    required this.isLiked,
    required this.parentId,
  });

  factory CommunityComment.fromJson(Map<String, dynamic> json) {
    return CommunityComment(
      id: json['id'] as int,
      postId: json['post_id'] as int,
      authorName: json['author_name'] as String? ?? 'Unknown',
      body: json['body'] as String? ?? '',
      bodyMarkdown: json['body_md'] as String? ?? json['body'] as String? ?? '',
      createdAt: DateTime.tryParse(json['created_at'] as String? ?? '') ?? DateTime.now(),
      likeCount: json['like_count'] as int? ?? 0,
      isLiked: json['liked'] as bool? ?? false,
      parentId: json['parent_id'] as int?,
    );
  }

  final int id;
  final int postId;
  final String authorName;
  final String body;
  final String bodyMarkdown;
  final DateTime createdAt;
  final int likeCount;
  final bool isLiked;
  final int? parentId;

  bool get isReply => parentId != null;
}
