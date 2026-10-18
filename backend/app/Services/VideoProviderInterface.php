<?php

namespace App\Services;

use App\Models\VideoAsset;

interface VideoProviderInterface
{
    /**
     * Create a direct-upload slot; returns ['upload_id' => ..., 'upload_url' => ...].
     */
    public function createDirectUpload(array $meta = []): array;

    /**
     * Fetch asset status/details from the provider.
     */
    public function getAsset(string $assetId): array;

    /**
     * Get asset linked to an upload id.
     */
    public function getAssetForUpload(string $uploadId): ?string;

    /**
     * Build a signed playback token for a playback id.
     */
    public function playbackToken(VideoAsset $asset, int $userId, int $sessionId, int $ttlSeconds): ?string;

    /**
     * Manifest URL template for a playback id (signed providers embed {token}).
     */
    public function manifestUrl(string $playbackId, ?string $token = null): string;

    public function deleteAsset(string $assetId): bool;

    public function isConfigured(): bool;
}
