<?php

namespace App\Console\Commands;

use App\Services\Queue\QueueMetricsFetcher;
use App\Services\Queue\QueueMetricsSnapshot;
use App\Services\Systemd\EnvironmentFileEditor;
use App\Services\Systemd\SystemdServiceManager;
use Illuminate\Console\Command;
use Illuminate\Contracts\Config\Repository as ConfigRepository;
use Illuminate\Support\Collection;
use Psr\Log\LoggerInterface;

class QueueAutoscaleCommand extends Command
{
    protected $signature = 'queues:autoscale {--dry-run : Evaluate scaling actions without touching systemd.}';

    protected $description = 'Adjust Horizon supervisor concurrency based on queue backlogs.';

    public function __construct(
        private readonly QueueMetricsFetcher $metricsFetcher,
        private readonly EnvironmentFileEditor $environmentFileEditor,
        private readonly SystemdServiceManager $systemdServiceManager,
        private readonly ConfigRepository $config,
        private readonly LoggerInterface $logger,
    ) {
        parent::__construct();
    }

    public function handle(): int
    {
        $profiles = $this->config->get('queue-autoscale.queues', []);
        if (empty($profiles)) {
            $this->info('No queue autoscale profiles configured.');

            return self::SUCCESS;
        }

        $connection = (string) $this->config->get('queue-monitor.connection', 'horizon');
        $queueNames = [];

        foreach ($profiles as $profileName => $profile) {
            $queueNames[$profileName] = (string) ($profile['queue'] ?? $profileName);
        }

        $snapshots = $this->metricsFetcher->fetch(array_values($queueNames), $connection);
        $metrics = $this->mapSnapshots($snapshots);

        foreach ($profiles as $name => $profile) {
            $this->processProfile($name, $profile, $metrics[$queueNames[$name]] ?? null);
        }

        return self::SUCCESS;
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function processProfile(string $profileName, array $profile, ?QueueMetricsSnapshot $snapshot): void
    {
        $pending = $snapshot?->pendingJobs ?? 0;
        $oldestPending = $snapshot?->oldestPendingSeconds ?? null;
        $minDefault = (int) $this->config->get('queue-autoscale.default_min_processes', 1);
        $maxDefault = (int) $this->config->get('queue-autoscale.default_max_processes', 16);

        $minProcesses = max(1, (int) ($profile['min_processes'] ?? $minDefault));
        $maxProcesses = max($minProcesses, (int) ($profile['max_processes'] ?? $maxDefault));
        $targetProcesses = $this->determineProcessCount($profile, $pending, $oldestPending, $minProcesses, $maxProcesses);

        $envFile = (string) ($profile['env_file'] ?? null);
        $service = (string) ($profile['service'] ?? null);

        if ($envFile === '' || $service === '') {
            $this->warn(sprintf('Profile [%s] missing env_file or service declaration.', $profileName));

            return;
        }

        $currentValues = $this->environmentFileEditor->read($envFile);
        $currentMax = isset($currentValues['HORIZON_MAX_PROCESSES']) ? (int) $currentValues['HORIZON_MAX_PROCESSES'] : null;
        $currentMin = isset($currentValues['HORIZON_MIN_PROCESSES']) ? (int) $currentValues['HORIZON_MIN_PROCESSES'] : null;

        $nextValues = array_merge($currentValues, [
            'HORIZON_QUEUE' => $profile['queue'] ?? $profileName,
            'HORIZON_MAX_PROCESSES' => $targetProcesses,
            'HORIZON_MIN_PROCESSES' => max(1, min($targetProcesses, $minProcesses)),
            'HORIZON_BALANCE' => $currentValues['HORIZON_BALANCE'] ?? 'auto',
            'HORIZON_AUTO_SCALING_STRATEGY' => $currentValues['HORIZON_AUTO_SCALING_STRATEGY'] ?? 'time',
        ]);

        if ($currentMax === $targetProcesses && $currentMin === $nextValues['HORIZON_MIN_PROCESSES']) {
            $this->line(sprintf('[%s] No scaling action required (pending: %d).', $profileName, $pending));

            return;
        }

        $this->info(sprintf('[%s] scaling to %d workers (pending: %d, oldest: %s).',
            $profileName,
            $targetProcesses,
            $pending,
            $oldestPending !== null ? $oldestPending . 's' : 'n/a'
        ));

        if ($this->option('dry-run')) {
            return;
        }

        $this->environmentFileEditor->write($envFile, $nextValues);

        try {
            $this->systemdServiceManager->reload($service);
        } catch (\Throwable $exception) {
            $this->logger->error('Failed to reload Horizon supervisor.', [
                'service' => $service,
                'exception' => $exception,
            ]);
            $this->error(sprintf('Failed to reload %s: %s', $service, $exception->getMessage()));
        }
    }

    /**
     * @param  array<string, mixed>  $profile
     */
    private function determineProcessCount(array $profile, int $pending, ?int $oldestPending, int $minProcesses, int $maxProcesses): int
    {
        $scale = $profile['scale'] ?? [];
        if (! is_array($scale) || $scale === []) {
            return $minProcesses;
        }

        $target = $minProcesses;

        foreach ($scale as $step) {
            $pendingThreshold = (int) ($step['pending'] ?? 0);
            $processes = (int) ($step['processes'] ?? $minProcesses);
            $ageThreshold = isset($step['oldest_pending']) ? (int) $step['oldest_pending'] : null;

            $pendingTriggered = $pending >= $pendingThreshold;
            $ageTriggered = $ageThreshold !== null && $oldestPending !== null && $oldestPending >= $ageThreshold;

            if ($pendingTriggered || $ageTriggered) {
                $target = max($target, $processes);
            }
        }

        return max($minProcesses, min($target, $maxProcesses));
    }

    /**
     * @param  array<int, QueueMetricsSnapshot>  $snapshots
     * @return array<string, QueueMetricsSnapshot>
     */
    private function mapSnapshots(array $snapshots): array
    {
        $collection = Collection::make($snapshots);

        return $collection->keyBy(fn (QueueMetricsSnapshot $snapshot) => $snapshot->queueName)->all();
    }
}
