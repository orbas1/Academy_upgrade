import 'package:academy_lms_app/constants.dart' as constants;
import 'package:academy_lms_app/features/communities/models/community_summary.dart';
import 'package:academy_lms_app/features/communities/presentation/community_detail_screen.dart';
import 'package:academy_lms_app/features/communities/presentation/community_onboarding_flow.dart';
import 'package:academy_lms_app/features/communities/state/community_notifier.dart';
import 'package:academy_lms_app/features/communities/state/community_onboarding_notifier.dart';
import 'package:academy_lms_app/features/communities/ui/community_card.dart';
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
  bool _onboardingCheckPending = false;
  bool _onboardingShowing = false;

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (!_bootstrapped) {
      _bootstrapped = true;
      final notifier = context.read<CommunityNotifier>();
      notifier.refreshCommunities();
      _scheduleOnboardingCheck();
    }
  }

  @override
  Widget build(BuildContext context) {
    final isAuthenticated = context.watch<Auth>().token.isNotEmpty;

    if (isAuthenticated) {
      _scheduleOnboardingCheck();
    } else {
      _onboardingCheckPending = false;
      _onboardingShowing = false;
    }

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
                child: CommunityCard(
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

  void _scheduleOnboardingCheck() {
    if (_onboardingCheckPending || _onboardingShowing) {
      return;
    }
    _onboardingCheckPending = true;
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _onboardingCheckPending = false;
      if (!mounted) {
        return;
      }
      _maybePresentOnboarding();
    });
  }

  Future<void> _maybePresentOnboarding() async {
    if (_onboardingShowing) {
      return;
    }

    final auth = context.read<Auth>();
    if (auth.token.isEmpty) {
      return;
    }

    final onboardingNotifier = context.read<CommunityOnboardingNotifier>();
    final communityNotifier = context.read<CommunityNotifier>();

    await onboardingNotifier.initialize();
    if (!mounted || !onboardingNotifier.shouldPrompt) {
      return;
    }

    _onboardingShowing = true;
    await onboardingNotifier.bootstrap(communityNotifier);
    if (!mounted || !onboardingNotifier.shouldPrompt) {
      _onboardingShowing = false;
      return;
    }

    final completed = await showCommunityOnboardingFlow(
      context,
      communityNotifier: communityNotifier,
      onboardingNotifier: onboardingNotifier,
    );

    _onboardingShowing = false;

    if (!mounted || completed != true) {
      return;
    }

    await communityNotifier.refreshCommunities(
      filter: communityNotifier.currentCommunitiesFilter,
    );

    if (!mounted) {
      return;
    }

    ScaffoldMessenger.of(context).showSnackBar(
      const SnackBar(
        content: Text('Welcome aboard! Your community feed was personalized.'),
        behavior: SnackBarBehavior.floating,
      ),
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
