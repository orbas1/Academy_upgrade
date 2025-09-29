<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\Storage\RestoreRequest;
use App\Services\Storage\LifecycleManager;
use Illuminate\Http\JsonResponse;

class StorageController extends Controller
{
    public function __construct(private readonly LifecycleManager $lifecycleManager)
    {
        $this->middleware(['auth:sanctum']);
    }

    public function requestRestore(RestoreRequest $request): JsonResponse
    {
        $profile = $request->input('profile', 'media');
        $objectKey = $request->string('key')->toString();
        $days = (int) $request->input('days', 2);

        $this->lifecycleManager->requestRestore($profile, $objectKey, $days);

        return response()->json([
            'status' => 'accepted',
            'message' => 'Restore request submitted. Retrieval may take up to 12 hours depending on storage tier.',
        ]);
    }
}
