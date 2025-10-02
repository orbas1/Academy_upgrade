<?php

declare(strict_types=1);

namespace App\Console\Commands;

use App\Support\Acceptance\AcceptanceReport;
use App\Support\Acceptance\AcceptanceReportService;
use Illuminate\Console\Command;
use Illuminate\Support\Str;

final class AcceptanceReportCommand extends Command
{
    private const JSON_FLAGS = JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE;

    /**
     * @var string
     */
    protected $signature = 'acceptance:report {--format=table : Output format (table,json)}';

    /**
     * @var string
     */
    protected $description = 'Render the staged acceptance report with completion and quality metrics.';

    public function __construct(private readonly AcceptanceReportService $service)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $report = $this->service->generate();

        $format = strtolower((string) $this->option('format'));

        if ($format === 'json') {
            $this->line(json_encode($report->toArray(), self::JSON_FLAGS));

            return self::SUCCESS;
        }

        $this->renderTable($report);

        return self::SUCCESS;
    }

    private function renderTable(AcceptanceReport $report): void
    {
        $summary = $report->summary;
        $this->components->twoColumnDetail('Generated at', $report->generatedAt);
        $this->components->twoColumnDetail('Completion', sprintf('%.2f%%', $summary['completion']));
        $this->components->twoColumnDetail('Quality', sprintf('%.2f%%', $summary['quality']));
        $this->components->twoColumnDetail('Requirements', sprintf('%d/%d', $summary['requirements_passed'], $summary['requirements_total']));
        $this->components->twoColumnDetail('Checks', sprintf('%.2f/%.2f', $summary['checks_passed'], $summary['checks_total']));

        $this->line('');

        $headers = ['ID', 'Title', 'Status', 'Completion', 'Quality', 'Tags'];
        $rows = array_map(function ($requirement) {
            return [
                $requirement['id'],
                $requirement['title'],
                Str::upper($requirement['status']),
                sprintf('%.2f%%', $requirement['completion']),
                sprintf('%.2f%%', $requirement['quality']),
                implode(', ', $requirement['tags']),
            ];
        }, $report->toArray()['requirements']);

        $this->table($headers, $rows);
    }
}
