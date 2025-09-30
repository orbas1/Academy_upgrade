<?php

namespace App\Http\Controllers\Admin;

use App\Domain\AdminPortal\CommunityModuleManifest;
use App\Domain\AdminPortal\CommunityPermissionResolver;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\URL;

class CommunitySpaController extends Controller
{
    public function __invoke(Request $request): View
    {
        $user = $request->user();

        abort_unless($user && $user->role === 'admin', 403);

        $permissionResolver = app(CommunityPermissionResolver::class);
        $grantedPermissions = $permissionResolver->resolve($user);

        $manifestBuilder = CommunityModuleManifest::make();
        $manifest = $manifestBuilder->build([], $grantedPermissions);

        $apiBase = rtrim(URL::to($manifest['api']['base_url']), '/');

        $context = [
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role,
                'permissions' => $grantedPermissions,
            ],
            'csrfToken' => csrf_token(),
            'locale' => app()->getLocale(),
            'timezone' => $user->timezone ?? config('app.timezone'),
            'featureFlags' => $manifest['feature_flags'],
            'spaBasePath' => $manifest['base_path'],
            'endpoints' => [
                'apiBaseUrl' => $apiBase,
                'manifestUrl' => URL::to($manifest['api']['manifest_endpoint']),
            ],
            'manifest' => [
                'version' => $manifest['version'],
                'generatedAt' => $manifest['generated_at'],
                'modules' => $manifest['modules'],
            ],
        ];

        return view('admin.community-app', [
            'appContext' => $context,
        ]);
    }
}
