<?php

declare(strict_types=1);

namespace App\Http\Controllers\Testing;

use App\Domain\Communities\Services\CommunityEndToEndHarness;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;

class CommunityFlowTestController extends Controller
{
    public function __invoke(CommunityEndToEndHarness $harness): JsonResponse
    {
        $result = $harness->execute();

        return response()->json($result->toArray());
    }
}
