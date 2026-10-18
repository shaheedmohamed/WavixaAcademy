<?php

namespace App\Jobs;

use App\Models\VideoAsset;
use App\Services\GoogleDriveClient;
use App\Services\VideoProviderInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Drive → temp file → provider direct upload → poll for asset readiness.
 * The temp file is always removed in finally{} so originals never
 * accumulate on the VPS disk.
 */
class ImportVideoFromDriveJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 1800;
    public int $tries = 2;

    public function __construct(public int $assetId) {}

    public function handle(GoogleDriveClient $drive, VideoProviderInterface $provider): void
    {
        $asset = VideoAsset::find($this->assetId);
        if (! $asset || $asset->status === VideoAsset::STATUS_READY) {
            return;
        }

        $tmpPath = null;

        try {
            $asset->update(['status' => VideoAsset::STATUS_IMPORTING, 'error_message' => null]);

            $tmpPath = $drive->downloadToTemp($asset->google_file_id);

            $asset->update(['status' => VideoAsset::STATUS_PROCESSING]);

            $upload = $provider->createDirectUpload([
                'passthrough' => json_encode(['video_asset_id' => $asset->id]),
            ]);

            $asset->update(['provider_upload_id' => $upload['upload_id']]);

            $this->uploadFileToProvider($upload['upload_url'], $tmpPath);

            FinalizeVideoAssetJob::dispatch($asset->id, $upload['upload_id'])
                ->delay(now()->addSeconds(30));
        } catch (\Throwable $e) {
            $asset->update([
                'status' => VideoAsset::STATUS_FAILED,
                'error_message' => mb_substr($e->getMessage(), 0, 1000),
            ]);
            Log::error('Video import failed', ['asset' => $asset->id, 'error' => $e->getMessage()]);
            throw $e;
        } finally {
            if ($tmpPath && is_file($tmpPath)) {
                @unlink($tmpPath);
            }
        }
    }

    private function uploadFileToProvider(string $uploadUrl, string $filePath): void
    {
        $response = Http::timeout(1500)
            ->withBody(fopen($filePath, 'rb'), 'application/octet-stream')
            ->put($uploadUrl);

        $response->throw();
    }
}
