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
    this.isPending = false,
    this.isFailed = false,
    this.clientReference,
    this.failureReason,
    this.shareUrl,
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
      isPending: json['is_pending'] as bool? ?? false,
      isFailed: json['is_failed'] as bool? ?? false,
      clientReference: json['client_reference'] as String?,
      failureReason: json['failure_reason'] as String?,
      shareUrl: json['share_url'] as String? ?? json['permalink'] as String?,
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
  final bool isPending;
  final bool isFailed;
  final String? clientReference;
  final String? failureReason;
  final String? shareUrl;

  CommunityFeedItem copyWith({
    int? id,
    String? type,
    String? authorName,
    String? body,
    String? bodyMarkdown,
    DateTime? createdAt,
    int? likeCount,
    int? commentCount,
    String? visibility,
    bool? isLiked,
    int? paywallTierId,
    bool? isPending,
    bool? isFailed,
    String? clientReference,
    String? failureReason,
    String? shareUrl,
  }) {
    return CommunityFeedItem(
      id: id ?? this.id,
      type: type ?? this.type,
      authorName: authorName ?? this.authorName,
      body: body ?? this.body,
      bodyMarkdown: bodyMarkdown ?? this.bodyMarkdown,
      createdAt: createdAt ?? this.createdAt,
      likeCount: likeCount ?? this.likeCount,
      commentCount: commentCount ?? this.commentCount,
      visibility: visibility ?? this.visibility,
      isLiked: isLiked ?? this.isLiked,
      paywallTierId: paywallTierId ?? this.paywallTierId,
      isPending: isPending ?? this.isPending,
      isFailed: isFailed ?? this.isFailed,
      clientReference: clientReference ?? this.clientReference,
      failureReason: failureReason ?? this.failureReason,
      shareUrl: shareUrl ?? this.shareUrl,
    );
  }

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
      'is_pending': isPending,
      'is_failed': isFailed,
      'client_reference': clientReference,
      'failure_reason': failureReason,
      'share_url': shareUrl,
    };
  }
}
