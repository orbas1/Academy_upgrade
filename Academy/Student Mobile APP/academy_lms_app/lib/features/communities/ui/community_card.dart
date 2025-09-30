import 'package:academy_lms_app/constants.dart' as constants;
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
    final visibilityColor = summary.visibility == 'public'
        ? constants.kDefaultColor
        : summary.visibility == 'private'
            ? constants.kGreyLightColor
            : const Color(0xFF6366F1);

    return Card(
      elevation: 2,
      clipBehavior: Clip.antiAlias,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(20)),
      child: InkWell(
        onTap: onOpen,
        child: Padding(
          padding: const EdgeInsets.all(20),
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
                        Text(
                          summary.name,
                          style: theme.textTheme.titleMedium?.copyWith(
                            fontWeight: FontWeight.w700,
                            color: constants.kBlackColor,
                          ),
                        ),
                        const SizedBox(height: 6),
                        Text(
                          summary.tagline.isEmpty
                              ? 'Stay tuned for updates.'
                              : summary.tagline,
                          style: theme.textTheme.bodyMedium?.copyWith(
                            color: constants.kGreyLightColor,
                          ),
                        ),
                      ],
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                    decoration: BoxDecoration(
                      color: visibilityColor.withOpacity(0.12),
                      borderRadius: BorderRadius.circular(100),
                    ),
                    child: Text(
                      summary.visibility.toUpperCase(),
                      style: theme.textTheme.labelSmall?.copyWith(
                        letterSpacing: 0.8,
                        fontWeight: FontWeight.w600,
                        color: visibilityColor,
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 16),
              Row(
                children: [
                  _InfoPill(
                    icon: Icons.people_alt_outlined,
                    label: '${summary.memberCount} members',
                  ),
                  const SizedBox(width: 12),
                  _InfoPill(
                    icon: Icons.podcasts_outlined,
                    label: '${summary.onlineCount} online',
                  ),
                  const SizedBox(width: 12),
                  _InfoPill(
                    icon: Icons.leaderboard_outlined,
                    label: summary.postsPerDay > 0
                        ? '${summary.postsPerDay} posts/day'
                        : summary.isMember
                            ? 'Member'
                            : 'Discover',
                  ),
                  if (summary.paywallEnabled) ...[
                    const SizedBox(width: 12),
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
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: constants.kGreyLightColor.withOpacity(0.12),
        borderRadius: BorderRadius.circular(100),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 16, color: constants.kGreyLightColor),
          const SizedBox(width: 6),
          Text(
            label,
            style: Theme.of(context).textTheme.bodySmall?.copyWith(
                  color: constants.kGreyLightColor,
                  fontWeight: FontWeight.w500,
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
          shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(100)),
          foregroundColor: constants.kDefaultColor,
        ),
        child: const Text('Leave'),
      );
    }

    return ElevatedButton(
      onPressed: isBusy ? null : onJoin,
      style: ElevatedButton.styleFrom(
        backgroundColor: constants.kDefaultColor,
        foregroundColor: constants.kWhiteColor,
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
        shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(100)),
      ),
      child: const Text('Join'),
    );
  }
}
