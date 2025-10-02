<?php

declare(strict_types=1);

namespace Tests\Unit\Acceptance;

use App\Support\Acceptance\AcceptanceReportService;
use Illuminate\Filesystem\Filesystem;
use Tests\TestCase;

/**
 * @group data-protection
 */
final class AcceptanceReportServiceTest extends TestCase
{
    public function test_generate_returns_passed_requirements(): void
    {
        $service = new AcceptanceReportService(app(Filesystem::class));

        $report = $service->generate();
        $data = $report->toArray();

        $this->assertArrayHasKey('requirements', $data);
        $this->assertNotEmpty($data['requirements']);

        foreach ($data['requirements'] as $requirement) {
            $this->assertSame('pass', $requirement['status']);
            $this->assertEquals(100.0, $requirement['completion']);
            $this->assertEquals(100.0, $requirement['quality']);
        }

        $this->assertSame($data['summary']['requirements_total'], count($data['requirements']));
        $this->assertSame($data['summary']['requirements_total'], $data['summary']['requirements_passed']);
    }
}
