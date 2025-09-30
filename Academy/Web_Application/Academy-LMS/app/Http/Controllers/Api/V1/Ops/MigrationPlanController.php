<?php

namespace App\Http\Controllers\Api\V1\Ops;

use App\Http\Controllers\Controller;
use App\Support\Migrations\MigrationPlanner;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class MigrationPlanController extends Controller
{
    public function __construct(private readonly MigrationPlanner $planner)
    {
    }

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('migration.plan.view');

        $phaseFilter = collect($request->query('phase', []))
            ->map(fn ($phase) => strtolower((string) $phase))
            ->filter()
            ->values()
            ->all();

        $plans = $this->planner->plans()
            ->map(function ($plan) use ($phaseFilter) {
                $payload = $plan->toArray();

                if ($phaseFilter !== []) {
                    $payload['phases'] = collect($payload['phases'])
                        ->filter(fn (array $phase) => in_array(Str::lower($phase['key']), $phaseFilter, true))
                        ->values()
                        ->all();
                }

                return $payload;
            })
            ->values();

        return response()->json([
            'data' => [
                'default_stability_window_days' => $this->planner->toArray()['default_stability_window_days'],
                'plans' => $plans,
            ],
        ]);
    }

    public function show(Request $request, string $planKey): JsonResponse
    {
        Gate::authorize('migration.plan.view');

        $plan = $this->planner->get($planKey);
        $payload = $plan->toArray();

        $phaseFilter = collect($request->query('phase', []))
            ->map(fn ($phase) => strtolower((string) $phase))
            ->filter()
            ->values()
            ->all();

        if ($phaseFilter !== []) {
            $payload['phases'] = collect($payload['phases'])
                ->filter(fn (array $phase) => in_array(Str::lower($phase['key']), $phaseFilter, true))
                ->values()
                ->all();
        }

        return response()->json([
            'data' => $payload,
        ]);
    }
}
