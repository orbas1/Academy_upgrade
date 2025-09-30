<?php

namespace App\Http\Controllers\Api\V1\Security;

use App\Http\Controllers\Controller;
use App\Http\Resources\Security\DeviceSessionResource;
use App\Models\DeviceIp;
use App\Services\Security\DeviceTrustService;
use App\Services\Security\SessionTokenService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Gate;

class DeviceSessionController extends Controller
{
    public function __construct(
        private readonly DeviceTrustService $devices,
        private readonly SessionTokenService $tokens
    ) {
    }

    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        $currentToken = $user?->currentAccessToken();
        $currentDevice = $this->tokens->deviceForToken($currentToken);

        $sessions = DeviceIp::query()
            ->where('user_id', $user->id)
            ->orderByDesc('last_seen_at')
            ->get();

        return DeviceSessionResource::collection($sessions)
            ->additional([
                'meta' => [
                    'current_device_id' => $currentDevice?->id,
                ],
            ])
            ->response();
    }

    public function destroy(Request $request, DeviceIp $device): JsonResponse
    {
        $this->authorizeDevice($request, $device);

        $this->devices->removeDevice($device);

        return response()->json([
            'status' => 'ok',
        ]);
    }

    public function update(Request $request, DeviceIp $device): JsonResponse
    {
        $this->authorizeDevice($request, $device);

        $data = $request->validate([
            'trusted' => ['required', 'boolean'],
        ]);

        $this->devices->toggleTrust($device, (bool) $data['trusted']);

        return response()->json([
            'status' => 'ok',
            'device' => new DeviceSessionResource($device->fresh()),
        ]);
    }

    protected function authorizeDevice(Request $request, DeviceIp $device): void
    {
        $user = $request->user();
        if (! $user) {
            abort(401);
        }

        if ($device->user_id === $user->id) {
            return;
        }

        if (Gate::forUser($user)->allows('secrets.manage')) {
            return;
        }

        abort(403);
    }
}
