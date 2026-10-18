<?php

namespace App\Services;

use App\Models\VideoAsset;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class MuxVideoProvider implements VideoProviderInterface
{
    private const API = 'https://api.mux.com/video/v1';

    public function isConfigured(): bool
    {
        return (bool) (config('services.mux.token_id') && config('services.mux.token_secret'));
    }

    private function http()
    {
        return Http::withBasicAuth(
            (string) config('services.mux.token_id'),
            (string) config('services.mux.token_secret')
        )->timeout(30);
    }

    public function createDirectUpload(array $meta = []): array
    {
        $response = $this->http()->post(self::API.'/uploads', [
            'new_asset_settings' => [
                'playback_policy' => [config('services.mux.playback_policy', 'signed')],
                'passthrough' => $meta['passthrough'] ?? null,
            ],
            'cors_origin' => parse_url((string) config('app.url'), PHP_URL_HOST) ?: '*',
        ]);

        $response->throw();
        $data = $response->json('data');

        return [
            'upload_id' => $data['id'],
            'upload_url' => $data['url'],
        ];
    }

    public function getAssetForUpload(string $uploadId): ?string
    {
        $response = $this->http()->get(self::API."/uploads/{$uploadId}");

        return $response->json('data.asset_id');
    }

    public function getAsset(string $assetId): array
    {
        $response = $this->http()->get(self::API."/assets/{$assetId}");
        $response->throw();

        return $response->json('data') ?? [];
    }

    public function playbackToken(VideoAsset $asset, int $userId, int $sessionId, int $ttlSeconds): ?string
    {
        $keyId = config('services.mux.signing_key_id');
        $privateKey = config('services.mux.signing_key_private_key');

        if (! $keyId || ! $privateKey || ! $asset->playback_id) {
            return null;
        }

        $privateKey = str_replace('\\n', "\n", $privateKey);

        $header = ['alg' => 'RS256', 'typ' => 'JWT', 'kid' => $keyId];
        $payload = [
            'sub' => $asset->playback_id,
            'aud' => 'v',
            'exp' => time() + $ttlSeconds,
            'kid' => $keyId,
            'custom' => [
                'uid' => (string) $userId,
                'sid' => (string) $sessionId,
            ],
        ];

        $segments = [
            $this->b64(json_encode($header)),
            $this->b64(json_encode($payload)),
        ];

        $signature = '';
        openssl_sign(implode('.', $segments), $signature, $privateKey, OPENSSL_ALGO_SHA256);
        $segments[] = $this->b64($signature);

        return implode('.', $segments);
    }

    public function manifestUrl(string $playbackId, ?string $token = null): string
    {
        $url = "https://stream.mux.com/{$playbackId}.m3u8";

        return $token ? $url.'?token='.$token : $url;
    }

    public function deleteAsset(string $assetId): bool
    {
        try {
            $this->http()->delete(self::API."/assets/{$assetId}")->throw();

            return true;
        } catch (\Throwable $e) {
            Log::warning('Mux delete asset failed', ['asset' => $assetId, 'error' => $e->getMessage()]);

            return false;
        }
    }

    public function verifyWebhookSignature(string $payload, string $signatureHeader): bool
    {
        $secret = (string) config('services.mux.webhook_secret');
        if (! $secret) {
            return false;
        }

        // Mux format: t=timestamp,v1=signature
        $parts = [];
        foreach (explode(',', $signatureHeader) as $piece) {
            [$k, $v] = explode('=', $piece, 2);
            $parts[$k] = $v;
        }

        if (empty($parts['t']) || empty($parts['v1'])) {
            return false;
        }

        $expected = hash_hmac('sha256', $parts['t'].'.'.$payload, $secret);

        return hash_equals($expected, $parts['v1']);
    }

    private function b64(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
