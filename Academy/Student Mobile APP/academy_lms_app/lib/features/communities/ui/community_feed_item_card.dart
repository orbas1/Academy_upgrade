import 'package:academy_lms_app/constants.dart' as constants;
import 'package:academy_lms_app/features/communities/models/community_feed_item.dart';
import 'package:academy_lms_app/features/communities/ui/community_feed_reaction_bar.dart';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

enum CommunityFeedItemAction { report, hide }

class CommunityFeedItemCard extends StatelessWidget {
  const CommunityFeedItemCard({
    super.key,
    required this.item,
    required this.onToggleReaction,
    required this.onShowComments,
    required this.onShowReactions,
    required this.onAction,
    this.canModerate = false,
  });

  final CommunityFeedItem item;
  final ValueChanged<String?>? onToggleReaction;
  final VoidCallback? onShowComments;
  final VoidCallback? onShowReactions;
  final ValueChanged<CommunityFeedItemAction>? onAction;
  final bool canModerate;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final formatter = DateFormat.yMMMd().add_jm();
    final reactionCounts = <String, int>{
      'like': item.likeCount,
      'celebrate': 0,
      'insightful': 0,
      'support': 0,
    };

    return Card(
      elevation: 1,
      margin: EdgeInsets.zero,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      child: Padding(
        padding: const EdgeInsets.all(20),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                CircleAvatar(
                  backgroundColor: constants.kDefaultColor.withOpacity(0.12),
                  child: Text(item.authorName.isNotEmpty ? item.authorName[0].toUpperCase() : '?'),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(item.authorName, style: theme.textTheme.titleSmall),
                      Text(
                        formatter.format(item.createdAt),
                        style: theme.textTheme.bodySmall?.copyWith(color: constants.kGreyLightColor),
                      ),
                    ],
                  ),
                ),
                _VisibilityBadge(visibility: item.visibility),
                PopupMenuButton<CommunityFeedItemAction>(
                  icon: const Icon(Icons.more_vert),
                  tooltip: 'Post actions',
                  enabled: !item.isPending && onAction != null,
                  onSelected: (value) => onAction?.call(value),
                  itemBuilder: (context) {
                    return <PopupMenuEntry<CommunityFeedItemAction>>[
                      PopupMenuItem<CommunityFeedItemAction>(
                        value: CommunityFeedItemAction.report,
                        child: Row(
                          children: const [
                            Icon(Icons.flag_outlined, size: 18),
                            SizedBox(width: 8),
                            Text('Report'),
                          ],
                        ),
                      ),
                      if (canModerate)
                        PopupMenuItem<CommunityFeedItemAction>(
                          value: CommunityFeedItemAction.hide,
                          child: Row(
                            children: const [
                              Icon(Icons.hide_source_outlined, size: 18),
                              SizedBox(width: 8),
                              Text('Hide from feed'),
                            ],
                          ),
                        ),
                    ];
                  },
                ),
              ],
            ),
            if (item.isPending || item.isFailed) ...[
              const SizedBox(height: 12),
              _SyncStatusBanner(item: item),
            ],
            const SizedBox(height: 12),
            Text(
              item.body.isEmpty ? 'Attachment-only post' : item.body,
              style: theme.textTheme.bodyMedium,
            ),
            const SizedBox(height: 16),
            CommunityFeedReactionBar(
              reactionCounts: reactionCounts,
              activeReaction: item.isLiked ? 'like' : null,
              onReactionSelected: item.isPending ? null : onToggleReaction,
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                TextButton.icon(
                  onPressed: item.isPending ? null : onShowComments,
                  icon: const Icon(Icons.comment_outlined),
                  label: Text('${item.commentCount} comments'),
                ),
                const SizedBox(width: 8),
                TextButton(
                  onPressed: item.isPending ? null : onShowReactions,
                  child: Text('${item.likeCount} total reactions'),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _VisibilityBadge extends StatelessWidget {
  const _VisibilityBadge({required this.visibility});

  final String visibility;

  @override
  Widget build(BuildContext context) {
    final color = visibility == 'public'
        ? constants.kDefaultColor
        : visibility == 'paid'
            ? const Color(0xFFE5A663)
            : constants.kGreyLightColor;
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: color.withOpacity(0.12),
        borderRadius: BorderRadius.circular(100),
      ),
      child: Text(
        visibility.toUpperCase(),
        style: Theme.of(context).textTheme.labelSmall?.copyWith(
              fontWeight: FontWeight.w600,
              letterSpacing: 0.8,
              color: color,
            ),
      ),
    );
  }
}

class _SyncStatusBanner extends StatelessWidget {
  const _SyncStatusBanner({required this.item});

  final CommunityFeedItem item;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    if (item.isPending) {
      return Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
        decoration: BoxDecoration(
          color: theme.colorScheme.primary.withOpacity(0.08),
          borderRadius: BorderRadius.circular(12),
        ),
        child: Row(
          children: [
            SizedBox(
              height: 16,
              width: 16,
              child: CircularProgressIndicator(
                strokeWidth: 2,
                valueColor: AlwaysStoppedAnimation<Color>(theme.colorScheme.primary),
              ),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Text(
                'Waiting for connection. This post will sync automatically once you are back online.',
                style: theme.textTheme.bodySmall?.copyWith(
                  color: theme.colorScheme.primary,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ),
          ],
        ),
      );
    }

    if (item.isFailed) {
      return Container(
        padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
        decoration: BoxDecoration(
          color: theme.colorScheme.error.withOpacity(0.08),
          borderRadius: BorderRadius.circular(12),
        ),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Icon(Icons.error_outline, color: theme.colorScheme.error),
            const SizedBox(width: 12),
            Expanded(
              child: Text(
                item.failureReason ?? 'We could not sync this post. Please retry when you have a stable connection.',
                style: theme.textTheme.bodySmall?.copyWith(
                  color: theme.colorScheme.error,
                  fontWeight: FontWeight.w600,
                ),
              ),
            ),
          ],
        ),
      );
    }

    return const SizedBox.shrink();
  }
}
