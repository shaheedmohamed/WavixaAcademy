<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\Lesson;
use App\Services\CourseShield;
use App\Services\ProgressService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VideoController extends Controller
{
    public function __construct(
        private CourseShield $shield,
        private ProgressService $progress,
    ) {}

    public function start(Request $request): JsonResponse
    {
        $data = $request->validate([
            'lesson_id' => ['required', 'exists:lessons,id'],
            'device_id' => ['nullable', 'string', 'max:64'],
        ]);

        $lesson = Lesson::findOrFail($data['lesson_id']);
        $payload = $this->shield->authorizePlayback(
            $request->user(), $lesson, $data['device_id'] ?? null, $request
        );

        return response()->json($payload);
    }

    public function heartbeat(Request $request): JsonResponse
    {
        $data = $request->validate([
            'session_id' => ['required', 'integer'],
            'session_token' => ['required', 'string'],
            'position_seconds' => ['required', 'integer', 'min:0'],
            'duration_seconds' => ['nullable', 'integer', 'min:0'],
        ]);

        $session = $this->shield->heartbeat(
            $data['session_token'], (int) $data['session_id'],
            (int) $data['position_seconds'], $request
        );

        $this->progress->recordPosition(
            $request->user(), $session->lesson,
            (int) $data['position_seconds'], (int) ($data['duration_seconds'] ?? 0)
        );

        return response()->json([
            'ok' => true,
            'expires_at' => $session->expires_at->toIso8601String(),
        ]);
    }

    public function end(Request $request): JsonResponse
    {
        $data = $request->validate([
            'session_id' => ['required', 'integer'],
            'session_token' => ['required', 'string'],
        ]);

        $this->shield->endSession($data['session_token'], (int) $data['session_id']);

        return response()->json(['ok' => true]);
    }
}
