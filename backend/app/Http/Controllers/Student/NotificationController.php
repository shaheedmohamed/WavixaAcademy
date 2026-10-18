<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        return response()->json([
            'notifications' => $request->user()->notifications()->latest()->paginate(20),
            'unread' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function markRead(Request $request): JsonResponse
    {
        $data = $request->validate([
            'ids' => ['nullable', 'array'],
            'ids.*' => ['string'],
            'all' => ['nullable', 'boolean'],
        ]);

        if ($data['all'] ?? false) {
            $request->user()->unreadNotifications->markAsRead();
        } elseif (! empty($data['ids'])) {
            $request->user()->unreadNotifications()
                ->whereIn('id', $data['ids'])->update(['read_at' => now()]);
        }

        return response()->json(['ok' => true]);
    }
}
