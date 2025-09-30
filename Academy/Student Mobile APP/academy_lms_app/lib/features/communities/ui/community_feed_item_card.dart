import 'package:academy_lms_app/constants.dart' as constants;
import 'package:academy_lms_app/features/communities/models/community_feed_item.dart';
import 'package:academy_lms_app/features/communities/ui/community_feed_reaction_bar.dart';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';

class CommunityFeedItemCard extends StatelessWidget {
  const CommunityFeedItemCard({
    super.key,
    required this.item,
    required this.onToggleReaction,
    required this.onShowComments,
    required this.onShowReactions,
  });

  final CommunityFeedItem item;
  final ValueChanged<String?> onToggleReaction;
  final VoidCallback onShowComments;
  final VoidCallback onShowReactions;

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
              ],
            ),
            const SizedBox(height: 12),
            Text(
              item.body.isEmpty ? 'Attachment-only post' : item.body,
              style: theme.textTheme.bodyMedium,
            ),
            const SizedBox(height: 16),
            CommunityFeedReactionBar(
              reactionCounts: reactionCounts,
              activeReaction: item.isLiked ? 'like' : null,
              onReactionSelected: onToggleReaction,
            ),
            const SizedBox(height: 12),
            Row(
              children: [
                TextButton.icon(
                  onPressed: onShowComments,
                  icon: const Icon(Icons.comment_outlined),
                  label: Text('${item.commentCount} comments'),
                ),
                const SizedBox(width: 8),
                TextButton(
                  onPressed: onShowReactions,
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
