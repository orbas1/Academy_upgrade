<?php

declare(strict_types=1);

namespace App\Http\Controllers\Api\V1\Profile;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class AnalyticsConsentController extends Controller
{
    public function __invoke(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'granted' => ['required', 'boolean'],
        ]);

        $user = $request->user();

        if (! $user) {
            abort(401);
        }

        if ($validated['granted']) {
            $user->grantAnalyticsConsent();
        } else {
            $user->revokeAnalyticsConsent();
        }

        return response()->json([
            'granted' => $validated['granted'],
            'version' => $user->analytics_consent_version,
            'granted_at' => optional($user->analytics_consent_at)->toIso8601String(),
            'revoked_at' => optional($user->analytics_consent_revoked_at)->toIso8601String(),
        ]);
    }
}
