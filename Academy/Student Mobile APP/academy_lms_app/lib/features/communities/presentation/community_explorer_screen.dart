import 'package:academy_lms_app/constants.dart' as constants;
import 'package:academy_lms_app/features/communities/models/community_summary.dart';
import 'package:academy_lms_app/features/communities/presentation/community_detail_screen.dart';
import 'package:academy_lms_app/features/communities/state/community_notifier.dart';
import 'package:academy_lms_app/providers/auth.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

class CommunityExplorerScreen extends StatefulWidget {
  const CommunityExplorerScreen({super.key});

  @override
  State<CommunityExplorerScreen> createState() => _CommunityExplorerScreenState();
}

class _CommunityExplorerScreenState extends State<CommunityExplorerScreen> {
  String? _lastError;
  bool _bootstrapped = false;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (!_bootstrapped) {
      _bootstrapped = true;
      final notifier = context.read<CommunityNotifier>();
      notifier.refreshCommunities();
    }
  }

  @override
  Widget build(BuildContext context) {
    final isAuthenticated = context.watch<Auth>().token.isNotEmpty;

    if (!isAuthenticated) {
      return const _CommunityAuthPrompt();
    }

    return Consumer<CommunityNotifier>(
      builder: (context, notifier, child) {
        final communities = notifier.communities;

        WidgetsBinding.instance.addPostFrameCallback((_) {
          final currentError = notifier.error;
          if (currentError != null && currentError != _lastError && mounted) {
            _lastError = currentError;
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(
                content: Text(currentError),
                behavior: SnackBarBehavior.floating,
              ),
            );
          }
        });

        if (notifier.isLoading && communities.isEmpty) {
          return const Center(child: CircularProgressIndicator());
        }

        return RefreshIndicator(
          onRefresh: () => notifier.refreshCommunities(),
          child: ListView.builder(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            itemCount: communities.isEmpty ? 1 : communities.length,
            itemBuilder: (context, index) {
              if (communities.isEmpty) {
                return const _CommunityEmptyState();
              }

              final summary = communities[index];
              return Padding(
                padding: const EdgeInsets.symmetric(vertical: 8),
                child: _CommunityCard(
                  summary: summary,
                  isBusy: notifier.isMutatingMembership,
                  onJoin: () => notifier.joinCommunity(summary.id),
                  onLeave: () => notifier.leaveCommunity(summary.id),
                  onOpen: () => _openCommunityDetail(context, summary),
                ),
              );
            },
          ),
        );
      },
    );
  }

  void _openCommunityDetail(BuildContext context, CommunitySummary summary) {
    Navigator.of(context).push(
      MaterialPageRoute<Widget>(
        builder: (context) => CommunityDetailScreen(summary: summary),
      ),
    );
  }
}

class _CommunityCard extends StatelessWidget {
  const _CommunityCard({
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

    return Card(
      elevation: 2,
      clipBehavior: Clip.antiAlias,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: InkWell(
        onTap: onOpen,
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Expanded(
                    child: Text(
                      summary.name,
                      style: theme.textTheme.titleMedium?.copyWith(
                        fontWeight: FontWeight.w600,
                        color: constants.kBlackColor,
                      ),
                    ),
                  ),
                  Container(
                    padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                    decoration: BoxDecoration(
                      color: summary.visibility == 'public'
                          ? constants.kDefaultColor.withOpacity(0.12)
                          : constants.kGreyLightColor.withOpacity(0.15),
                      borderRadius: BorderRadius.circular(100),
                    ),
                    child: Text(
                      summary.visibility.toUpperCase(),
                      style: theme.textTheme.labelSmall?.copyWith(
                        letterSpacing: 0.8,
                        fontWeight: FontWeight.w600,
                        color: summary.visibility == 'public'
                            ? constants.kDefaultColor
                            : constants.kGreyLightColor,
                      ),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 8),
              Text(
                summary.tagline.isEmpty ? 'Stay tuned for updates.' : summary.tagline,
                style: theme.textTheme.bodyMedium?.copyWith(color: constants.kGreyLightColor),
              ),
              const SizedBox(height: 12),
              Row(
                children: [
                  Icon(Icons.people_alt_outlined, size: 18, color: constants.kGreyLightColor),
                  const SizedBox(width: 6),
                  Text(
                    '${summary.memberCount} members',
                    style: theme.textTheme.bodySmall?.copyWith(color: constants.kGreyLightColor),
                  ),
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

class _CommunityEmptyState extends StatelessWidget {
  const _CommunityEmptyState();

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Center(
      child: Padding(
        padding: const EdgeInsets.symmetric(vertical: 48),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.forum_outlined, size: 48, color: constants.kGreyLightColor.withOpacity(0.8)),
            const SizedBox(height: 12),
            Text(
              'No communities yet',
              style: theme.textTheme.titleMedium,
            ),
            const SizedBox(height: 4),
            Text(
              'Check back soon or pull to refresh.',
              style: theme.textTheme.bodyMedium?.copyWith(color: constants.kGreyLightColor),
            ),
          ],
        ),
      ),
    );
  }
}

class _CommunityAuthPrompt extends StatelessWidget {
  const _CommunityAuthPrompt();

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.lock_outline, size: 48, color: constants.kGreyLightColor.withOpacity(0.8)),
            const SizedBox(height: 12),
            Text(
              'Sign in to explore communities',
              style: theme.textTheme.titleMedium,
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 8),
            Text(
              'Join vibrant groups, unlock exclusive posts, and connect with peers.',
              style: theme.textTheme.bodyMedium?.copyWith(color: constants.kGreyLightColor),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 24),
            ElevatedButton(
              onPressed: () {
                Navigator.of(context).pushNamed('/login');
              },
              style: ElevatedButton.styleFrom(
                backgroundColor: constants.kDefaultColor,
                foregroundColor: constants.kWhiteColor,
              ),
              child: const Text('Sign In'),
            ),
          ],
        ),
      ),
    );
  }
}
