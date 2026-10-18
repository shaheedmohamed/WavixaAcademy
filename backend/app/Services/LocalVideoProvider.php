<?php

namespace App\Services;

use App\Models\VideoAsset;
use Illuminate\Support\Facades\Storage;

/**
 * Local provider — videos uploaded directly to this server, encoded to
 * HLS by ProcessLocalVideoJob, stored privately under storage/app/videos/
 * and streamed through session-bound signed URLs. No external service
 * involved; CourseShield session validity is the authorization gate.
 */
class LocalVideoProvider implements VideoProviderInterface
{
    public function isConfigured(): bool
    {
        return true;
    }

    public function createDirectUpload(array $meta = []): array
    {
        return ['upload_id' => '', 'upload_url' => ''];
    }

    public function getAsset(string $assetId): array
    {
        return [];
    }

    public function getAssetForUpload(string $uploadId): ?string
    {
        return null;
    }

    /**
     * The "token" embeds the video session id — the stream endpoint
     * re-validates the live session on every segment request, so an
     * ended/expired/terminated session kills playback instantly.
     */
    public function playbackToken(VideoAsset $asset, int $userId, int $sessionId, int $ttlSeconds): ?string
    {
        return (string) $sessionId;
    }

    /**
     * Signed path-prefix URL: /api/video/stream/{asset}/{sid}/{sig}/index.m3u8
     * Relative segment references inside the playlist resolve under the
     * same signed prefix automatically.
     */
    public function manifestUrl(string $playbackId, ?string $token = null): string
    {
        $sessionId = (int) $token;
        $sig = self::signature((int) $playbackId, $sessionId);

        return url("/api/video/stream/{$playbackId}/{$sessionId}/{$sig}/index.m3u8");
    }

    public static function signature(int $assetId, int $sessionId): string
    {
        return hash_hmac('sha256', "local-stream:{$assetId}:{$sessionId}", (string) config('app.key'));
    }

    public function deleteAsset(string $assetId): bool
    {
        return Storage::disk('local')->deleteDirectory("videos/{$assetId}");
    }
}
