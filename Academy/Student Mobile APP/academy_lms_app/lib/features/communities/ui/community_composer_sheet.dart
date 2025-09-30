import 'package:flutter/material.dart';

class CommunityComposerResult {
  const CommunityComposerResult({
    required this.body,
    required this.visibility,
  });

  final String body;
  final String visibility;
}

Future<CommunityComposerResult?> showCommunityComposerSheet(BuildContext context) {
  final controller = TextEditingController();
  String visibility = 'community';
  bool isSubmitting = false;

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
                  items: const [
                    DropdownMenuItem(value: 'community', child: Text('Members only')),
                    DropdownMenuItem(value: 'public', child: Text('Public')),
                    DropdownMenuItem(value: 'paid', child: Text('Paid tiers')),
                  ],
                  onChanged: isSubmitting
                      ? null
                      : (value) {
                          if (value == null) {
                            return;
                          }
                          setState(() => visibility = value);
                        },
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: controller,
                  maxLines: 6,
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
                        : () {
                            final text = controller.text.trim();
                            if (text.isEmpty) {
                              ScaffoldMessenger.of(context).showSnackBar(
                                const SnackBar(content: Text('Write something before posting.')),
                              );
                              return;
                            }
                            setState(() => isSubmitting = true);
                            Navigator.of(context).pop(
                              CommunityComposerResult(body: text, visibility: visibility),
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
