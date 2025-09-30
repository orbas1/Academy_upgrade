import 'package:flutter/material.dart';

import '../../services/migration/migration_runbook_service.dart';

class MigrationRunbookScreen extends StatefulWidget {
  const MigrationRunbookScreen({super.key});

  @override
  State<MigrationRunbookScreen> createState() => _MigrationRunbookScreenState();
}

class _MigrationRunbookScreenState extends State<MigrationRunbookScreen> {
  final MigrationRunbookService _service = MigrationRunbookService();

  MigrationRunbookCache? _cache;
  String? _selectedRunbookKey;
  bool _isLoading = false;
  String? _errorMessage;
  bool _usingCacheFallback = false;

  @override
  void initState() {
    super.initState();
    _loadRunbooks();
  }

  Future<void> _loadRunbooks() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
      _usingCacheFallback = false;
    });

    try {
      final result = await _service.synchronize();
      if (!mounted) return;
      setState(() {
        _cache = result.cache;
        _selectedRunbookKey = _selectedRunbookKey ??
            (result.cache.runbooks.isNotEmpty ? result.cache.runbooks.first.key : null);
      });
    } catch (error, stackTrace) {
      debugPrint('MigrationRunbookScreen> failed to refresh: $error');
      debugPrint('$stackTrace');
      final cache = await _service.loadCache();
      if (!mounted) return;
      if (cache != null) {
        setState(() {
          _cache = cache;
          _selectedRunbookKey = _selectedRunbookKey ??
              (cache.runbooks.isNotEmpty ? cache.runbooks.first.key : null);
          _usingCacheFallback = true;
          _errorMessage = 'Showing cached runbook data; retry to refresh.';
        });
      } else {
        setState(() {
          _errorMessage = 'Unable to load migration runbooks. Please try again.';
        });
      }
    } finally {
      if (mounted) {
        setState(() {
          _isLoading = false;
        });
      }
    }
  }

  MigrationRunbookModel? get _selectedRunbook {
    final runbooks = _cache?.runbooks ?? <MigrationRunbookModel>[];
    if (runbooks.isEmpty) {
      return null;
    }
    final key = _selectedRunbookKey;
    if (key == null) {
      return runbooks.first;
    }
    return runbooks.firstWhere(
      (MigrationRunbookModel runbook) => runbook.key == key,
      orElse: () => runbooks.first,
    );
  }

  @override
  Widget build(BuildContext context) {
    final runbooks = _cache?.runbooks ?? <MigrationRunbookModel>[];

    return Scaffold(
      appBar: AppBar(
        title: const Text('Migration Runbook'),
      ),
      body: RefreshIndicator(
        onRefresh: _loadRunbooks,
        child: _buildBody(context, runbooks),
      ),
    );
  }

  Widget _buildBody(BuildContext context, List<MigrationRunbookModel> runbooks) {
    if (_isLoading && runbooks.isEmpty && _errorMessage == null) {
      return const Center(child: CircularProgressIndicator());
    }

    if (_errorMessage != null && runbooks.isEmpty) {
      return ListView(
        children: [
          Padding(
            padding: const EdgeInsets.all(24.0),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  'Migration runbooks are unavailable',
                  style: Theme.of(context).textTheme.titleLarge,
                ),
                const SizedBox(height: 12),
                Text(_errorMessage ?? 'Unexpected error'),
                const SizedBox(height: 16),
                ElevatedButton(
                  onPressed: _loadRunbooks,
                  child: const Text('Retry'),
                ),
              ],
            ),
          ),
        ],
      );
    }

    final runbook = _selectedRunbook;
    if (runbook == null) {
      return ListView(
        children: const [
          Padding(
            padding: EdgeInsets.all(24.0),
            child: Text('No migration runbooks have been published yet.'),
          ),
        ],
      );
    }

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        if (_usingCacheFallback)
          Padding(
            padding: const EdgeInsets.only(bottom: 12),
            child: Row(
              children: [
                const Icon(Icons.info_outline, color: Colors.orange),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    _errorMessage ?? 'Showing cached data',
                    style: const TextStyle(color: Colors.orange),
                  ),
                ),
              ],
            ),
          ),
        if (runbooks.length > 1)
          DropdownButton<String>(
            value: runbook.key,
            isExpanded: true,
            onChanged: (String? value) {
              setState(() {
                _selectedRunbookKey = value;
              });
            },
            items: runbooks
                .map(
                  (MigrationRunbookModel item) => DropdownMenuItem<String>(
                    value: item.key,
                    child: Text(item.name),
                  ),
                )
                .toList(growable: false),
          ),
        if (runbook.description.isNotEmpty)
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 12),
            child: Text(runbook.description),
          ),
        _buildMetadataChips(context, runbook),
        const SizedBox(height: 16),
        ...runbook.steps.map(_buildStepCard),
        const SizedBox(height: 32),
        if (_cache?.defaultMaintenanceWindowMinutes != null)
          Text(
            'Default maintenance window: ${_cache!.defaultMaintenanceWindowMinutes} minutes',
            style: Theme.of(context).textTheme.bodySmall,
          ),
      ],
    );
  }

  Widget _buildMetadataChips(BuildContext context, MigrationRunbookModel runbook) {
    final chips = <Widget>[];

    if (runbook.planKey.isNotEmpty) {
      chips.add(_buildChip(context, 'Plan: ${runbook.planKey}'));
    }
    if (runbook.maintenanceWindowMinutes != null) {
      chips.add(_buildChip(
        context,
        'Window: ${runbook.maintenanceWindowMinutes} min',
        icon: Icons.schedule,
      ));
    }
    if (runbook.serviceOwner.isNotEmpty) {
      chips.add(_buildChip(context, 'Owners: ${runbook.serviceOwner.join(', ')}'));
    }
    if (runbook.approvers.isNotEmpty) {
      chips.add(_buildChip(context, 'Approvers: ${runbook.approvers.join(', ')}'));
    }
    if (runbook.communicationChannels.isNotEmpty) {
      chips.add(_buildChip(
        context,
        'Comm: ${runbook.communicationChannels.join(', ')}',
        icon: Icons.campaign_outlined,
      ));
    }

    if (chips.isEmpty) {
      return const SizedBox.shrink();
    }

    return Wrap(
      spacing: 8,
      runSpacing: 8,
      children: chips,
    );
  }

  Widget _buildChip(BuildContext context, String label, {IconData? icon}) {
    return Chip(
      avatar: icon != null ? Icon(icon, size: 18) : null,
      label: Text(label),
    );
  }

  Widget _buildStepCard(MigrationRunbookStepModel step) {
    return Card(
      margin: const EdgeInsets.only(bottom: 16),
      child: ExpansionTile(
        title: Text(step.name),
        subtitle: Text(_buildStepSubtitle(step)),
        childrenPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        children: [
          _buildStepMetaSection(step),
          _buildBulletSection('Prechecks', step.prechecks),
          _buildBulletSection('Execution', step.execution),
          _buildBulletSection('Verification', step.verification),
          _buildBulletSection('Rollback', step.rollback),
          if (step.telemetry.isNotEmpty)
            _buildBulletSection('Telemetry', step.telemetry),
          if (step.notes != null && step.notes!.isNotEmpty)
            Padding(
              padding: const EdgeInsets.only(top: 8),
              child: Row(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Icon(Icons.sticky_note_2_outlined, size: 18),
                  const SizedBox(width: 8),
                  Expanded(child: Text(step.notes!)),
                ],
              ),
            ),
        ],
      ),
    );
  }

  Widget _buildStepMetaSection(MigrationRunbookStepModel step) {
    final metaChips = <Widget>[];

    if (step.ownerRoles.isNotEmpty) {
      metaChips.add(_buildChip(
        context,
        'Owners: ${step.ownerRoles.join(', ')}',
        icon: Icons.badge_outlined,
      ));
    }
    if (step.maintenanceWindowMinutes != null) {
      metaChips.add(_buildChip(
        context,
        'Window: ${step.maintenanceWindowMinutes} min',
        icon: Icons.timer_outlined,
      ));
    }
    if (step.expectedRuntimeMinutes != null) {
      metaChips.add(_buildChip(
        context,
        'Runtime: ${step.expectedRuntimeMinutes} min',
        icon: Icons.hourglass_bottom,
      ));
    }
    if (step.dependencies.isNotEmpty) {
      metaChips.add(_buildChip(
        context,
        'Depends on: ${step.dependencies.join(', ')}',
        icon: Icons.link,
      ));
    }
    if (step.relatedMigrations.isNotEmpty) {
      metaChips.add(_buildChip(
        context,
        'Migrations: ${step.relatedMigrations.join(', ')}',
        icon: Icons.data_object,
      ));
    }
    if (step.relatedCommands.isNotEmpty) {
      metaChips.add(_buildChip(
        context,
        'Commands: ${step.relatedCommands.join(', ')}',
        icon: Icons.terminal_outlined,
      ));
    }

    if (metaChips.isEmpty) {
      return const SizedBox.shrink();
    }

    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Wrap(
        spacing: 8,
        runSpacing: 8,
        children: metaChips,
      ),
    );
  }

  Widget _buildBulletSection(String title, List<String> items) {
    return Padding(
      padding: const EdgeInsets.only(top: 8),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(
            title,
            style: Theme.of(context).textTheme.titleSmall,
          ),
          const SizedBox(height: 4),
          if (items.isEmpty)
            Padding(
              padding: const EdgeInsets.only(left: 16.0, bottom: 4),
              child: Text(
                'No items documented.',
                style: Theme.of(context).textTheme.bodySmall,
              ),
            )
          else
            ...items.map(
              (String item) => Padding(
                padding: const EdgeInsets.only(left: 16.0, bottom: 4),
                child: Row(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    const Text('• '),
                    Expanded(child: Text(item)),
                  ],
                ),
              ),
            ),
        ],
      ),
    );
  }

  String _buildStepSubtitle(MigrationRunbookStepModel step) {
    final segments = <String>[];
    if (step.type.isNotEmpty) {
      segments.add(step.type);
    }
    if (step.ownerRoles.isNotEmpty) {
      segments.add('Owners: ${step.ownerRoles.join(', ')}');
    }
    if (step.maintenanceWindowMinutes != null) {
      segments.add('${step.maintenanceWindowMinutes} min window');
    }
    return segments.join(' • ');
  }
}
