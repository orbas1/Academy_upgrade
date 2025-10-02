import 'package:flutter/foundation.dart';
import 'package:flutter/material.dart';

import '../../services/acceptance/acceptance_report_service.dart';

class AcceptanceReportScreen extends StatefulWidget {
  const AcceptanceReportScreen({super.key});

  @override
  State<AcceptanceReportScreen> createState() => _AcceptanceReportScreenState();
}

class _AcceptanceReportScreenState extends State<AcceptanceReportScreen> {
  final AcceptanceReportService _service = AcceptanceReportService();

  AcceptanceReportCache? _cache;
  bool _isLoading = false;
  String? _errorMessage;
  bool _usingCacheFallback = false;

  AcceptanceReportModel? get _report => _cache?.report;

  @override
  void initState() {
    super.initState();
    _loadReport();
  }

  Future<void> _loadReport() async {
    setState(() {
      _isLoading = true;
      _errorMessage = null;
      _usingCacheFallback = false;
    });

    try {
      final result = await _service.synchronize();
      if (!mounted) {
        return;
      }
      setState(() {
        _cache = result.cache;
      });
    } catch (error, stackTrace) {
      debugPrint('AcceptanceReportScreen> failed to refresh: $error');
      debugPrint('$stackTrace');
      final cache = await _service.loadCache();
      if (!mounted) {
        return;
      }
      if (cache != null) {
        setState(() {
          _cache = cache;
          _usingCacheFallback = true;
          _errorMessage = 'Showing cached acceptance data; retry to refresh.';
        });
      } else {
        setState(() {
          _errorMessage = 'Unable to load acceptance report. Please try again.';
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

  @override
  Widget build(BuildContext context) {
    final report = _report;

    return Scaffold(
      appBar: AppBar(
        title: const Text('Acceptance Report'),
      ),
      body: RefreshIndicator(
        onRefresh: _loadReport,
        child: _buildBody(context, report),
      ),
    );
  }

  Widget _buildBody(BuildContext context, AcceptanceReportModel? report) {
    if (_isLoading && report == null && _errorMessage == null) {
      return const ListView(
        children: [
          SizedBox(
            height: 320,
            child: Center(child: CircularProgressIndicator()),
          ),
        ],
      );
    }

    if (_errorMessage != null && report == null) {
      return ListView(
        padding: const EdgeInsets.all(24),
        children: [
          Text(
            'Acceptance report unavailable',
            style: Theme.of(context).textTheme.titleLarge,
          ),
          const SizedBox(height: 12),
          Text(_errorMessage ?? 'Unexpected error'),
          const SizedBox(height: 16),
          ElevatedButton(
            onPressed: _loadReport,
            child: const Text('Retry'),
          ),
        ],
      );
    }

    if (report == null) {
      return ListView(
        padding: const EdgeInsets.all(24),
        children: const [
          Text('No acceptance report has been generated yet.'),
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
                    _errorMessage ?? 'Showing cached acceptance results.',
                    style: const TextStyle(color: Colors.orange),
                  ),
                ),
              ],
            ),
          ),
        _buildHeader(context, report),
        const SizedBox(height: 16),
        _buildSummaryGrid(context, report.summary),
        const SizedBox(height: 24),
        Text(
          'Requirements',
          style: Theme.of(context).textTheme.titleMedium,
        ),
        const SizedBox(height: 8),
        ...report.requirements.map(_buildRequirementCard),
      ],
    );
  }

  Widget _buildHeader(BuildContext context, AcceptanceReportModel report) {
    final theme = Theme.of(context);
    final generatedAt = report.generatedAt.toLocal();

    return Card(
      elevation: 1,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              'Stage acceptance coverage',
              style: theme.textTheme.titleMedium,
            ),
            const SizedBox(height: 8),
            Text(
              'Generated ${generatedAt.toString()}',
              style: theme.textTheme.bodySmall,
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildSummaryGrid(BuildContext context, AcceptanceSummaryModel summary) {
    final theme = Theme.of(context);
    final entries = [
      _SummaryEntry('Completion', '${summary.completion.toStringAsFixed(2)}%'),
      _SummaryEntry('Quality', '${summary.quality.toStringAsFixed(2)}%'),
      _SummaryEntry('Requirements', '${summary.requirementsPassed}/${summary.requirementsTotal}'),
      _SummaryEntry(
        'Checks',
        '${summary.checksPassed.toStringAsFixed(0)}/${summary.checksTotal.toStringAsFixed(0)}',
      ),
    ];

    return GridView.count(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      crossAxisCount: 2,
      crossAxisSpacing: 12,
      mainAxisSpacing: 12,
      childAspectRatio: 3,
      children: entries
          .map(
            (entry) => Card(
              color: theme.colorScheme.surfaceVariant,
              child: Padding(
                padding: const EdgeInsets.all(12),
                child: Column(
                  mainAxisAlignment: MainAxisAlignment.center,
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      entry.label,
                      style: theme.textTheme.bodySmall,
                    ),
                    const SizedBox(height: 4),
                    Text(
                      entry.value,
                      style: theme.textTheme.titleMedium?.copyWith(fontWeight: FontWeight.bold),
                    ),
                  ],
                ),
              ),
            ),
          )
          .toList(growable: false),
    );
  }

  Widget _buildRequirementCard(AcceptanceRequirementModel requirement) {
    final statusColor = requirement.status.toLowerCase() == 'pass'
        ? Colors.green
        : requirement.status.toLowerCase() == 'fail'
            ? Colors.red
            : Colors.blueGrey;

    return Card(
      margin: const EdgeInsets.only(bottom: 12),
      child: ExpansionTile(
        tilePadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        title: Text(requirement.title),
        subtitle: Wrap(
          crossAxisAlignment: WrapCrossAlignment.center,
          spacing: 12,
          children: [
            Chip(
              label: Text(requirement.status.toUpperCase()),
              backgroundColor: statusColor.withOpacity(0.15),
              labelStyle: TextStyle(color: statusColor, fontWeight: FontWeight.bold),
            ),
            Text('Completion ${requirement.completion.toStringAsFixed(2)}%'),
            Text('Quality ${requirement.quality.toStringAsFixed(2)}%'),
          ],
        ),
        childrenPadding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
        children: [
          if (requirement.description.isNotEmpty)
            Padding(
              padding: const EdgeInsets.only(bottom: 8),
              child: Text(requirement.description),
            ),
          if (requirement.tags.isNotEmpty)
            Padding(
              padding: const EdgeInsets.only(bottom: 12),
              child: Wrap(
                spacing: 8,
                runSpacing: 4,
                children: requirement.tags
                    .map((tag) => Chip(
                          label: Text(tag),
                          backgroundColor: Colors.blueGrey.withOpacity(0.1),
                        ))
                    .toList(growable: false),
              ),
            ),
          Text(
            'Checks',
            style: Theme.of(context).textTheme.titleSmall,
          ),
          const SizedBox(height: 8),
          ...requirement.checks.map(_buildCheckRow),
          if (requirement.evidence.isNotEmpty) ...[
            const SizedBox(height: 12),
            Text(
              'Evidence',
              style: Theme.of(context).textTheme.titleSmall,
            ),
            const SizedBox(height: 8),
            ...requirement.evidence.map(
              (evidence) => ListTile(
                dense: true,
                contentPadding: EdgeInsets.zero,
                leading: const Icon(Icons.link, size: 18),
                title: Text(evidence.identifier),
                subtitle: Text('Type: ${evidence.type}'),
              ),
            ),
          ],
        ],
      ),
    );
  }

  Widget _buildCheckRow(AcceptanceCheckModel check) {
    final statusColor = check.passed ? Colors.green : Colors.red;

    return Padding(
      padding: const EdgeInsets.only(bottom: 6),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(
            check.passed ? Icons.check_circle : Icons.cancel,
            size: 18,
            color: statusColor,
          ),
          const SizedBox(width: 8),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text('${check.type} · ${check.identifier}', style: const TextStyle(fontWeight: FontWeight.w600)),
                Text('Weight ${check.weight.toStringAsFixed(2)}'),
                if (check.message != null && check.message!.isNotEmpty)
                  Text(
                    check.message!,
                    style: const TextStyle(color: Colors.redAccent),
                  ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _SummaryEntry {
  const _SummaryEntry(this.label, this.value);

  final String label;
  final String value;
}
