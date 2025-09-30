import 'package:academy_lms_app/constants.dart' as constants;
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../models/community_summary.dart';
import '../state/community_notifier.dart';
import '../state/community_onboarding_notifier.dart';

Future<bool?> showCommunityOnboardingFlow(
  BuildContext context, {
  required CommunityNotifier communityNotifier,
  required CommunityOnboardingNotifier onboardingNotifier,
}) {
  return showModalBottomSheet<bool>(
    context: context,
    isScrollControlled: true,
    useSafeArea: true,
    isDismissible: false,
    enableDrag: false,
    builder: (context) {
      return ChangeNotifierProvider<CommunityOnboardingNotifier>.value(
        value: onboardingNotifier,
        child: _CommunityOnboardingFlow(communityNotifier: communityNotifier),
      );
    },
  );
}

class _CommunityOnboardingFlow extends StatefulWidget {
  const _CommunityOnboardingFlow({required this.communityNotifier});

  final CommunityNotifier communityNotifier;

  @override
  State<_CommunityOnboardingFlow> createState() => _CommunityOnboardingFlowState();
}

class _CommunityOnboardingFlowState extends State<_CommunityOnboardingFlow> {
  final PageController _pageController = PageController();
  int _activePage = 0;

  @override
  void initState() {
    super.initState();
    final onboarding = context.read<CommunityOnboardingNotifier>();
    if (!onboarding.isLoadingRecommendations && onboarding.recommendations.isEmpty) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        onboarding.bootstrap(widget.communityNotifier);
      });
    }
  }

  @override
  void dispose() {
    _pageController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Consumer<CommunityOnboardingNotifier>(
      builder: (context, onboarding, child) {
        final totalSteps = 3;
        final progress = (_activePage + 1) / totalSteps;

        return SizedBox(
          height: MediaQuery.of(context).size.height * 0.92,
          child: Column(
            children: [
              Padding(
                padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 20),
                child: Row(
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'Set up your community experience',
                            style: theme.textTheme.titleLarge?.copyWith(fontWeight: FontWeight.w700),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            'Choose communities to join and personalize your feed.',
                            style: theme.textTheme.bodyMedium?.copyWith(color: constants.kGreyLightColor),
                          ),
                        ],
                      ),
                    ),
                    TextButton(
                      onPressed: onboarding.isCompleting
                          ? null
                          : () => _completeOnboarding(context, onboarding, skip: true),
                      child: const Text('Skip'),
                    ),
                  ],
                ),
              ),
              LinearProgressIndicator(
                value: progress,
                minHeight: 4,
                backgroundColor: constants.kGreyLightColor.withOpacity(0.2),
                color: constants.kDefaultColor,
              ),
              const SizedBox(height: 12),
              Expanded(
                child: PageView(
                  controller: _pageController,
                  physics: const NeverScrollableScrollPhysics(),
                  children: [
                    _WelcomeStep(onContinue: _next),
                    _CommunitySelectionStep(onRetry: () {
                      onboarding.bootstrap(widget.communityNotifier);
                    }),
                    _SummaryStep(onEditSelection: _previous),
                  ],
                ),
              ),
              Padding(
                padding: const EdgeInsets.fromLTRB(24, 12, 24, 24),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.stretch,
                  children: [
                    if (onboarding.error != null)
                      Padding(
                        padding: const EdgeInsets.only(bottom: 12),
                        child: Text(
                          onboarding.error!,
                          style: theme.textTheme.bodyMedium?.copyWith(color: Colors.red.shade700),
                        ),
                      ),
                    FilledButton(
                      onPressed: onboarding.isCompleting
                          ? null
                          : () {
                              if (_activePage < totalSteps - 1) {
                                _next();
                                return;
                              }
                              _completeOnboarding(context, onboarding);
                            },
                      style: FilledButton.styleFrom(
                        padding: const EdgeInsets.symmetric(vertical: 16),
                        backgroundColor: constants.kDefaultColor,
                      ),
                      child: Text(
                        _activePage == totalSteps - 1 ? 'Finish onboarding' : 'Continue',
                        style: theme.textTheme.titleMedium?.copyWith(color: Colors.white),
                      ),
                    ),
                    if (_activePage == 1)
                      TextButton(
                        onPressed: onboarding.isCompleting
                            ? null
                            : () => _completeOnboarding(context, onboarding, skip: true),
                        child: const Text('Skip for now'),
                      ),
                  ],
                ),
              ),
            ],
          ),
        );
      },
    );
  }

  void _next() {
    setState(() {
      _activePage = (_activePage + 1).clamp(0, 2);
    });
    _pageController.animateToPage(
      _activePage,
      duration: const Duration(milliseconds: 300),
      curve: Curves.easeInOut,
    );
  }

  void _previous() {
    setState(() {
      _activePage = (_activePage - 1).clamp(0, 2);
    });
    _pageController.animateToPage(
      _activePage,
      duration: const Duration(milliseconds: 300),
      curve: Curves.easeInOut,
    );
  }

  Future<void> _completeOnboarding(
    BuildContext context,
    CommunityOnboardingNotifier onboarding, {
    bool skip = false,
  }) async {
    final succeeded = await onboarding.complete(
      notifier: widget.communityNotifier,
      skip: skip,
    );
    if (!mounted) {
      return;
    }
    if (!succeeded && onboarding.error != null) {
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(
          content: Text('We could not finish onboarding: ${onboarding.error}'),
          behavior: SnackBarBehavior.floating,
        ),
      );
      return;
    }

    Navigator.of(context).pop(!skip);
  }
}

class _WelcomeStep extends StatelessWidget {
  const _WelcomeStep({required this.onContinue});

  final VoidCallback onContinue;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 32, vertical: 24),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const Spacer(),
          Text(
            'Welcome to Communities',
            style: theme.textTheme.headlineMedium?.copyWith(fontWeight: FontWeight.bold),
          ),
          const SizedBox(height: 12),
          Text(
            'Pick the groups and paywall tiers that match your goals. We will fine-tune your feed and notifications automatically.',
            style: theme.textTheme.bodyLarge?.copyWith(color: constants.kGreyLightColor),
          ),
          const SizedBox(height: 24),
          FilledButton.tonal(
            onPressed: onContinue,
            child: const Text('Let’s get started'),
          ),
          const Spacer(flex: 2),
        ],
      ),
    );
  }
}

class _CommunitySelectionStep extends StatelessWidget {
  const _CommunitySelectionStep({required this.onRetry});

  final VoidCallback onRetry;

  @override
  Widget build(BuildContext context) {
    return Consumer<CommunityOnboardingNotifier>(
      builder: (context, onboarding, child) {
        if (onboarding.isLoadingRecommendations) {
          return const Center(child: CircularProgressIndicator());
        }

        if (onboarding.error != null && onboarding.recommendations.isEmpty) {
          return _ErrorState(message: onboarding.error!, onRetry: onRetry);
        }

        if (onboarding.recommendations.isEmpty) {
          return const _ErrorState(
            message: 'There are no communities to join right now. You can revisit onboarding later from settings.',
          );
        }

        return ListView.separated(
          padding: const EdgeInsets.fromLTRB(24, 12, 24, 24),
          itemCount: onboarding.recommendations.length,
          separatorBuilder: (context, index) => const SizedBox(height: 12),
          itemBuilder: (context, index) {
            final summary = onboarding.recommendations[index];
            final isSelected = onboarding.isSelected(summary.id);
            return _SelectableCommunityTile(
              summary: summary,
              isSelected: isSelected,
              onToggle: () => onboarding.toggleCommunity(summary.id),
            );
          },
        );
      },
    );
  }
}

class _SummaryStep extends StatelessWidget {
  const _SummaryStep({required this.onEditSelection});

  final VoidCallback onEditSelection;

  @override
  Widget build(BuildContext context) {
    return Consumer<CommunityOnboardingNotifier>(
      builder: (context, onboarding, child) {
        final selected = onboarding.recommendations
            .where((community) => onboarding.isSelected(community.id))
            .toList(growable: false);

        return Padding(
          padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 24),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Text(
                selected.isEmpty ? 'You have not selected any communities yet.' : 'You will join:',
                style: Theme.of(context).textTheme.titleMedium,
              ),
              const SizedBox(height: 12),
              if (selected.isEmpty)
                Text(
                  'You can still access the discovery tab after onboarding to join communities anytime.',
                  style: Theme.of(context)
                      .textTheme
                      .bodyMedium
                      ?.copyWith(color: constants.kGreyLightColor),
                )
              else
                Expanded(
                  child: ListView.builder(
                    itemCount: selected.length,
                    itemBuilder: (context, index) {
                      final summary = selected[index];
                      return ListTile(
                        contentPadding: EdgeInsets.zero,
                        leading: CircleAvatar(
                          backgroundColor: constants.kDefaultColor.withOpacity(0.12),
                          child: Text(summary.name.isNotEmpty ? summary.name[0].toUpperCase() : '?'),
                        ),
                        title: Text(summary.name),
                        subtitle: Text(summary.tagline.isEmpty
                            ? 'Members: ${summary.memberCount}'
                            : summary.tagline),
                      );
                    },
                  ),
                ),
              const SizedBox(height: 16),
              TextButton(
                onPressed: onEditSelection,
                child: const Text('Edit selection'),
              ),
            ],
          ),
        );
      },
    );
  }
}

class _SelectableCommunityTile extends StatelessWidget {
  const _SelectableCommunityTile({
    required this.summary,
    required this.isSelected,
    required this.onToggle,
  });

  final CommunitySummary summary;
  final bool isSelected;
  final VoidCallback onToggle;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return InkWell(
      onTap: onToggle,
      borderRadius: BorderRadius.circular(16),
      child: Container(
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(16),
          border: Border.all(
            color: isSelected ? constants.kDefaultColor : constants.kGreyLightColor.withOpacity(0.4),
            width: 1.4,
          ),
        ),
        padding: const EdgeInsets.all(16),
        child: Row(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            CircleAvatar(
              backgroundColor: constants.kDefaultColor.withOpacity(0.12),
              child: Text(summary.name.isNotEmpty ? summary.name[0].toUpperCase() : '?'),
            ),
            const SizedBox(width: 12),
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    summary.name,
                    style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.w600),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    summary.tagline.isEmpty ? 'Stay tuned for announcements.' : summary.tagline,
                    style: theme.textTheme.bodyMedium?.copyWith(color: constants.kGreyLightColor),
                  ),
                  const SizedBox(height: 8),
                  Wrap(
                    spacing: 8,
                    runSpacing: 4,
                    children: [
                      _Chip(label: '${summary.memberCount} members', icon: Icons.people_alt_outlined),
                      _Chip(
                        label: summary.visibility.toUpperCase(),
                        icon: summary.visibility == 'paid'
                            ? Icons.workspace_premium_outlined
                            : Icons.visibility_outlined,
                      ),
                    ],
                  ),
                ],
              ),
            ),
            Checkbox(value: isSelected, onChanged: (_) => onToggle()),
          ],
        ),
      ),
    );
  }
}

class _Chip extends StatelessWidget {
  const _Chip({required this.label, required this.icon});

  final String label;
  final IconData icon;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
      decoration: BoxDecoration(
        color: constants.kGreyLightColor.withOpacity(0.15),
        borderRadius: BorderRadius.circular(32),
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

class _ErrorState extends StatelessWidget {
  const _ErrorState({required this.message, this.onRetry});

  final String message;
  final VoidCallback? onRetry;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Padding(
      padding: const EdgeInsets.all(32),
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        crossAxisAlignment: CrossAxisAlignment.center,
        children: [
          Icon(Icons.wifi_off_outlined, size: 56, color: constants.kGreyLightColor.withOpacity(0.6)),
          const SizedBox(height: 16),
          Text(
            message,
            style: theme.textTheme.bodyLarge?.copyWith(color: constants.kGreyLightColor),
            textAlign: TextAlign.center,
          ),
          if (onRetry != null) ...[
            const SizedBox(height: 16),
            FilledButton.tonal(
              onPressed: onRetry,
              child: const Text('Retry'),
            ),
          ],
        ],
      ),
    );
  }
}
