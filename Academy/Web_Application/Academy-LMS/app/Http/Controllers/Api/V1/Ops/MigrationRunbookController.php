<?php

namespace App\Http\Controllers\Api\V1\Ops;

use App\Http\Controllers\Controller;
use App\Support\Migrations\MigrationRunbookRegistry;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\Str;

class MigrationRunbookController extends Controller
{
    public function __construct(private readonly MigrationRunbookRegistry $registry)
    {
    }

    public function index(Request $request): JsonResponse
    {
        Gate::authorize('migration.runbook.view');

        $stepFilter = collect($request->query('step', []))
            ->map(fn ($step) => strtolower((string) $step))
            ->filter()
            ->values()
            ->all();

        $runbooks = $this->registry->runbooks()
            ->map(function ($runbook) use ($stepFilter) {
                $payload = $runbook->toArray();

                if ($stepFilter !== []) {
                    $payload['steps'] = collect($payload['steps'])
                        ->filter(fn (array $step) => in_array(Str::lower($step['key']), $stepFilter, true))
                        ->values()
                        ->all();
                }

                return $payload;
            })
            ->values();

        return response()->json([
            'data' => [
                'default_maintenance_window_minutes' => $this->registry->toArray()['default_maintenance_window_minutes'],
                'runbooks' => $runbooks,
            ],
        ]);
    }

    public function show(Request $request, string $runbookKey): JsonResponse
    {
        Gate::authorize('migration.runbook.view');

        $runbook = $this->registry->get($runbookKey);
        $payload = $runbook->toArray();

        $stepFilter = collect($request->query('step', []))
            ->map(fn ($step) => strtolower((string) $step))
            ->filter()
            ->values()
            ->all();

        if ($stepFilter !== []) {
            $payload['steps'] = collect($payload['steps'])
                ->filter(fn (array $step) => in_array(Str::lower($step['key']), $stepFilter, true))
                ->values()
                ->all();
        }

        return response()->json([
            'data' => $payload,
        ]);
    }
}
