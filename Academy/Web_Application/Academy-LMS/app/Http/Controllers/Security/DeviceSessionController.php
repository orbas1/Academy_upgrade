<?php

namespace App\Http\Controllers\Security;

use App\Http\Controllers\Controller;
use App\Models\DeviceIp;
use App\Services\Security\DeviceTrustService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Session;

class DeviceSessionController extends Controller
{
    public function __construct(private readonly DeviceTrustService $devices)
    {
    }

    public function destroy(Request $request, DeviceIp $device): RedirectResponse
    {
        $this->authorizeDevice($request, $device);

        $this->devices->removeDevice($device);
        Session::flash('status', get_phrase('The device has been signed out.'));

        return back();
    }

    public function updateTrust(Request $request, DeviceIp $device): RedirectResponse
    {
        $request->validate([
            'trusted' => ['required', 'boolean'],
        ]);

        $this->authorizeDevice($request, $device);

        $this->devices->toggleTrust($device, $request->boolean('trusted'));
        Session::flash('status', get_phrase('Device trust preferences have been updated.'));

        return back();
    }

    private function authorizeDevice(Request $request, DeviceIp $device): void
    {
        if ($request->user()->id !== $device->user_id && $request->user()->role !== 'admin') {
            abort(403);
        }
    }
}
