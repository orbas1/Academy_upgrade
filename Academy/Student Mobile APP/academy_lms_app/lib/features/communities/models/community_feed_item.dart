import 'package:meta/meta.dart';

@immutable
class CommunityFeedItem {
  const CommunityFeedItem({
    required this.id,
    required this.communityId,
    required this.type,
    required this.authorName,
    required this.authorId,
    required this.body,
    required this.bodyMarkdown,
    required this.bodyHtml,
    required this.createdAt,
    required this.likeCount,
    required this.commentCount,
    required this.visibility,
    required this.isLiked,
    required this.paywallTierId,
    required this.attachments,
    required this.isArchived,
    this.archivedAt,
    this.isPending = false,
    this.isFailed = false,
    this.clientReference,
    this.failureReason,
    this.shareUrl,
  });

  factory CommunityFeedItem.fromJson(Map<String, dynamic> json) {
    return CommunityFeedItem(
      id: json['id'] as int,
      communityId: json['community_id'] as int? ?? json['communityId'] as int? ?? 0,
      type: json['type'] as String? ?? 'text',
      authorName: json['author_name'] as String? ?? 'Unknown',
      authorId: json['author_id'] as int? ?? 0,
      body: json['body'] as String? ?? '',
      bodyMarkdown: json['body_md'] as String? ?? json['body'] as String? ?? '',
      bodyHtml: json['body_html'] as String? ?? '',
      createdAt: DateTime.tryParse(json['created_at'] as String? ?? '') ?? DateTime.now(),
      likeCount: json['like_count'] as int? ?? 0,
      commentCount: json['comment_count'] as int? ?? 0,
      visibility: json['visibility'] as String? ?? 'community',
      isLiked: json['liked'] as bool? ?? false,
      paywallTierId: json['paywall_tier_id'] as int?,
      attachments: (json['attachments'] as List<dynamic>? ?? <dynamic>[])
          .map((dynamic item) =>
              Map<String, dynamic>.from(item as Map<String, dynamic>))
          .toList(),
      isArchived: json['is_archived'] as bool? ?? false,
      archivedAt: json['archived_at'] != null
          ? DateTime.tryParse(json['archived_at'] as String)
          : null,
      isPending: json['is_pending'] as bool? ?? false,
      isFailed: json['is_failed'] as bool? ?? false,
      clientReference: json['client_reference'] as String?,
      failureReason: json['failure_reason'] as String?,
      shareUrl: json['share_url'] as String? ?? json['permalink'] as String?,
    );
  }

  final int id;
  final int communityId;
  final String type;
  final String authorName;
  final int authorId;
  final String body;
  final String bodyMarkdown;
  final String bodyHtml;
  final DateTime createdAt;
  final int likeCount;
  final int commentCount;
  final String visibility;
  final bool isLiked;
  final int? paywallTierId;
  final List<Map<String, dynamic>> attachments;
  final bool isArchived;
  final DateTime? archivedAt;
  final bool isPending;
  final bool isFailed;
  final String? clientReference;
  final String? failureReason;
  final String? shareUrl;

  CommunityFeedItem copyWith({
    int? id,
    int? communityId,
    String? type,
    String? authorName,
    int? authorId,
    String? body,
    String? bodyMarkdown,
    String? bodyHtml,
    DateTime? createdAt,
    int? likeCount,
    int? commentCount,
    String? visibility,
    bool? isLiked,
    int? paywallTierId,
    List<Map<String, dynamic>>? attachments,
    bool? isArchived,
    DateTime? archivedAt,
    bool? isPending,
    bool? isFailed,
    String? clientReference,
    String? failureReason,
    String? shareUrl,
  }) {
    return CommunityFeedItem(
      id: id ?? this.id,
      communityId: communityId ?? this.communityId,
      type: type ?? this.type,
      authorName: authorName ?? this.authorName,
      authorId: authorId ?? this.authorId,
      body: body ?? this.body,
      bodyMarkdown: bodyMarkdown ?? this.bodyMarkdown,
      bodyHtml: bodyHtml ?? this.bodyHtml,
      createdAt: createdAt ?? this.createdAt,
      likeCount: likeCount ?? this.likeCount,
      commentCount: commentCount ?? this.commentCount,
      visibility: visibility ?? this.visibility,
      isLiked: isLiked ?? this.isLiked,
      paywallTierId: paywallTierId ?? this.paywallTierId,
      attachments: attachments ?? this.attachments,
      isArchived: isArchived ?? this.isArchived,
      archivedAt: archivedAt ?? this.archivedAt,
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
      'community_id': communityId,
      'type': type,
      'author_name': authorName,
      'author_id': authorId,
      'body': body,
      'body_md': bodyMarkdown,
      'body_html': bodyHtml,
      'created_at': createdAt.toIso8601String(),
      'like_count': likeCount,
      'comment_count': commentCount,
      'visibility': visibility,
      'liked': isLiked,
      'paywall_tier_id': paywallTierId,
      'attachments': attachments,
      'is_archived': isArchived,
      'archived_at': archivedAt?.toIso8601String(),
      'is_pending': isPending,
      'is_failed': isFailed,
      'client_reference': clientReference,
      'failure_reason': failureReason,
      'share_url': shareUrl,
    };
  }
}
