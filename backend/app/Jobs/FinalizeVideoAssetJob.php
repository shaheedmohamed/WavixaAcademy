<?php

namespace App\Jobs;

use App\Models\VideoAsset;
use App\Services\VideoProviderInterface;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Polls the provider until the uploaded asset is encoded and ready.
 * Re-dispatches itself with delay up to ~30 minutes.
 */
class FinalizeVideoAssetJob implements ShouldQueue
{
    use Queueable;

    public int $timeout = 120;
    public int $tries = 1;

    public function __construct(
        public int $assetId,
        public string $uploadId,
        public int $attempt = 1,
    ) {}

    public function handle(VideoProviderInterface $provider): void
    {
        $asset = VideoAsset::find($this->assetId);
        if (! $asset || $asset->status === VideoAsset::STATUS_READY) {
            return;
        }

        $providerAssetId = $asset->provider_asset_id ?: $provider->getAssetForUpload($this->uploadId);

        if (! $providerAssetId) {
            $this->retryOrFail($asset, 'لم يُنشأ الـ asset بعد لدى المزود.');
            return;
        }

        $data = $provider->getAsset($providerAssetId);
        $asset->update(['provider_asset_id' => $providerAssetId]);

        $status = $data['status'] ?? null;

        if ($status === 'ready') {
            $playbackId = collect($data['playback_ids'] ?? [])
                ->firstWhere('policy', 'signed')['id']
                ?? ($data['playback_ids'][0]['id'] ?? null);

            $asset->update([
                'status' => VideoAsset::STATUS_READY,
                'playback_id' => $playbackId,
                'duration_seconds' => (int) round((float) ($data['duration'] ?? 0)),
                'meta' => array_merge($asset->meta ?? [], [
                    'aspect_ratio' => $data['aspect_ratio'] ?? null,
                    'max_resolution' => $data['max_stored_resolution'] ?? null,
                ]),
            ]);

            if ($asset->lesson && $asset->duration_seconds) {
                $asset->lesson->update(['duration_seconds' => $asset->duration_seconds]);
            }

            return;
        }

        if ($status === 'errored') {
            $asset->update([
                'status' => VideoAsset::STATUS_FAILED,
                'error_message' => 'فشلت معالجة الفيديو لدى المزود.',
            ]);

            return;
        }

        $this->retryOrFail($asset, 'ما زال الفيديو قيد المعالجة.');
    }

    private function retryOrFail(VideoAsset $asset, string $reason): void
    {
        if ($this->attempt >= 60) {
            $asset->update([
                'status' => VideoAsset::STATUS_FAILED,
                'error_message' => 'انتهت مهلة المعالجة: '.$reason,
            ]);

            return;
        }

        self::dispatch($asset->id, $this->uploadId, $this->attempt + 1)
            ->delay(now()->addSeconds(30));
    }
}
