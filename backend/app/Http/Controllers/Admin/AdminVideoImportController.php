<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Jobs\ImportVideoFromDriveJob;
use App\Jobs\ProcessLocalVideoJob;
use App\Models\Lesson;
use App\Models\VideoAsset;
use App\Services\VideoImportService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class AdminVideoImportController extends Controller
{
    public function __construct(private VideoImportService $imports) {}

    public function index(Request $request): JsonResponse
    {
        $assets = VideoAsset::with(['lesson:id,title,module_id', 'lesson.module:id,title,course_id', 'importer:id,name'])
            ->latest()->paginate(20);

        return response()->json($assets);
    }

    public function import(Request $request, Lesson $lesson): JsonResponse
    {
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

    /**
     * Direct upload from the admin's device. The file is stored privately,
     * then ProcessLocalVideoJob transcodes it to HLS and deletes the source.
     */
    public function upload(Request $request, Lesson $lesson): JsonResponse
    {
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

    public function show(VideoAsset $asset): JsonResponse
    {
        $asset->load('lesson:id,title,module_id');

        return response()->json(['asset' => $asset]);
    }

    public function retry(VideoAsset $asset): JsonResponse
    {
        abort_if(! in_array($asset->status, [VideoAsset::STATUS_FAILED, VideoAsset::STATUS_PENDING]), 422);

        if ($asset->provider === 'local') {
            abort_unless(
                $asset->meta['source_path'] ?? null,
                422, 'الملف المصدر حُذف بعد المعالجة — ارفع الفيديو مجددًا.'
            );
            $asset->update(['status' => VideoAsset::STATUS_PENDING, 'error_message' => null]);
            ProcessLocalVideoJob::dispatch($asset->id);

            return response()->json(['asset' => $asset]);
        }

        $asset->update(['status' => VideoAsset::STATUS_PENDING, 'error_message' => null]);
        ImportVideoFromDriveJob::dispatch($asset->id);

        return response()->json(['asset' => $asset]);
    }
}
