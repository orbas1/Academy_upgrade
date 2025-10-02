<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Ops;

use App\Http\Controllers\Controller;
use App\Support\Acceptance\AcceptanceReportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Gate;

final class AcceptanceReportController extends Controller
{
    public function __construct(private readonly AcceptanceReportService $service) {}

    public function __invoke(): JsonResponse
    {
        Gate::authorize('acceptance.report.view');

        $report = $this->service->generate();

        return response()->json([
            'data' => $report->toArray(),
        ]);
    }
}
