import 'dart:async';

import 'package:academy_lms_app/constants.dart' as constants;
import 'package:academy_lms_app/features/communities/models/community_feed_item.dart';
import 'package:academy_lms_app/features/communities/models/community_leaderboard_entry.dart';
import 'package:academy_lms_app/features/communities/models/community_summary.dart';
import 'package:academy_lms_app/features/communities/state/community_comments_notifier.dart';
import 'package:academy_lms_app/features/communities/state/community_notifier.dart';
import 'package:flutter/material.dart';
import 'package:intl/intl.dart';
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
    final controller = TextEditingController();
    final visibilities = <String>['community', 'public', 'paid'];
    String selectedVisibility = 'community';
    bool isSubmitting = false;

    final result = await showModalBottomSheet<bool>(
      context: context,
      isScrollControlled: true,
      builder: (context) {
        return Padding(
          padding: EdgeInsets.only(
            left: 20,
            right: 20,
            top: 24,
            bottom: MediaQuery.of(context).viewInsets.bottom + 24,
          ),
          child: StatefulBuilder(
            builder: (context, setState) {
              return Column(
                mainAxisSize: MainAxisSize.min,
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'Share an update',
                    style: Theme.of(context).textTheme.titleMedium,
                  ),
                  const SizedBox(height: 12),
                  DropdownButtonFormField<String>(
                    value: selectedVisibility,
                    decoration: const InputDecoration(
                      labelText: 'Visibility',
                      border: OutlineInputBorder(),
                    ),
                    items: visibilities
                        .map(
                          (visibility) => DropdownMenuItem<String>(
                            value: visibility,
                            child: Text(visibility.toUpperCase()),
                          ),
                        )
                        .toList(growable: false),
                    onChanged: isSubmitting
                        ? null
                        : (value) {
                            if (value == null) {
                              return;
                            }
                            setState(() {
                              selectedVisibility = value;
                            });
                          },
                  ),
                  const SizedBox(height: 12),
                  TextField(
                    controller: controller,
                    maxLines: 6,
                    textInputAction: TextInputAction.newline,
                    decoration: const InputDecoration(
                      labelText: 'What is happening?',
                      border: OutlineInputBorder(),
                    ),
                  ),
                  const SizedBox(height: 20),
                  Align(
                    alignment: Alignment.centerRight,
                    child: FilledButton.icon(
                      onPressed: isSubmitting
                          ? null
                          : () async {
                              final text = controller.text.trim();
                              if (text.isEmpty) {
                                ScaffoldMessenger.of(context).showSnackBar(
                                  const SnackBar(
                                    content: Text('Write something before posting.'),
                                  ),
                                );
                                return;
                              }
                              setState(() => isSubmitting = true);
                              try {
                                await notifier.createPost(
                                  widget.summary.id,
                                  bodyMarkdown: text,
                                  visibility: selectedVisibility,
                                );
                                if (context.mounted) {
                                  Navigator.of(context).pop(true);
                                }
                              } catch (error) {
                                if (context.mounted) {
                                  ScaffoldMessenger.of(context).showSnackBar(
                                    SnackBar(content: Text(error.toString())),
                                  );
                                }
                              } finally {
                                setState(() => isSubmitting = false);
                              }
                            },
                      icon: const Icon(Icons.send),
                      label: const Text('Publish'),
                    ),
                  ),
                ],
              );
            },
          ),
        );
      },
    );

    if (result == true && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(
          content: Text('Post published to the community feed.'),
        ),
      );
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

        if (notifier.isLoading && feed.isEmpty) {
          return const Center(child: CircularProgressIndicator());
        }

        if (!notifier.isLoading && feed.isEmpty) {
          return const _EmptyFeedState();
        }

        return RefreshIndicator(
          onRefresh: () => notifier.refreshFeed(communityId),
          child: ListView.separated(
            controller: controller,
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
            itemCount: feed.length + (notifier.canLoadMoreFeed(communityId) ? 1 : 0),
            separatorBuilder: (context, index) => const SizedBox(height: 12),
            itemBuilder: (context, index) {
              if (index >= feed.length) {
                return const _LoadingMoreIndicator();
              }

              final item = feed[index];

              return _CommunityPostTile(
                item: item,
                onToggleReaction: () => notifier.togglePostReaction(communityId, item.id),
                onShowComments: () => _showComments(context, notifier, item),
              );
            },
          ),
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
}

class _CommunityPostTile extends StatelessWidget {
  const _CommunityPostTile({
    required this.item,
    required this.onToggleReaction,
    required this.onShowComments,
  });

  final CommunityFeedItem item;
  final VoidCallback onToggleReaction;
  final VoidCallback onShowComments;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final formatter = DateFormat.yMMMd().add_jm();

    return Card(
      elevation: 1,
      margin: EdgeInsets.zero,
      shape: RoundedRectangleBorder(borderRadius: BorderRadius.circular(16)),
      child: Padding(
        padding: const EdgeInsets.all(16),
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
                      Text(
                        item.authorName,
                        style: theme.textTheme.titleSmall,
                      ),
                      Text(
                        formatter.format(item.createdAt),
                        style: theme.textTheme.bodySmall?.copyWith(
                          color: constants.kGreyLightColor,
                        ),
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
            Row(
              children: [
                IconButton(
                  icon: Icon(
                    item.isLiked ? Icons.favorite : Icons.favorite_border,
                    color: item.isLiked ? constants.kDefaultColor : constants.kGreyLightColor,
                  ),
                  onPressed: onToggleReaction,
                ),
                Text('${item.likeCount}'),
                const SizedBox(width: 12),
                IconButton(
                  icon: const Icon(Icons.comment_outlined, color: constants.kGreyLightColor),
                  onPressed: onShowComments,
                ),
                Text('${item.commentCount}'),
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
        borderRadius: BorderRadius.circular(32),
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
