import 'package:flutter/material.dart';

import '../../l10n/app_localizations.dart';
import '../../models/security/device_session.dart';
import '../../services/security/device_security_api.dart';
import '../../widgets/custom_text.dart';

class DeviceSecurityScreen extends StatefulWidget {
  const DeviceSecurityScreen({super.key});

  @override
  State<DeviceSecurityScreen> createState() => _DeviceSecurityScreenState();
}

class _DeviceSecurityScreenState extends State<DeviceSecurityScreen> {
  final DeviceSecurityApi _api = DeviceSecurityApi();
  List<DeviceSession> _sessions = <DeviceSession>[];
  bool _loading = true;
  String? _error;

  @override
  void initState() {
    super.initState();
    _loadSessions();
  }

  Future<void> _loadSessions() async {
    setState(() {
      _loading = true;
      _error = null;
    });

    try {
      final sessions = await _api.fetchSessions();
      if (!mounted) return;
      setState(() {
        _sessions = sessions;
        _loading = false;
      });
    } catch (error) {
      if (!mounted) return;
      setState(() {
        _error = error.toString();
        _loading = false;
      });
    }
  }

  Future<void> _toggleTrust(DeviceSession session, bool trusted) async {
    try {
      final updated = await _api.toggleTrust(session.id, trusted);
      if (!mounted) return;
      setState(() {
        _sessions = _sessions
            .map((existing) => existing.id == updated.id ? updated : existing)
            .toList();
      });
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(error.toString())),
      );
    }
  }

  Future<void> _revoke(DeviceSession session) async {
    final localizations = AppLocalizations.of(context);
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (context) {
        return AlertDialog(
          title: Text(localizations.deviceSecurityRevokeConfirmTitle),
          content: Text(localizations.deviceSecurityRevokeConfirmMessage),
          actions: [
            TextButton(
              onPressed: () => Navigator.of(context).pop(false),
              child: Text(localizations.twoFactorCancel),
            ),
            ElevatedButton(
              onPressed: () => Navigator.of(context).pop(true),
              child: Text(localizations.deviceSecurityRevoke),
            ),
          ],
        );
      },
    );

    if (confirmed != true) {
      return;
    }

    try {
      await _api.revoke(session.id);
      if (!mounted) return;
      setState(() {
        _sessions = _sessions.map((existing) {
          if (existing.id == session.id) {
            return existing.copyWith(
              isRevoked: true,
              isTrusted: false,
              revokedAt: DateTime.now(),
            );
          }
          return existing;
        }).toList();
      });
    } catch (error) {
      if (!mounted) return;
      ScaffoldMessenger.of(context).showSnackBar(
        SnackBar(content: Text(error.toString())),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    final localizations = AppLocalizations.of(context);

    return Scaffold(
      appBar: AppBar(
        title: Text(localizations.deviceSecurityTitle),
      ),
      body: RefreshIndicator(
        onRefresh: _loadSessions,
        child: _loading
            ? const Center(child: CircularProgressIndicator())
            : _error != null
                ? ListView(
                    children: [
                      Padding(
                        padding: const EdgeInsets.all(24.0),
                        child: Text(
                          _error!,
                          style: Theme.of(context)
                              .textTheme
                              .bodyMedium
                              ?.copyWith(color: Colors.red),
                        ),
                      ),
                    ],
                  )
                : _sessions.isEmpty
                    ? ListView(
                        children: [
                          Padding(
                            padding: const EdgeInsets.all(24.0),
                            child: CustomText(
                              text: localizations.deviceSecurityNoDevices,
                              fontSize: 16,
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                        ],
                      )
                    : ListView(
                        padding: const EdgeInsets.all(16.0),
                        children: [
                          Text(
                            localizations.deviceSecuritySubtitle,
                            style: Theme.of(context)
                                .textTheme
                                .bodyMedium
                                ?.copyWith(color: Colors.grey[700]),
                          ),
                          const SizedBox(height: 12),
                          ..._sessions.map(
                            (session) => _DeviceSessionTile(
                              session: session,
                              onToggleTrust: (value) =>
                                  _toggleTrust(session, value),
                              onRevoke: () => _revoke(session),
                            ),
                          ),
                        ],
                      ),
      ),
    );
  }
}

class _DeviceSessionTile extends StatelessWidget {
  const _DeviceSessionTile({
    required this.session,
    required this.onToggleTrust,
    required this.onRevoke,
  });

  final DeviceSession session;
  final ValueChanged<bool> onToggleTrust;
  final VoidCallback onRevoke;

  @override
  Widget build(BuildContext context) {
    final localizations = AppLocalizations.of(context);
    final theme = Theme.of(context);

    final badges = <Widget>[];
    if (session.isCurrentDevice) {
      badges.add(_buildChip(theme, localizations.deviceSecurityCurrentDevice));
    }
    if (session.isRevoked) {
      badges.add(_buildChip(theme, localizations.deviceSecurityRevoked,
          color: Colors.red.shade100, textColor: Colors.red.shade900));
    } else if (session.isTrusted) {
      badges.add(_buildChip(theme, localizations.deviceSecurityTrusted,
          color: Colors.green.shade100, textColor: Colors.green.shade800));
    } else {
      badges.add(_buildChip(theme, localizations.deviceSecurityUntrusted,
          color: Colors.orange.shade100, textColor: Colors.orange.shade800));
    }

    return Card(
      margin: const EdgeInsets.symmetric(vertical: 8.0),
      child: Padding(
        padding: const EdgeInsets.all(16.0),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(
                        session.deviceName ?? '—',
                        style: theme.textTheme.titleMedium,
                      ),
                      const SizedBox(height: 4),
                      Text(
                        [
                          session.platform,
                          session.appVersion,
                        ].whereType<String>().where((value) => value.isNotEmpty).join(' • '),
                        style: theme.textTheme.bodySmall,
                      ),
                    ],
                  ),
                ),
                Wrap(
                  spacing: 8,
                  runSpacing: 4,
                  children: badges,
                ),
              ],
            ),
            const SizedBox(height: 8),
            Text(
              '${localizations.deviceSecurityLastSeen}: '
              '${session.lastSeenAt != null ? session.lastSeenAt!.toLocal().toString() : '—'}',
              style: theme.textTheme.bodySmall,
            ),
            if (session.ipAddress != null && session.ipAddress!.isNotEmpty)
              Text(
                '${localizations.deviceSecurityIpAddress}: ${session.ipAddress}',
                style: theme.textTheme.bodySmall,
              ),
            const SizedBox(height: 12),
            Row(
              children: [
                Expanded(
                  child: SwitchListTile.adaptive(
                    value: session.isTrusted,
                    title: Text(localizations.deviceSecurityTrustToggle),
                    contentPadding: EdgeInsets.zero,
                    onChanged: session.isRevoked ? null : onToggleTrust,
                  ),
                ),
                const SizedBox(width: 12),
                OutlinedButton(
                  onPressed: session.isRevoked ? null : onRevoke,
                  child: Text(localizations.deviceSecurityRevoke),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildChip(ThemeData theme, String label,
      {Color? color, Color? textColor}) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: color ?? theme.colorScheme.primary.withOpacity(0.1),
        borderRadius: BorderRadius.circular(16),
      ),
      child: Text(
        label,
        style: theme.textTheme.labelSmall?.copyWith(
          color: textColor ?? theme.colorScheme.primary,
          fontWeight: FontWeight.w600,
        ),
      ),
    );
  }
}
