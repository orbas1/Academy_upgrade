import 'package:academy_lms_app/config/design_tokens.dart';
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
      elevation: theme.cardTheme.elevation ?? 1,
      margin: EdgeInsets.zero,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(DesignRadii.xl),
      ),
      child: Padding(
        padding: const EdgeInsets.all(DesignSpacing.xl),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                CircleAvatar(
                  backgroundColor: DesignColors.primary100,
                  child: Text(item.authorName.isNotEmpty ? item.authorName[0].toUpperCase() : '?'),
                ),
                const SizedBox(width: DesignSpacing.md),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(item.authorName, style: theme.textTheme.titleSmall),
                      Text(
                        formatter.format(item.createdAt),
                        style: theme.textTheme.bodySmall?.copyWith(
                          color: DesignColors.textMuted,
                        ),
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
            const SizedBox(height: DesignSpacing.md),
            Text(
              item.body.isEmpty ? 'Attachment-only post' : item.body,
              style: theme.textTheme.bodyMedium,
            ),
            const SizedBox(height: DesignSpacing.lg),
            CommunityFeedReactionBar(
              reactionCounts: reactionCounts,
              activeReaction: item.isLiked ? 'like' : null,
              onReactionSelected: item.isPending ? null : onToggleReaction,
            ),
            const SizedBox(height: DesignSpacing.md),
            Row(
              children: [
                TextButton.icon(
                  onPressed: item.isPending ? null : onShowComments,
                  icon: const Icon(Icons.comment_outlined),
                  label: Text('${item.commentCount} comments'),
                ),
                const SizedBox(width: DesignSpacing.sm),
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
    Color color;
    switch (visibility) {
      case 'public':
        color = DesignColors.primary600;
        break;
      case 'paid':
        color = DesignColors.paywall500;
        break;
      default:
        color = DesignColors.textMuted;
    }
    return Container(
      padding: const EdgeInsets.symmetric(
        horizontal: DesignSpacing.sm,
        vertical: DesignSpacing.xs,
      ),
      decoration: BoxDecoration(
        color: color.withOpacity(0.12),
        borderRadius: DesignRadii.pillRadius,
      ),
      child: Text(
        visibility.toUpperCase(),
        style: Theme.of(context).textTheme.labelSmall?.copyWith(
              fontWeight: FontWeight.w600,
              letterSpacing: 0.6,
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
        padding: const EdgeInsets.symmetric(
          horizontal: DesignSpacing.lg,
          vertical: DesignSpacing.sm,
        ),
        decoration: BoxDecoration(
          color: theme.colorScheme.primary.withOpacity(0.08),
          borderRadius: BorderRadius.circular(DesignRadii.lg),
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
            const SizedBox(width: DesignSpacing.md),
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
        padding: const EdgeInsets.symmetric(
          horizontal: DesignSpacing.lg,
          vertical: DesignSpacing.sm,
        ),
        decoration: BoxDecoration(
          color: theme.colorScheme.error.withOpacity(0.08),
          borderRadius: BorderRadius.circular(DesignRadii.lg),
        ),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Icon(Icons.error_outline, color: theme.colorScheme.error),
            const SizedBox(width: DesignSpacing.md),
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
