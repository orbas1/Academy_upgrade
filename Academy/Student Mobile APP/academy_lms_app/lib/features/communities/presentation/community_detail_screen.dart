import 'dart:async';

import 'package:academy_lms_app/constants.dart' as constants;
import 'package:academy_lms_app/features/communities/models/community_feed_item.dart';
import 'package:academy_lms_app/features/communities/models/community_leaderboard_entry.dart';
import 'package:academy_lms_app/features/communities/models/community_summary.dart';
import 'package:academy_lms_app/features/communities/models/paywall_tier.dart';
import 'package:academy_lms_app/features/communities/state/community_comments_notifier.dart';
import 'package:academy_lms_app/features/communities/state/community_notifier.dart';
import 'package:academy_lms_app/features/communities/ui/community_composer_sheet.dart';
import 'package:academy_lms_app/features/communities/ui/community_feed_item_card.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

class CommunityDetailScreen extends StatefulWidget {
  const CommunityDetailScreen({super.key, required this.summary});

  final CommunitySummary summary;

  @override
  State<CommunityDetailScreen> createState() => _CommunityDetailScreenState();
}

class _CommunityDetailScreenState extends State<CommunityDetailScreen> {
  final ScrollController _feedController = ScrollController();
  bool _bootstrapped = false;
  String? _lastError;
  String? _lastWarning;

  @override
  void initState() {
    super.initState();
    _feedController.addListener(_onFeedScroll);
  }

  @override
  void dispose() {
    _feedController.removeListener(_onFeedScroll);
    _feedController.dispose();
    super.dispose();
  }

  @override
  void didChangeDependencies() {
    super.didChangeDependencies();
    if (_bootstrapped) {
      return;
    }
    _bootstrapped = true;

    final notifier = context.read<CommunityNotifier>();
    unawaited(notifier.refreshMembership(widget.summary.id));
    unawaited(notifier.refreshFeed(widget.summary.id));
    unawaited(notifier.loadLeaderboard(widget.summary.id));
  }

  void _onFeedScroll() {
    if (!_feedController.hasClients) {
      return;
    }
    final notifier = context.read<CommunityNotifier>();
    final threshold = _feedController.position.maxScrollExtent -
        _feedController.position.viewportDimension * 0.4;
    if (_feedController.position.pixels >= threshold &&
        notifier.canLoadMoreFeed(widget.summary.id) &&
        !notifier.isFeedLoadingMore(widget.summary.id)) {
      unawaited(notifier.loadMoreFeed(widget.summary.id));
    }
  }

  @override
  Widget build(BuildContext context) {
    return Consumer<CommunityNotifier>(
      builder: (context, notifier, child) {
        WidgetsBinding.instance.addPostFrameCallback((_) {
          final error = notifier.error;
          if (error != null && error != _lastError && mounted) {
            _lastError = error;
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(
                content: Text(error),
                behavior: SnackBarBehavior.floating,
              ),
            );
          }

          final warning = notifier.consumeQueueWarning();
          if (warning != null && warning != _lastWarning && mounted) {
            _lastWarning = warning;
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(
                content: Text(warning),
                behavior: SnackBarBehavior.floating,
                backgroundColor: Colors.orange.shade700,
              ),
            );
          }
        });

        final isMember = notifier.isMember;

        return DefaultTabController(
          length: 3,
          child: Scaffold(
            floatingActionButton: isMember
                ? FloatingActionButton.extended(
                    onPressed: () => _showComposer(context, notifier),
                    icon: const Icon(Icons.create),
                    label: const Text('New post'),
                  )
                : null,
            body: NestedScrollView(
              headerSliverBuilder: (context, innerScrolled) {
                return <Widget>[
                  SliverAppBar(
                    expandedHeight: 220,
                    pinned: true,
                    stretch: true,
                    title: Text(widget.summary.name),
                    flexibleSpace: FlexibleSpaceBar(
                      background: _CommunityHeader(
                        summary: widget.summary,
                      ),
                    ),
                    bottom: const TabBar(
                      tabs: [
                        Tab(text: 'Feed'),
                        Tab(text: 'Leaderboard'),
                        Tab(text: 'About & Access'),
                      ],
                    ),
                  ),
                ];
              },
              body: TabBarView(
                children: [
                  _FeedTab(
                    controller: _feedController,
                    communityId: widget.summary.id,
                  ),
                  _LeaderboardTab(communityId: widget.summary.id),
                  _AboutTab(summary: widget.summary),
                ],
              ),
            ),
          ),
        );
      },
    );
  }

  Future<void> _showComposer(
    BuildContext context,
    CommunityNotifier notifier,
  ) async {
    List<PaywallTier> paywallTiers = const <PaywallTier>[];
    try {
      paywallTiers = await notifier.repository.loadPaywallTiers(widget.summary.id);
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(
            content: Text('Unable to load paywall tiers: $error'),
            behavior: SnackBarBehavior.floating,
          ),
        );
      }
    }

    final result = await showCommunityComposerSheet(
      context,
      paywallTiers: paywallTiers,
      canPostPublic: notifier.canModerate || notifier.isMember,
    );
    if (result == null) {
      return;
    }

    try {
      await notifier.createPost(
        widget.summary.id,
        bodyMarkdown: result.body,
        visibility: result.visibility,
        paywallTierId: result.paywallTierId,
      );
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('Post published to the community feed.')),
        );
      }
    } catch (error) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          SnackBar(content: Text('Failed to publish: $error')),
        );
      }
    }
  }
}

class _CommunityHeader extends StatelessWidget {
  const _CommunityHeader({required this.summary});

  final CommunitySummary summary;

  @override
  Widget build(BuildContext context) {
    return Container(
      decoration: const BoxDecoration(
        gradient: LinearGradient(
          colors: [constants.kDefaultColor, Color(0xFF6A4DF0)],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
      ),
      padding: const EdgeInsets.fromLTRB(20, 72, 20, 20),
      child: Consumer<CommunityNotifier>(
        builder: (context, notifier, child) {
          final membership = notifier.membership;
          final isMember = notifier.isMember;
          final theme = Theme.of(context);

          return Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            mainAxisAlignment: MainAxisAlignment.end,
            children: [
              Text(
                summary.name,
                style: theme.textTheme.headlineSmall?.copyWith(
                  color: constants.kWhiteColor,
                  fontWeight: FontWeight.w600,
                ),
              ),
              const SizedBox(height: 8),
              Text(
                summary.tagline.isEmpty
                    ? 'Stay tuned for community announcements.'
                    : summary.tagline,
                style: theme.textTheme.bodyMedium?.copyWith(
                  color: constants.kWhiteColor.withOpacity(0.85),
                ),
              ),
              const SizedBox(height: 16),
              Wrap(
                spacing: 12,
                runSpacing: 8,
                children: [
                  _HeaderChip(
                    icon: Icons.people_alt,
                    label: '${summary.memberCount} members',
                  ),
                  _HeaderChip(
                    icon: Icons.lock_open,
                    label: summary.visibility.toUpperCase(),
                  ),
                  if (membership != null)
                    _HeaderChip(
                      icon: Icons.workspace_premium,
                      label: 'Level ${membership.level}',
                    ),
                ],
              ),
              const Spacer(),
              Row(
                children: [
                  Expanded(
                    child: FilledButton(
                      onPressed: notifier.isMutatingMembership
                          ? null
                          : () {
                              if (isMember) {
                                notifier.leaveCommunity(summary.id);
                              } else {
                                notifier.joinCommunity(summary.id);
                              }
                            },
                      style: FilledButton.styleFrom(
                        backgroundColor: isMember
                            ? constants.kWhiteColor.withOpacity(0.15)
                            : constants.kWhiteColor,
                        foregroundColor:
                            isMember ? constants.kWhiteColor : constants.kDefaultColor,
                      ),
                      child: Text(isMember ? 'Leave community' : 'Join community'),
                    ),
                  ),
                  const SizedBox(width: 12),
                  FilledButton.tonalIcon(
                    onPressed: () => _showInviteDialog(context),
                    icon: const Icon(Icons.share),
                    label: const Text('Invite'),
                    style: FilledButton.styleFrom(
                      foregroundColor: constants.kWhiteColor,
                      backgroundColor: constants.kWhiteColor.withOpacity(0.18),
                    ),
                  ),
                ],
              ),
            ],
          );
        },
      ),
    );
  }

  void _showInviteDialog(BuildContext context) {
    showDialog<void>(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: const Text('Share community'),
          content: Text(
            'Share ${summary.name} with peers. Copy the invite link from the admin dashboard or send a direct invite.',
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(context).pop(),
              child: const Text('Close'),
            ),
          ],
        );
      },
    );
  }
}

class _HeaderChip extends StatelessWidget {
  const _HeaderChip({required this.icon, required this.label});

  final IconData icon;
  final String label;

  @override
  Widget build(BuildContext context) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
      decoration: BoxDecoration(
        color: constants.kWhiteColor.withOpacity(0.16),
        borderRadius: BorderRadius.circular(32),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 16, color: constants.kWhiteColor),
          const SizedBox(width: 6),
          Text(
            label,
            style: Theme.of(context).textTheme.bodySmall?.copyWith(
                  color: constants.kWhiteColor,
                  fontWeight: FontWeight.w500,
                ),
          ),
        ],
      ),
    );
  }
}

class _FeedTab extends StatelessWidget {
  const _FeedTab({required this.controller, required this.communityId});

  final ScrollController controller;
  final int communityId;

  @override
  Widget build(BuildContext context) {
    return Consumer<CommunityNotifier>(
      builder: (context, notifier, child) {
        final feed = notifier.feed;
        final filter = notifier.feedFilterFor(communityId);

        if (notifier.isLoading && feed.isEmpty) {
          return const Center(child: CircularProgressIndicator());
        }

        Widget buildFeedList() {
          if (feed.isEmpty) {
            return ListView(
              physics: const AlwaysScrollableScrollPhysics(),
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 48),
              children: const [
                _EmptyFeedState(),
              ],
            );
          }

          return ListView.separated(
            controller: controller,
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            itemCount: feed.length + (notifier.canLoadMoreFeed(communityId) ? 1 : 0),
            separatorBuilder: (context, index) => const SizedBox(height: 12),
            itemBuilder: (context, index) {
              if (index >= feed.length) {
                return const _LoadingMoreIndicator();
              }

              final item = feed[index];

              return CommunityFeedItemCard(
                item: item,
                onToggleReaction: (reaction) => notifier.togglePostReaction(
                  communityId,
                  item.id,
                  reaction: reaction ?? 'like',
                  clientReference: item.clientReference,
                ),
                onShowComments: () => _showComments(context, notifier, item),
                onShowReactions: () => _showReactions(context, item),
                onAction: (action) => _handleFeedAction(context, notifier, item, action),
                canModerate: notifier.canModerate,
              );
            },
          );
        }

        return Column(
          children: [
            Padding(
              padding: const EdgeInsets.fromLTRB(16, 8, 16, 4),
              child: SegmentedButton<String>(
                segments: const [
                  ButtonSegment<String>(
                    value: 'new',
                    label: Text('Latest'),
                    icon: Icon(Icons.bolt_outlined),
                  ),
                  ButtonSegment<String>(
                    value: 'top',
                    label: Text('Top'),
                    icon: Icon(Icons.trending_up),
                  ),
                  ButtonSegment<String>(
                    value: 'following',
                    label: Text('Following'),
                    icon: Icon(Icons.favorite_border_outlined),
                  ),
                ],
                selected: <String>{filter},
                showSelectedIcon: false,
                onSelectionChanged: (selection) {
                  if (selection.isEmpty) {
                    return;
                  }
                  final next = selection.first;
                  if (next == filter) {
                    return;
                  }
                  notifier.changeFeedFilter(communityId, filter: next);
                },
              ),
            ),
            Expanded(
              child: RefreshIndicator(
                onRefresh: () => notifier.refreshFeed(communityId, filter: filter),
                child: buildFeedList(),
              ),
            ),
          ],
        );
      },
    );
  }

  Future<void> _showComments(
    BuildContext context,
    CommunityNotifier notifier,
    CommunityFeedItem item,
  ) async {
    await showModalBottomSheet<void>(
      context: context,
      isScrollControlled: true,
      builder: (context) {
        return ChangeNotifierProvider<CommunityCommentsNotifier>(
          create: (_) => CommunityCommentsNotifier(
            repository: notifier.repository,
            communityId: communityId,
            postId: item.id,
          )..refresh(),
          child: _CommunityCommentsSheet(item: item),
        );
      },
    );
  }

  void _showReactions(BuildContext context, CommunityFeedItem item) {
    showDialog<void>(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: const Text('Reactions'),
          content: Text('${item.likeCount} members reacted to this post.'),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(context).pop(),
              child: const Text('Close'),
            ),
          ],
        );
      },
    );
  }

  Future<void> _handleFeedAction(
    BuildContext context,
    CommunityNotifier notifier,
    CommunityFeedItem item,
    CommunityFeedItemAction action,
  ) async {
    switch (action) {
      case CommunityFeedItemAction.report:
        final reason = await _promptReportReason(context);
        if (reason == null || reason.trim().isEmpty) {
          return;
        }
        try {
          await notifier.reportPost(communityId, item.id, reason: reason.trim());
          if (context.mounted) {
            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(
                content: Text('Thanks for the report. Moderators will review it shortly.'),
                behavior: SnackBarBehavior.floating,
              ),
            );
          }
        } catch (error) {
          if (context.mounted) {
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(
                content: Text('Failed to submit report: $error'),
                behavior: SnackBarBehavior.floating,
              ),
            );
          }
        }
        break;
      case CommunityFeedItemAction.hide:
        final confirmed = await _confirmHidePost(context);
        if (!confirmed) {
          return;
        }
        try {
          await notifier.moderatePost(
            communityId,
            item.id,
            action: 'hide',
            note: 'Hidden via mobile moderation tools',
          );
          if (context.mounted) {
            ScaffoldMessenger.of(context).showSnackBar(
              const SnackBar(
                content: Text('Post hidden from the community feed.'),
                behavior: SnackBarBehavior.floating,
              ),
            );
          }
        } catch (error) {
          if (context.mounted) {
            ScaffoldMessenger.of(context).showSnackBar(
              SnackBar(
                content: Text('Unable to hide post: $error'),
                behavior: SnackBarBehavior.floating,
              ),
            );
          }
        }
        break;
    }
  }

  Future<String?> _promptReportReason(BuildContext context) async {
    final reasons = <String>[
      'Spam or promotion',
      'Harassment or hate',
      'Sensitive or unsafe content',
      'Other',
    ];
    String currentReason = reasons.first;
    String? errorMessage;
    final detailController = TextEditingController();

    final result = await showDialog<String>(
      context: context,
      builder: (context) {
        return StatefulBuilder(
          builder: (context, setState) {
            return AlertDialog(
              title: const Text('Report post'),
              content: Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  DropdownButtonFormField<String>(
                    value: currentReason,
                    decoration: const InputDecoration(labelText: 'Reason'),
                    items: reasons
                        .map(
                          (reason) => DropdownMenuItem<String>(
                            value: reason,
                            child: Text(reason),
                          ),
                        )
                        .toList(growable: false),
                    onChanged: (value) {
                      if (value == null) {
                        return;
                      }
                      setState(() {
                        currentReason = value;
                        errorMessage = null;
                      });
                    },
                  ),
                  const SizedBox(height: 12),
                  TextField(
                    controller: detailController,
                    maxLines: 3,
                    decoration: const InputDecoration(
                      labelText: 'Add details (optional)',
                      border: OutlineInputBorder(),
                    ),
                  ),
                  if (errorMessage != null)
                    Padding(
                      padding: const EdgeInsets.only(top: 8),
                      child: Text(
                        errorMessage!,
                        style: TextStyle(color: Colors.red.shade700),
                      ),
                    ),
                ],
              ),
              actions: [
                TextButton(
                  onPressed: () => Navigator.of(context).pop(),
                  child: const Text('Cancel'),
                ),
                FilledButton(
                  onPressed: () {
                    if (currentReason == 'Other' && detailController.text.trim().isEmpty) {
                      setState(() {
                        errorMessage = 'Please describe the issue.';
                      });
                      return;
                    }
                    final detail = detailController.text.trim();
                    final reason = detail.isEmpty
                        ? currentReason
                        : '$currentReason — $detail';
                    Navigator.of(context).pop(reason);
                  },
                  child: const Text('Submit report'),
                ),
              ],
            );
          },
        );
      },
    );

    detailController.dispose();
    return result;
  }

  Future<bool> _confirmHidePost(BuildContext context) async {
    final result = await showDialog<bool>(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: const Text('Hide post for members?'),
          content: const Text(
            'Hidden posts remain visible to moderators for review but are removed from member feeds.',
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(context).pop(false),
              child: const Text('Cancel'),
            ),
            FilledButton(
              onPressed: () => Navigator.of(context).pop(true),
              child: const Text('Hide post'),
            ),
          ],
        );
      },
    );

    return result ?? false;
  }
}

class _LeaderboardTab extends StatelessWidget {
  const _LeaderboardTab({required this.communityId});

  final int communityId;

  @override
  Widget build(BuildContext context) {
    return Consumer<CommunityNotifier>(
      builder: (context, notifier, child) {
        final entries = notifier.leaderboard;

        if (notifier.isLeaderboardLoading && entries.isEmpty) {
          return const Center(child: CircularProgressIndicator());
        }

        if (entries.isEmpty) {
          return const _EmptyLeaderboardState();
        }

        return RefreshIndicator(
          onRefresh: () => notifier.loadLeaderboard(communityId),
          child: ListView.separated(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 20),
            itemCount: entries.length,
            separatorBuilder: (context, index) => const SizedBox(height: 12),
            itemBuilder: (context, index) {
              final entry = entries[index];
              return _LeaderboardTile(entry: entry);
            },
          ),
        );
      },
    );
  }
}

class _LeaderboardTile extends StatelessWidget {
  const _LeaderboardTile({required this.entry});

  final CommunityLeaderboardEntry entry;

  @override
  Widget build(BuildContext context) {
    return ListTile(
      leading: CircleAvatar(
        backgroundColor: constants.kDefaultColor.withOpacity(0.15),
        child: Text('${entry.rank + 1}'),
      ),
      title: Text(entry.memberName ?? 'Anonymous'),
      subtitle: Text('${entry.points} pts'),
      trailing: entry.streak != null
          ? Chip(
              label: Text('🔥 ${entry.streak}d'),
            )
          : null,
    );
  }
}

class _AboutTab extends StatelessWidget {
  const _AboutTab({required this.summary});

  final CommunitySummary summary;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return ListView(
      padding: const EdgeInsets.all(20),
      children: [
        Text('About', style: theme.textTheme.titleMedium),
        const SizedBox(height: 8),
        Text(
          summary.tagline.isEmpty
              ? 'This community brings learners together. New updates will appear soon.'
              : summary.tagline,
          style: theme.textTheme.bodyMedium,
        ),
        const SizedBox(height: 20),
        Text('Access', style: theme.textTheme.titleMedium),
        const SizedBox(height: 8),
        Text(
          summary.visibility == 'public'
              ? 'Anyone can view posts. Join to participate in discussions and unlock premium modules.'
              : 'Posts are visible to members only. Join to see the latest updates and contribute.',
          style: theme.textTheme.bodyMedium,
        ),
      ],
    );
  }
}

class _CommunityCommentsSheet extends StatefulWidget {
  const _CommunityCommentsSheet({required this.item});

  final CommunityFeedItem item;

  @override
  State<_CommunityCommentsSheet> createState() => _CommunityCommentsSheetState();
}

class _CommunityCommentsSheetState extends State<_CommunityCommentsSheet> {
  final ScrollController _controller = ScrollController();
  final TextEditingController _composerController = TextEditingController();
  bool _submitting = false;

  @override
  void initState() {
    super.initState();
    _controller.addListener(_onScroll);
  }

  @override
  void dispose() {
    _controller.removeListener(_onScroll);
    _controller.dispose();
    _composerController.dispose();
    super.dispose();
  }

  void _onScroll() {
    final notifier = context.read<CommunityCommentsNotifier>();
    if (_controller.position.pixels >=
            _controller.position.maxScrollExtent -
                _controller.position.viewportDimension * 0.3 &&
        notifier.hasMore &&
        !notifier.isLoadingMore) {
      unawaited(notifier.loadMore());
    }
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return SafeArea(
      child: Padding(
        padding: EdgeInsets.only(
          bottom: MediaQuery.of(context).viewInsets.bottom,
        ),
        child: Consumer<CommunityCommentsNotifier>(
          builder: (context, notifier, child) {
            final comments = notifier.comments;

            return Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Container(
                  width: 60,
                  margin: const EdgeInsets.symmetric(vertical: 12),
                  height: 4,
                  decoration: BoxDecoration(
                    color: constants.kGreyLightColor.withOpacity(0.4),
                    borderRadius: BorderRadius.circular(100),
                  ),
                ),
                Padding(
                  padding: const EdgeInsets.symmetric(horizontal: 20),
                  child: Row(
                    children: [
                      Expanded(
                        child: Text(
                          'Comments',
                          style: theme.textTheme.titleMedium,
                        ),
                      ),
                      IconButton(
                        icon: const Icon(Icons.refresh),
                        onPressed: notifier.isLoading ? null : () => notifier.refresh(),
                      ),
                    ],
                  ),
                ),
                if (notifier.isLoading && comments.isEmpty)
                  const Padding(
                    padding: EdgeInsets.all(24),
                    child: CircularProgressIndicator(),
                  )
                else
                  Flexible(
                    child: ListView.builder(
                      controller: _controller,
                      shrinkWrap: true,
                      padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
                      itemCount: comments.length + (notifier.hasMore ? 1 : 0),
                      itemBuilder: (context, index) {
                        if (index >= comments.length) {
                          return const _LoadingMoreIndicator();
                        }

                        final comment = comments[index];
                        return ListTile(
                          contentPadding: EdgeInsets.zero,
                          leading: CircleAvatar(
                            backgroundColor: constants.kDefaultColor.withOpacity(0.1),
                            child: Text(comment.authorName.isNotEmpty
                                ? comment.authorName[0].toUpperCase()
                                : '?'),
                          ),
                          title: Text(comment.authorName),
                          subtitle: Text(comment.body),
                        );
                      },
                    ),
                  ),
                const Divider(height: 1),
                Padding(
                  padding: const EdgeInsets.fromLTRB(20, 12, 20, 20),
                  child: Row(
                    children: [
                      Expanded(
                        child: TextField(
                          controller: _composerController,
                          minLines: 1,
                          maxLines: 3,
                          decoration: const InputDecoration(
                            hintText: 'Add a comment',
                            border: OutlineInputBorder(),
                          ),
                        ),
                      ),
                      const SizedBox(width: 12),
                      IconButton(
                        icon: const Icon(Icons.send),
                        onPressed: _submitting
                            ? null
                            : () async {
                                final text = _composerController.text.trim();
                                if (text.isEmpty) {
                                  return;
                                }
                                setState(() => _submitting = true);
                                try {
                                  await notifier.addComment(text);
                                  _composerController.clear();
                                } catch (error) {
                                  if (context.mounted) {
                                    ScaffoldMessenger.of(context).showSnackBar(
                                      SnackBar(content: Text(error.toString())),
                                    );
                                  }
                                } finally {
                                  if (mounted) {
                                    setState(() => _submitting = false);
                                  }
                                }
                              },
                      ),
                    ],
                  ),
                ),
              ],
            );
          },
        ),
      ),
    );
  }
}

class _EmptyFeedState extends StatelessWidget {
  const _EmptyFeedState();

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Center(
      child: Padding(
        padding: const EdgeInsets.symmetric(horizontal: 32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.forum_outlined, size: 48, color: constants.kGreyLightColor.withOpacity(0.8)),
            const SizedBox(height: 12),
            Text('No posts yet', style: theme.textTheme.titleMedium),
            const SizedBox(height: 4),
            Text(
              'Be the first to start a conversation or check back soon for new activity.',
              textAlign: TextAlign.center,
              style: theme.textTheme.bodyMedium?.copyWith(color: constants.kGreyLightColor),
            ),
          ],
        ),
      ),
    );
  }
}

class _EmptyLeaderboardState extends StatelessWidget {
  const _EmptyLeaderboardState();

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Icon(Icons.emoji_events_outlined, size: 48, color: constants.kGreyLightColor.withOpacity(0.8)),
            const SizedBox(height: 12),
            Text('Leaderboard warming up', style: theme.textTheme.titleMedium),
            const SizedBox(height: 4),
            Text(
              'Engage with the community to earn points and appear in the rankings.',
              textAlign: TextAlign.center,
              style: theme.textTheme.bodyMedium?.copyWith(color: constants.kGreyLightColor),
            ),
          ],
        ),
      ),
    );
  }
}

class _LoadingMoreIndicator extends StatelessWidget {
  const _LoadingMoreIndicator();

  @override
  Widget build(BuildContext context) {
    return const Padding(
      padding: EdgeInsets.symmetric(vertical: 16),
      child: Center(child: CircularProgressIndicator()),
    );
  }
}
