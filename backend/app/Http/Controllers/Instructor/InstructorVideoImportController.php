<?php

namespace App\Http\Controllers\Instructor;

use App\Http\Controllers\Controller;
use App\Jobs\ImportVideoFromDriveJob;
use App\Jobs\ProcessLocalVideoJob;
use App\Models\Lesson;
use App\Models\VideoAsset;
use App\Services\VideoImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class InstructorVideoImportController extends Controller
{
    public function __construct(private VideoImportService $imports) {}

    public function import(Request $request, Lesson $lesson): JsonResponse
    {
        $this->authorizeLesson($lesson);

        $data = $request->validate([
            'drive_url' => ['required', 'string', 'max:1000'],
        ]);

        try {
            $asset = $this->imports->queueImport($data['drive_url'], $lesson, $request->user());
        } catch (\RuntimeException $e) {
            return response()->json(['message' => $e->getMessage()], 422);
        }

        return response()->json(['asset' => $asset], 202);
    }

    public function upload(Request $request, Lesson $lesson): JsonResponse
    {
        $this->authorizeLesson($lesson);

        $request->validate([
            'video' => ['required', 'file', 'mimetypes:video/mp4,video/quicktime,video/x-msvideo,video/x-matroska,video/webm,video/mpeg', 'max:4194304'],
        ]);

        $file = $request->file('video');
        $relPath = 'video-source/'.Str::uuid().'.'.$file->getClientOriginalExtension();
        $file->storeAs('', $relPath, 'local');

        $asset = VideoAsset::create([
            'lesson_id' => $lesson->id,
            'provider' => 'local',
            'source_filename' => $file->getClientOriginalName(),
            'status' => VideoAsset::STATUS_PENDING,
            'imported_by' => $request->user()->id,
            'meta' => ['source_path' => $relPath],
        ]);

        $lesson->update(['video_asset_id' => $asset->id]);

        ProcessLocalVideoJob::dispatch($asset->id);

        return response()->json(['asset' => $asset], 202);
    }

    private function authorizeLesson(Lesson $lesson): void
    {
        $course = $lesson->module?->course;

        if (! $course || $course->instructor_id != auth()->id()) {
            abort(403, 'غير مصرح بالتعامل مع هذا الدرس.');
        }
    }
}
