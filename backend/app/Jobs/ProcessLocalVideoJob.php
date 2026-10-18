<?php

namespace App\Jobs;

use App\Models\VideoAsset;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\Process\Process;

/**
 * Transcode an uploaded source video to HLS with libx264.
 *
 * CRF 20 + preset medium: visually transparent quality (no perceptible
 * loss) while typically cutting size 40-70% versus camera/editor exports.
 * The source file is deleted on success — only the compressed HLS output
 * is kept, as requested for disk savings.
 */
class ProcessLocalVideoJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 7200;
    public int $tries = 1;

    public function __construct(public int $assetId) {}

    public function handle(): void
    {
        $asset = VideoAsset::find($this->assetId);
        if (! $asset || $asset->status === VideoAsset::STATUS_READY) {
            return;
        }

        $sourceRel = $asset->meta['source_path'] ?? null;
        $source = $sourceRel ? Storage::disk('local')->path($sourceRel) : null;

        if (! $source || ! is_file($source)) {
            $asset->update([
                'status' => VideoAsset::STATUS_FAILED,
                'error_message' => 'الملف المصدر غير موجود على السيرفر.',
            ]);

            return;
        }

        $outDir = Storage::disk('local')->path("videos/{$asset->id}");
        if (! is_dir($outDir)) {
            mkdir($outDir, 0755, true);
        }

        $asset->update(['status' => VideoAsset::STATUS_PROCESSING, 'error_message' => null]);

        try {
            $duration = $this->probeDuration($source);

            $process = new Process([
                'ffmpeg', '-y', '-i', $source,
                '-vf', "scale='min(1920,iw)':-2",
                '-c:v', 'libx264', '-preset', 'medium', '-crf', '20',
                '-pix_fmt', 'yuv420p',
                '-c:a', 'aac', '-b:a', '128k', '-ac', '2',
                '-hls_time', '6',
                '-hls_playlist_type', 'vod',
                '-hls_segment_filename', $outDir.'/seg_%03d.ts',
                $outDir.'/index.m3u8',
            ]);
            $process->setTimeout(7200);
            $process->run();

            if (! $process->isSuccessful() || ! is_file($outDir.'/index.m3u8')) {
                throw new \RuntimeException('ffmpeg failed: '.mb_substr($process->getErrorOutput(), -500));
            }

            $size = collect(glob($outDir.'/*'))->sum(fn ($f) => filesize($f) ?: 0);

            $asset->update([
                'status' => VideoAsset::STATUS_READY,
                'provider' => 'local',
                'playback_id' => (string) $asset->id,
                'duration_seconds' => $duration,
                'file_size_bytes' => $size,
                'meta' => array_merge($asset->meta ?? [], ['hls_path' => "videos/{$asset->id}"]),
            ]);

            // Disk savings: drop the original upload, keep only compressed HLS.
            @unlink($source);
        } catch (\Throwable $e) {
            Log::error('Local video processing failed', ['asset' => $asset->id, 'error' => $e->getMessage()]);
            $asset->update([
                'status' => VideoAsset::STATUS_FAILED,
                'error_message' => 'فشلت معالجة الفيديو.',
            ]);

            throw $e;
        }
    }

    private function probeDuration(string $path): int
    {
        $process = new Process([
            'ffprobe', '-v', 'error', '-show_entries', 'format=duration',
            '-of', 'default=noprint_wrappers=1:nokey=1', $path,
        ]);
        $process->run();

        return (int) round((float) trim($process->getOutput()));
    }
}
