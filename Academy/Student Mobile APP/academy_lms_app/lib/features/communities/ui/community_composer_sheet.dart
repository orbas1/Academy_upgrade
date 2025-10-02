import 'package:flutter/material.dart';

import '../state/community_presence_notifier.dart';

import '../models/paywall_tier.dart';
import '../models/upload_quota_status.dart';
import '../models/upload_quota_summary.dart';

class CommunityComposerResult {
  const CommunityComposerResult({
    required this.body,
    required this.visibility,
    this.paywallTierId,
  });

  final String body;
  final String visibility;
  final int? paywallTierId;
}

Future<CommunityComposerResult?> showCommunityComposerSheet(
  BuildContext context, {
  required int communityId,
  List<PaywallTier> paywallTiers = const <PaywallTier>[],
  bool canPostPublic = true,
  CommunityPresenceNotifier? presenceNotifier,
  UploadQuotaSummary? quotaSummary,
}) {
  final controller = TextEditingController();
  String visibility = 'community';
  bool isSubmitting = false;
  final hasPaidTiers = paywallTiers.isNotEmpty;
  int? selectedTierId = hasPaidTiers ? paywallTiers.first.id : null;

  return showModalBottomSheet<CommunityComposerResult>(
    context: context,
    isScrollControlled: true,
    useSafeArea: true,
    builder: (context) {
      return Padding(
        padding: EdgeInsets.only(
          left: 20,
          right: 20,
          bottom: MediaQuery.of(context).viewInsets.bottom + 24,
          top: 24,
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
                  value: visibility,
                  decoration: const InputDecoration(
                    labelText: 'Visibility',
                    border: OutlineInputBorder(),
                  ),
                  items: [
                    const DropdownMenuItem(value: 'community', child: Text('Members only')),
                    if (canPostPublic)
                      const DropdownMenuItem(value: 'public', child: Text('Public')),
                    if (hasPaidTiers)
                      const DropdownMenuItem(value: 'paid', child: Text('Paid tier')),
                  ],
                  onChanged: isSubmitting
                      ? null
                      : (value) {
                          if (value == null) {
                            return;
                          }
                          setState(() {
                            visibility = value;
                            if (visibility == 'paid' && selectedTierId == null && hasPaidTiers) {
                              selectedTierId = paywallTiers.first.id;
                            }
                          });
                        },
                ),
                if (visibility == 'paid' && hasPaidTiers) ...[
                  const SizedBox(height: 12),
                  DropdownButtonFormField<int>(
                    value: selectedTierId,
                    decoration: const InputDecoration(
                      labelText: 'Select paywall tier',
                      border: OutlineInputBorder(),
                    ),
                    items: paywallTiers
                        .map(
                          (tier) => DropdownMenuItem<int>(
                            value: tier.id,
                            child: Text(
                              '${tier.name} — ${tier.priceCurrency} ${tier.priceAmount.toStringAsFixed(2)} / ${tier.interval}',
                            ),
                          ),
                        )
                        .toList(growable: false),
                    onChanged: isSubmitting
                        ? null
                        : (value) {
                            setState(() => selectedTierId = value);
                          },
                  ),
                ],
                if (quotaSummary != null && quotaSummary.hasAnyQuota) ...[
                  const SizedBox(height: 12),
                  _UploadQuotaBanner(summary: quotaSummary),
                ],
                const SizedBox(height: 12),
                TextField(
                  controller: controller,
                  maxLines: 6,
                  decoration: const InputDecoration(
                    labelText: 'What is happening?',
                    border: OutlineInputBorder(),
                  ),
                  onChanged: (_) {
                    presenceNotifier?.markTyping(communityId);
                  },
                ),
                const SizedBox(height: 20),
                Align(
                  alignment: Alignment.centerRight,
                  child: FilledButton.icon(
                    onPressed: isSubmitting
                        ? null
                        : () {
                            final text = controller.text.trim();
                            if (text.isEmpty) {
                              ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(content: Text('Write something before posting.')),
                              );
                              return;
                            }
                            if (visibility == 'paid' && selectedTierId == null) {
                              ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(content: Text('Select a paywall tier for paid posts.')),
                              );
                              return;
                            }
                            setState(() => isSubmitting = true);
                            Navigator.of(context).pop(
                              CommunityComposerResult(
                                body: text,
                                visibility: visibility,
                                paywallTierId: visibility == 'paid' ? selectedTierId : null,
                              ),
                            );
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
}

class _UploadQuotaBanner extends StatelessWidget {
  const _UploadQuotaBanner({required this.summary});

  final UploadQuotaSummary summary;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final rows = <Widget>[];

    if (summary.user != null) {
      rows.add(_QuotaRow(label: 'Your uploads', status: summary.user!));
    }

    if (summary.community != null) {
      if (rows.isNotEmpty) {
        rows.add(const SizedBox(height: 8));
      }
      rows.add(_QuotaRow(label: 'Community pool', status: summary.community!));
    }

    return DecoratedBox(
      decoration: BoxDecoration(
        color: theme.colorScheme.surfaceVariant.withOpacity(0.6),
        borderRadius: BorderRadius.circular(12),
      ),
      child: Padding(
        padding: const EdgeInsets.all(12),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Storage usage',
              style: theme.textTheme.titleSmall,
            ),
            const SizedBox(height: 8),
            ...rows,
          ],
        ),
      ),
    );
  }
}

class _QuotaRow extends StatelessWidget {
  const _QuotaRow({required this.label, required this.status});

  final String label;
  final UploadQuotaStatus status;

  @override
  Widget build(BuildContext context) {
    final theme = Theme.of(context);
    final usedMb = status.usedMegabytes;
    final limitMb = status.limitMegabytes;
    final remainingMb = status.remainingMegabytes;

    String usageLine;
    if (status.isUnlimited || limitMb == null || limitMb <= 0) {
      usageLine = '${usedMb.toStringAsFixed(1)} MB used (unlimited)';
    } else {
      final remainingPart = remainingMb != null
          ? ' • ${remainingMb.clamp(0, double.infinity).toStringAsFixed(1)} MB left'
          : '';
      usageLine =
          '${usedMb.toStringAsFixed(1)} / ${limitMb.toStringAsFixed(1)} MB used$remainingPart';
    }

    final windowDays = status.windowDurationDays;
    final windowLine = windowDays > 0
        ? 'Rolling $windowDays-day window'
        : null;

    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(
          label,
          style: theme.textTheme.bodyMedium?.copyWith(fontWeight: FontWeight.w600),
        ),
        const SizedBox(height: 2),
        Text(
          usageLine,
          style: theme.textTheme.bodySmall,
        ),
        if (windowLine != null) ...[
          const SizedBox(height: 2),
          Text(
            windowLine,
            style: theme.textTheme.bodySmall?.copyWith(
              color: theme.textTheme.bodySmall?.color?.withOpacity(0.7),
            ),
          ),
        ],
      ],
    );
  }
}
