import 'package:academy_lms_app/config/design_tokens.dart';
import 'package:academy_lms_app/features/communities/models/community_summary.dart';
import 'package:flutter/material.dart';

class CommunityCard extends StatelessWidget {
  const CommunityCard({
    super.key,
    required this.summary,
    required this.isBusy,
    required this.onJoin,
    required this.onLeave,
    required this.onOpen,
  });

  final CommunitySummary summary;
  final bool isBusy;
  final VoidCallback onJoin;
  final VoidCallback onLeave;
  final VoidCallback onOpen;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final isMember = summary.isMember;
    Color visibilityColor;
    switch (summary.visibility) {
      case 'public':
        visibilityColor = DesignColors.primary600;
        break;
      case 'private':
        visibilityColor = DesignColors.textMuted;
        break;
      case 'paid':
        visibilityColor = DesignColors.paywall500;
        break;
      default:
        visibilityColor = DesignColors.primary500;
    }

    return Card(
      elevation: theme.cardTheme.elevation ?? 2,
      clipBehavior: Clip.antiAlias,
      shape: RoundedRectangleBorder(
        borderRadius: BorderRadius.circular(DesignRadii.xl),
      ),
      child: InkWell(
        onTap: onOpen,
        child: Padding(
          padding: const EdgeInsets.all(DesignSpacing.xl),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisSize: MainAxisSize.min,
            children: [
              Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Text(summary.name, style: theme.textTheme.titleMedium),
                        const SizedBox(height: DesignSpacing.xs),
                        Text(
                          summary.tagline.isEmpty
                              ? 'Stay tuned for updates.'
                              : summary.tagline,
                          style: theme.textTheme.bodyMedium,
                        ),
                      ],
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(
                      horizontal: DesignSpacing.sm,
                      vertical: DesignSpacing.xs,
                    ),
                    decoration: BoxDecoration(
                      color: visibilityColor.withOpacity(0.12),
                      borderRadius: DesignRadii.pillRadius,
                    ),
                    child: Text(
                      summary.visibility.toUpperCase(),
                      style: theme.textTheme.labelSmall?.copyWith(
                        letterSpacing: 0.6,
                        color: visibilityColor,
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: DesignSpacing.lg),
              Row(
                children: [
                  _InfoPill(
                    icon: Icons.people_alt_outlined,
                    label: '${summary.memberCount} members',
                  ),
                  const SizedBox(width: DesignSpacing.md),
                  _InfoPill(
                    icon: Icons.podcasts_outlined,
                    label: '${summary.onlineCount} online',
                  ),
                  const SizedBox(width: DesignSpacing.md),
                  _InfoPill(
                    icon: Icons.leaderboard_outlined,
                    label: summary.postsPerDay > 0
                        ? '${summary.postsPerDay} posts/day'
                        : summary.isMember
                            ? 'Member'
                            : 'Discover',
                  ),
                  if (summary.paywallEnabled) ...[
                    const SizedBox(width: DesignSpacing.md),
                    _InfoPill(
                      icon: Icons.lock_outline,
                      label: 'Premium access',
                    ),
                  ],
                  const Spacer(),
                  _CommunityActionButton(
                    isMember: isMember,
                    isBusy: isBusy,
                    onJoin: onJoin,
                    onLeave: onLeave,
                  ),
                ],
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _InfoPill extends StatelessWidget {
  const _InfoPill({required this.icon, required this.label});

  final IconData icon;
  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(
        horizontal: DesignSpacing.sm,
        vertical: DesignSpacing.xs,
      ),
      decoration: BoxDecoration(
        color: DesignColors.textMuted.withOpacity(0.12),
        borderRadius: DesignRadii.pillRadius,
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 16, color: DesignColors.textMuted),
          const SizedBox(width: DesignSpacing.xs),
          Text(
            label,
            style: Theme.of(context).textTheme.bodySmall?.copyWith(
                  color: DesignColors.textMuted,
                ),
          ),
        ],
      ),
    );
  }
}

class _CommunityActionButton extends StatelessWidget {
  const _CommunityActionButton({
    required this.isMember,
    required this.isBusy,
    required this.onJoin,
    required this.onLeave,
  });

  final bool isMember;
  final bool isBusy;
  final VoidCallback onJoin;
  final VoidCallback onLeave;

  @override
  Widget build(BuildContext context) {
    if (isMember) {
      return OutlinedButton(
        onPressed: isBusy ? null : onLeave,
        style: OutlinedButton.styleFrom(
          shape: RoundedRectangleBorder(
            borderRadius: BorderRadius.circular(DesignRadii.pill),
          ),
        ),
        child: const Text('Leave'),
      );
    }

    return ElevatedButton(
      onPressed: isBusy ? null : onJoin,
      style: ElevatedButton.styleFrom(
        padding: const EdgeInsets.symmetric(
          horizontal: DesignSpacing.lg,
          vertical: DesignSpacing.sm,
        ),
        shape: RoundedRectangleBorder(
          borderRadius: BorderRadius.circular(DesignRadii.pill),
        ),
      ),
      child: const Text('Join'),
    );
  }
}
