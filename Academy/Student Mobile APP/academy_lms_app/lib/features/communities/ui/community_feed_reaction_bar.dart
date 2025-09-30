import 'package:flutter/material.dart';

class CommunityFeedReactionBar extends StatelessWidget {
  const CommunityFeedReactionBar({
    super.key,
    required this.reactionCounts,
    required this.activeReaction,
    required this.onReactionSelected,
  });

  final Map<String, int> reactionCounts;
  final String? activeReaction;
  final ValueChanged<String?> onReactionSelected;

  static const Map<String, String> _reactionLabels = <String, String>{
    'like': 'Like',
    'celebrate': 'Celebrate',
    'insightful': 'Insightful',
    'support': 'Support',
  };

  static const Map<String, String> _reactionEmoji = <String, String>{
    'like': '👍',
    'celebrate': '🎉',
    'insightful': '💡',
    'support': '🤝',
  };

  @override
  Widget build(BuildContext context) {
    final total = reactionCounts.values.fold<int>(0, (previousValue, element) => previousValue + element);
    return Wrap(
      spacing: 8,
      runSpacing: 8,
      crossAxisAlignment: WrapCrossAlignment.center,
      children: [
        ..._reactionLabels.keys.map((reaction) {
          final isActive = reaction == activeReaction;
          final count = reactionCounts[reaction] ?? 0;
          return FilterChip(
            label: Text('${_reactionEmoji[reaction]} ${_reactionLabels[reaction]} • $count'),
            selected: isActive,
            onSelected: (selected) => onReactionSelected(selected ? reaction : null),
            selectedColor: Theme.of(context).colorScheme.primary.withOpacity(0.15),
            checkmarkColor: Theme.of(context).colorScheme.primary,
          );
        }),
        if (total > 0)
          Text(
            '$total reactions',
            style: Theme.of(context).textTheme.labelSmall?.copyWith(color: Colors.grey.shade600),
          ),
      ],
    );
  }
}
