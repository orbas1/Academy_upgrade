<?php

namespace App\Http\Controllers\Api\V1\Security;

use App\Http\Controllers\Controller;
use App\Services\Security\UploadQuotaService;
use App\Support\Concerns\InteractsWithApiResponses;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class UploadQuotaController extends Controller
{
    use InteractsWithApiResponses;

    public function __construct(private readonly UploadQuotaService $quota)
    {
    }

    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();
        $communityId = $request->integer('community_id');
        if ($communityId <= 0) {
            $communityId = null;
        }

        $summary = $this->quota->summarize($user?->id, $communityId);

        return $this->respondWithData(
            $summary,
            [
                'quota_config' => config('security.uploads.quota'),
                'request_id' => $request->header('X-Request-Id'),
            ]
        );
    }
}
