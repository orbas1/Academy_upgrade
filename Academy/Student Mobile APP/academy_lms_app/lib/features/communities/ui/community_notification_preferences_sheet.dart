import 'package:flutter/material.dart';
import 'package:provider/provider.dart';

import '../../../providers/notification_preferences.dart';
import '../models/community_notification_preferences.dart';

class CommunityNotificationPreferencesSheet extends StatefulWidget {
  const CommunityNotificationPreferencesSheet({
    super.key,
    required this.communityId,
    required this.communityName,
  });

  final int communityId;
  final String communityName;

  @override
  State<CommunityNotificationPreferencesSheet> createState() =>
      _CommunityNotificationPreferencesSheetState();
}

class _CommunityNotificationPreferencesSheetState
    extends State<CommunityNotificationPreferencesSheet> {
  CommunityNotificationPreferences? _preferences;
  bool _hydrated = false;
  bool _saving = false;
  late Set<String> _mutedEvents;
  final List<String> _eventKeys = <String>[
    'community.post_created',
    'community.comment_created',
    'community.post_liked',
  ];

  @override
  void initState() {
    super.initState();
    _mutedEvents = <String>{};
    WidgetsBinding.instance.addPostFrameCallback((_) {
      _hydrate();
    });
  }

  Future<void> _hydrate() async {
    final provider = context.read<NotificationPreferencesProvider>();
    await provider.hydrate(widget.communityId);
    final prefs = provider.preferencesFor(widget.communityId) ??
        CommunityNotificationPreferences(
          communityId: widget.communityId,
          channelEmail: true,
          channelPush: true,
          channelInApp: true,
          digestFrequency: 'daily',
          mutedEvents: const <String>[],
        );

    setState(() {
      _preferences = prefs;
      _mutedEvents = prefs.mutedEvents.toSet();
      _hydrated = true;
    });
  }

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);

    return Consumer<NotificationPreferencesProvider>(
      builder: (context, provider, child) {
        final error = provider.error;
        final prefs = _preferences;

        return SafeArea(
          child: Padding(
            padding: EdgeInsets.only(
              bottom: MediaQuery.of(context).viewInsets.bottom,
            ),
            child: AnimatedSize(
              duration: const Duration(milliseconds: 200),
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 12),
                child: _hydrated && prefs != null
                    ? Column(
                        mainAxisSize: MainAxisSize.min,
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Center(
                            child: Container(
                              width: 48,
                              height: 4,
                              margin: const EdgeInsets.only(bottom: 12),
                              decoration: BoxDecoration(
                                color: theme.colorScheme.outlineVariant,
                                borderRadius: BorderRadius.circular(8),
                              ),
                            ),
                          ),
                          Text(
                            'Notification preferences',
                            style: theme.textTheme.titleMedium,
                          ),
                          const SizedBox(height: 8),
                          Text(
                            'Control how you hear from ${widget.communityName}.',
                            style: theme.textTheme.bodyMedium,
                          ),
                          const SizedBox(height: 16),
                          SwitchListTile.adaptive(
                            value: prefs.channelEmail,
                            onChanged: (value) => _updatePreferences(
                              prefs.copyWith(channelEmail: value),
                            ),
                            title: const Text('Email updates'),
                            subtitle: const Text('Mentions, replies, and important announcements.'),
                          ),
                          SwitchListTile.adaptive(
                            value: prefs.channelPush,
                            onChanged: (value) => _updatePreferences(
                              prefs.copyWith(channelPush: value),
                            ),
                            title: const Text('Push notifications'),
                            subtitle: const Text('Instant alerts on your device.'),
                          ),
                          SwitchListTile.adaptive(
                            value: prefs.channelInApp,
                            onChanged: (value) => _updatePreferences(
                              prefs.copyWith(channelInApp: value),
                            ),
                            title: const Text('In-app notifications'),
                            subtitle: const Text('Inbox and bell updates inside the app.'),
                          ),
                          const Divider(height: 32),
                          Text('Digest frequency', style: theme.textTheme.titleSmall),
                          const SizedBox(height: 8),
                          DropdownButtonFormField<String>(
                            value: prefs.digestFrequency,
                            decoration: const InputDecoration(
                              border: OutlineInputBorder(),
                            ),
                            items: const [
                              DropdownMenuItem(value: 'daily', child: Text('Daily summary')),
                              DropdownMenuItem(value: 'weekly', child: Text('Weekly summary')),
                              DropdownMenuItem(value: 'off', child: Text('Off')),
                            ],
                            onChanged: (value) {
                              if (value == null) return;
                              _updatePreferences(prefs.copyWith(digestFrequency: value));
                            },
                          ),
                          const SizedBox(height: 20),
                          Text('Mute specific events', style: theme.textTheme.titleSmall),
                          const SizedBox(height: 8),
                          Column(
                            children: _eventKeys.map((eventKey) {
                              final muted = _mutedEvents.contains(eventKey);
                              final labels = _labelsFor(eventKey);
                              return CheckboxListTile(
                                value: muted,
                                onChanged: (value) {
                                  setState(() {
                                    if (value == true) {
                                      _mutedEvents.add(eventKey);
                                    } else {
                                      _mutedEvents.remove(eventKey);
                                    }
                                    _updatePreferences(
                                      prefs.copyWith(
                                        mutedEvents: _mutedEvents.toList(),
                                      ),
                                    );
                                  });
                                },
                                title: Text(labels['title']!),
                                subtitle: Text(labels['subtitle']!),
                              );
                            }).toList(),
                          ),
                          if (error != null) ...[
                            const SizedBox(height: 8),
                            Text(
                              error,
                              style: theme.textTheme.bodySmall?.copyWith(color: theme.colorScheme.error),
                            ),
                          ],
                          const SizedBox(height: 16),
                          Row(
                            children: [
                              Expanded(
                                child: OutlinedButton(
                                  onPressed: _saving
                                      ? null
                                      : () async {
                                          await provider.reset(widget.communityId);
                                          if (!mounted) return;
                                          Navigator.of(context).pop();
                                        },
                                  child: const Text('Restore defaults'),
                                ),
                              ),
                              const SizedBox(width: 12),
                              Expanded(
                                child: ElevatedButton(
                                  onPressed: _saving
                                      ? null
                                      : () async {
                                          final updated = _preferences;
                                          if (updated == null) return;
                                          setState(() => _saving = true);
                                          await provider.update(widget.communityId, updated);
                                          if (!mounted) return;
                                          setState(() => _saving = false);
                                          if (provider.error == null) {
                                            Navigator.of(context).pop();
                                            ScaffoldMessenger.of(context).showSnackBar(
                                              const SnackBar(
                                                content: Text('Notification preferences updated'),
                                              ),
                                            );
                                          }
                                        },
                                  child: _saving
                                      ? const SizedBox(
                                          height: 18,
                                          width: 18,
                                          child: CircularProgressIndicator(strokeWidth: 2),
                                        )
                                      : const Text('Save changes'),
                                ),
                              ),
                            ],
                          ),
                        ],
                      )
                    : SizedBox(
                        height: MediaQuery.of(context).size.height * 0.35,
                        child: const Center(child: CircularProgressIndicator()),
                      ),
              ),
            ),
          ),
        );
      },
    );
  }

  void _updatePreferences(CommunityNotificationPreferences updated) {
    setState(() {
      _preferences = updated;
    });
  }

  Map<String, String> _labelsFor(String eventKey) {
    switch (eventKey) {
      case 'community.comment_created':
        return const {
          'title': 'Replies to my posts',
          'subtitle': 'Mute email/push alerts when members comment.',
        };
      case 'community.post_liked':
        return const {
          'title': 'Reactions to my posts',
          'subtitle': 'Silence notifications when someone likes your post.',
        };
      default:
        return const {
          'title': 'New posts in community',
          'subtitle': 'Stop notifications when new discussions go live.',
        };
    }
  }
}
