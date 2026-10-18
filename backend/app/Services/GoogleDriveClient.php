<?php

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Minimal Google Drive client using a service account (JWT → OAuth2 access token).
 * The service account must have the Drive files shared with it, or domain-wide
 * delegation configured.
 */
class GoogleDriveClient
{
    private const TOKEN_URL = 'https://oauth2.googleapis.com/token';
    private const DRIVE_API = 'https://www.googleapis.com/drive/v3';
    private const UPLOAD_API = 'https://www.googleapis.com/upload/drive/v3';

    public function isConfigured(): bool
    {
        return (bool) (config('services.google_drive.client_email') && config('services.google_drive.private_key'));
    }

    /**
     * Extract a file ID from any common Google Drive URL shape.
     */
    public static function extractFileId(string $url): ?string
    {
        $url = trim($url);
        // bare id
        if (preg_match('/^[a-zA-Z0-9_-]{20,}$/', $url)) {
            return $url;
        }
        foreach ([
            '/\/d\/([a-zA-Z0-9_-]{20,})/',
            '/[?&]id=([a-zA-Z0-9_-]{20,})/',
            '/\/folders\/([a-zA-Z0-9_-]{20,})/',
        ] as $pattern) {
            if (preg_match($pattern, $url, $m)) {
                return $m[1];
            }
        }

        return null;
    }

    public function fileMetadata(string $fileId): array
    {
        $response = Http::withToken($this->accessToken())
            ->get(self::DRIVE_API."/files/{$fileId}", [
                'fields' => 'id,name,mimeType,size,videoMediaMetadata',
                'supportsAllDrives' => 'true',
            ]);

        if ($response->status() === 404) {
            throw new RuntimeException('الملف غير موجود أو لم تتم مشاركته مع حساب الخدمة.');
        }

        $response->throw();

        return $response->json();
    }

    /**
     * Stream-download a Drive file to a local temp path. Returns the path.
     */
    public function downloadToTemp(string $fileId, callable $onProgress = null): string
    {
        $meta = $this->fileMetadata($fileId);
        $tmpPath = storage_path('app/tmp/drive_'.$fileId.'_'.bin2hex(random_bytes(6)));

        if (! is_dir(dirname($tmpPath))) {
            mkdir(dirname($tmpPath), 0755, true);
        }

        $response = Http::withToken($this->accessToken())
            ->withOptions(['stream' => true, 'timeout' => 600])
            ->get(self::DRIVE_API."/files/{$fileId}", [
                'alt' => 'media',
                'supportsAllDrives' => 'true',
            ]);

        $response->throw();

        $stream = $response->toPsrResponse()->getBody();
        $out = fopen($tmpPath, 'wb');
        $written = 0;
        while (! $stream->eof()) {
            $chunk = $stream->read(1024 * 512);
            fwrite($out, $chunk);
            $written += strlen($chunk);
            if ($onProgress) {
                $onProgress($written, (int) ($meta['size'] ?? 0));
            }
        }
        fclose($out);

        return $tmpPath;
    }

    private function accessToken(): string
    {
        return Cache::remember('gdrive:access_token', 3500, function () {
            $email = config('services.google_drive.client_email');
            $key = str_replace('\\n', "\n", (string) config('services.google_drive.private_key'));

            $now = time();
            $jwt = $this->b64(json_encode(['alg' => 'RS256', 'typ' => 'JWT']))
                .'.'.$this->b64(json_encode([
                    'iss' => $email,
                    'scope' => 'https://www.googleapis.com/auth/drive.readonly',
                    'aud' => self::TOKEN_URL,
                    'iat' => $now,
                    'exp' => $now + 3600,
                ]));

            $signature = '';
            if (! openssl_sign($jwt, $signature, $key, OPENSSL_ALGO_SHA256)) {
                throw new RuntimeException('Google Drive service account key is invalid.');
            }
            $jwt .= '.'.$this->b64($signature);

            $response = Http::asForm()->post(self::TOKEN_URL, [
                'grant_type' => 'urn:ietf:params:oauth:grant-type:jwt-bearer',
                'assertion' => $jwt,
            ]);
            $response->throw();

            return $response->json('access_token');
        });
    }

    private function b64(string $data): string
    {
        return rtrim(strtr(base64_encode($data), '+/', '-_'), '=');
    }
}
