<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\UserDevice;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class DeviceController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $devices = $request->user()->devices()
            ->latest('last_seen_at')
            ->get(['id', 'device_id', 'name', 'browser', 'os', 'last_seen_at', 'first_seen_at', 'status']);

        return response()->json([
            'devices' => $devices,
            'limit' => (int) config('academy.max_devices', 2),
        ]);
    }

    public function destroy(Request $request, UserDevice $device): JsonResponse
    {
        abort_if($device->user_id !== $request->user()->id, 403);

        $device->update(['status' => 'revoked']);
        $request->user()->videoSessions()
            ->where('user_device_id', $device->id)
            ->where('status', 'active')
            ->update(['status' => 'terminated']);

        return response()->json(['ok' => true]);
    }
}
