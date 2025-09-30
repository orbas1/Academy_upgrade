import 'package:characters/characters.dart';
import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../state/community_presence_notifier.dart';

class CommunityPresenceHeader extends StatelessWidget {
  const CommunityPresenceHeader({super.key, required this.communityId});

  final int communityId;

  @override
  Widget build(BuildContext context) {
    return Consumer<CommunityPresenceNotifier>(
      builder: (context, presence, child) {
        final onlineMembers = presence.onlineMembersFor(communityId).toList()
          ..sort((a, b) => a.displayName.compareTo(b.displayName));
        final typingMembers = presence.typingMembersFor(communityId);
        final isConnected = presence.isConnected;
        final error = presence.connectionError;

        final List<Widget> children = <Widget>[];

        if (!isConnected || error != null) {
          children.add(_PresenceStatusTile(
            icon: Icons.sync_problem_outlined,
            title: 'Reconnecting…',
            subtitle: error ?? 'Live updates will resume shortly.',
            backgroundColor: Colors.orange.shade50,
            foregroundColor: Colors.orange.shade900,
          ));
        } else {
          children.add(
            _PresenceStatusTile(
              icon: Icons.radio_button_checked,
              title: onlineMembers.isEmpty
                  ? 'No members online right now'
                  : '${onlineMembers.length} member${onlineMembers.length == 1 ? '' : 's'} online',
              subtitle: onlineMembers.isEmpty
                  ? 'We will notify you when activity picks up.'
                  : onlineMembers.take(4).map((member) => member.displayName).join(', '),
              backgroundColor: Theme.of(context).colorScheme.surfaceVariant,
              foregroundColor: Theme.of(context).colorScheme.onSurfaceVariant,
            ),
          );
        }

        if (typingMembers.isNotEmpty) {
          final typingNames = typingMembers.map((member) => member.displayName).join(', ');
          children.add(
            _PresenceStatusTile(
              icon: Icons.keyboard_alt_outlined,
              title: typingMembers.length == 1
                  ? '$typingNames is typing…'
                  : '$typingNames are typing…',
              backgroundColor: Colors.blue.shade50,
              foregroundColor: Colors.blue.shade900,
            ),
          );
        }

        if (onlineMembers.length > 4) {
          children.add(
            SizedBox(
              height: 36,
              child: ListView.separated(
                scrollDirection: Axis.horizontal,
                itemCount: onlineMembers.length,
                separatorBuilder: (_, __) => const SizedBox(width: 8),
                itemBuilder: (context, index) {
                  final member = onlineMembers[index];
                  return Chip(
                    avatar: CircleAvatar(
                      backgroundColor: Theme.of(context).colorScheme.primary,
                      child: Text(
                        member.displayName.isNotEmpty
                            ? member.displayName.characters.first.toUpperCase()
                            : '?',
                        style: const TextStyle(color: Colors.white),
                      ),
                    ),
                    label: Text(member.displayName),
                  );
                },
              ),
            ),
          );
        }

        if (children.isEmpty) {
          return const SizedBox.shrink();
        }

        return Column(
          crossAxisAlignment: CrossAxisAlignment.stretch,
          children: children
              .map((widget) => Padding(
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
                    child: widget,
                  ))
              .toList(growable: false),
        );
      },
    );
  }
}

class _PresenceStatusTile extends StatelessWidget {
  const _PresenceStatusTile({
    required this.icon,
    required this.title,
    this.subtitle,
    this.backgroundColor,
    this.foregroundColor,
  });

  final IconData icon;
  final String title;
  final String? subtitle;
  final Color? backgroundColor;
  final Color? foregroundColor;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final effectiveForeground = foregroundColor ?? theme.colorScheme.onSurfaceVariant;
    return Container(
      decoration: BoxDecoration(
        color: backgroundColor ?? theme.colorScheme.surfaceVariant,
        borderRadius: BorderRadius.circular(16),
      ),
      padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
      child: Row(
        children: [
          Icon(icon, color: effectiveForeground),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  title,
                  style: theme.textTheme.bodyMedium?.copyWith(
                    fontWeight: FontWeight.w600,
                    color: effectiveForeground,
                  ),
                ),
                if (subtitle != null && subtitle!.isNotEmpty)
                  Text(
                    subtitle!,
                    style: theme.textTheme.bodySmall?.copyWith(color: effectiveForeground.withOpacity(0.85)),
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}
