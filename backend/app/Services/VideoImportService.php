<?php

namespace App\Services;

use App\Jobs\ImportVideoFromDriveJob;
use App\Models\Lesson;
use App\Models\User;
use App\Models\VideoAsset;
use RuntimeException;

class VideoImportService
{
    public function __construct(
        private GoogleDriveClient $drive,
        private AuditLogger $audit,
    ) {}

    /**
     * Validate a Drive URL and create a pending asset; the heavy transfer
     * runs in ImportVideoFromDriveJob on the queue.
     */
    public function queueImport(string $driveUrl, Lesson $lesson, User $admin): VideoAsset
    {
        $fileId = GoogleDriveClient::extractFileId($driveUrl);
        if (! $fileId) {
            throw new RuntimeException('رابط Google Drive غير صالح.');
        }

        if (! $this->drive->isConfigured()) {
            throw new RuntimeException('تكامل Google Drive غير مُعد. أضف بيانات حساب الخدمة في ملف البيئة.');
        }

        // Quick readability check before queuing.
        $meta = $this->drive->fileMetadata($fileId);
        if (! str_starts_with((string) ($meta['mimeType'] ?? ''), 'video/')) {
            throw new RuntimeException('الملف في Google Drive ليس ملف فيديو.');
        }

        $asset = VideoAsset::create([
            'lesson_id' => $lesson->id,
            'provider' => config('services.mux.token_id') ? 'mux' : 'none',
            'google_file_id' => $fileId,
            'source_filename' => $meta['name'] ?? null,
            'file_size_bytes' => isset($meta['size']) ? (int) $meta['size'] : null,
            'status' => VideoAsset::STATUS_PENDING,
            'imported_by' => $admin->id,
        ]);

        $lesson->update(['video_asset_id' => $asset->id]);

        ImportVideoFromDriveJob::dispatch($asset->id);

        $this->audit->log('video.import_queued', $asset, ['file' => $meta['name'] ?? $fileId]);

        return $asset;
    }
}
