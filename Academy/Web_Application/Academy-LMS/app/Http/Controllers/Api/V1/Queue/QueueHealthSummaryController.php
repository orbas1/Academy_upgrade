<?php

namespace App\Http\Controllers\Api\V1\Queue;

use App\Http\Controllers\Controller;
use App\Models\QueueMetric;
use App\Services\Queue\QueueThresholdEvaluator;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class QueueHealthSummaryController extends Controller
{
    public function __construct(private readonly QueueThresholdEvaluator $evaluator)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $metrics = QueueMetric::query()
            ->orderByDesc('recorded_at')
            ->get()
            ->unique('queue_name')
            ->values();

        $isAdmin = $request->user()?->role === 'admin';

        $data = $metrics->map(function (QueueMetric $metric) use ($isAdmin) {
            $result = $this->evaluator->evaluate($metric);

            $payload = [
                'queue' => $metric->queue_name,
                'status' => $result->status,
                'updated_at' => optional($metric->recorded_at)->toIso8601String(),
            ];

            if ($result->publicMessage !== null) {
                $payload['public_message'] = $result->publicMessage;
            }

            if (! empty($result->alerts)) {
                $payload['alerts'] = $result->alerts;
            }

            if ($isAdmin) {
                $payload['metrics'] = [
                    'pending_jobs' => $metric->pending_jobs,
                    'reserved_jobs' => $metric->reserved_jobs,
                    'delayed_jobs' => $metric->delayed_jobs,
                    'oldest_pending_seconds' => $metric->oldest_pending_seconds,
                    'oldest_reserved_seconds' => $metric->oldest_reserved_seconds,
                    'oldest_delayed_seconds' => $metric->oldest_delayed_seconds,
                    'backlog_delta_per_minute' => $metric->backlog_delta_per_minute,
                ];
                $payload['thresholds'] = $result->thresholds;
            }

            return $payload;
        });

        return response()->json([
            'data' => $data,
            'generated_at' => now()->toIso8601String(),
        ]);
    }
}
