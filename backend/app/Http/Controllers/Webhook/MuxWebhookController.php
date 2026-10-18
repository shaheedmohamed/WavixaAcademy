<?php

namespace App\Http\Controllers\Webhook;

use App\Http\Controllers\Controller;
use App\Models\VideoAsset;
use App\Services\MuxVideoProvider;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MuxWebhookController extends Controller
{
    public function handle(Request $request, MuxVideoProvider $mux): JsonResponse
    {
        $signature = (string) $request->header('Mux-Signature');
        $payload = $request->getContent();

        if (! $mux->verifyWebhookSignature($payload, $signature)) {
            return response()->json(['message' => 'invalid signature'], 401);
        }

        $type = $request->input('type');
        $data = $request->input('data', []);

        // Resolve our asset via passthrough or asset id.
        $assetId = null;
        $passthrough = $data['passthrough'] ?? null;
        if ($passthrough) {
            $decoded = json_decode($passthrough, true);
            $assetId = $decoded['video_asset_id'] ?? null;
        }
        $asset = $assetId
            ? VideoAsset::find($assetId)
            : VideoAsset::where('provider_asset_id', $data['id'] ?? '')->first();

        if (! $asset) {
            return response()->json(['ok' => true]);
        }

        match ($type) {
            'video.asset.ready' => $this->markReady($asset, $data),
            'video.asset.errored' => $asset->update([
                'status' => VideoAsset::STATUS_FAILED,
                'error_message' => 'فشلت المعالجة لدى المزود.',
            ]),
            'video.upload.asset_created' => $asset->update(['provider_asset_id' => $data['asset_id'] ?? null]),
            default => null,
        };

        return response()->json(['ok' => true]);
    }

    private function markReady(VideoAsset $asset, array $data): void
    {
        $playbackId = collect($data['playback_ids'] ?? [])
            ->firstWhere('policy', 'signed')['id']
            ?? ($data['playback_ids'][0]['id'] ?? null);

        $asset->update([
            'provider_asset_id' => $data['id'] ?? $asset->provider_asset_id,
            'status' => VideoAsset::STATUS_READY,
            'playback_id' => $playbackId,
            'duration_seconds' => (int) round((float) ($data['duration'] ?? 0)),
        ]);

        if ($asset->lesson && $asset->duration_seconds) {
            $asset->lesson->update(['duration_seconds' => $asset->duration_seconds]);
        }

        Log::info('Video asset ready via webhook', ['asset' => $asset->id]);
    }
}
