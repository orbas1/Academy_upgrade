<?php

namespace App\Http\Controllers\Api\V1\Admin;

use App\Domain\AdminPortal\CommunityModuleManifest;
use App\Domain\AdminPortal\CommunityPermissionResolver;
use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class CommunityModuleManifestController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $user = $request->user();

        abort_unless($user, 401);

        $permissionResolver = app(CommunityPermissionResolver::class);
        $manifest = CommunityModuleManifest::make()->build([], $permissionResolver->resolve($user));

        return response()->json([
            'data' => $manifest,
        ]);
    }
}
