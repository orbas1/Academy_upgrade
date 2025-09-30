<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\Observability;

use App\Http\Controllers\Controller;
use App\Http\Requests\Observability\StoreMobileMetricRequest;
use App\Support\Observability\ObservabilityManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class MobileMetricController extends Controller
{
    public function __construct(private readonly ObservabilityManager $observability)
    {
    }

    public function store(StoreMobileMetricRequest $request): JsonResponse
    {
        $payload = $request->validated();
        $metrics = $payload['metrics'] ?? [];
        $ingested = 0;

        foreach ($metrics as $metric) {
            if (($metric['name'] ?? '') !== 'http_request') {
                continue;
            }

            $this->observability->recordMobileHttpRequest($metric);
            $ingested++;
        }

        if ($ingested === 0) {
            Log::debug('Mobile metrics ingestion received no supported metrics', [
                'session_id' => $payload['session_id'] ?? null,
                'count' => count($metrics),
            ]);
        }

        return response()->json([
            'status' => 'accepted',
            'processed' => $ingested,
        ]);
    }
}
