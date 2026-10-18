<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

/**
 * Client for the Wavixa public Meta WhatsApp API (same server, separate
 * Laravel app). Sends through the official WhatsApp Business Cloud API
 * numbers connected on wavixa.net — never through the unstable
 * whatsapp-web.js sessions.
 */
class WavixaWhatsApp
{
    public function isConfigured(): bool
    {
        return (bool) config('services.wavixa.api_key');
    }

    /**
     * Normalize Egyptian phone numbers to international format (digits only):
     *  01011679726 → 201011679726 · +20 10 11679726 → 201011679726
     */
    public static function normalizePhone(?string $phone): ?string
    {
        if (! $phone) {
            return null;
        }

        $digits = preg_replace('/\D/', '', $phone);
        if (! $digits) {
            return null;
        }

        if (str_starts_with($digits, '00')) {
            $digits = substr($digits, 2);
        }
        if (str_starts_with($digits, '0')) {
            $digits = '20'.substr($digits, 1);
        }

        return $digits;
    }

    /**
     * Send free-form text; falls back to an approved template when the
     * 24-hour customer-service window is closed (cold users).
     */
    public function send(string $to, string $message, ?string $fallbackTemplate = null): bool
    {
        if (! $this->isConfigured()) {
            Log::info('WavixaWhatsApp: not configured, skipping', ['to' => $to]);

            return false;
        }

        try {
            $payload = ['to' => $to, 'message' => $message];
            if ($fallbackTemplate) {
                $payload['template'] = $fallbackTemplate;
            }

            $response = Http::withHeaders(['X-API-Key' => config('services.wavixa.api_key')])
                ->timeout(15)
                ->post(rtrim(config('services.wavixa.api_url'), '/').'/public/meta/send-message', $payload);

            if (! $response->successful() || ! $response->json('success')) {
                Log::warning('WavixaWhatsApp send failed', [
                    'to' => $to,
                    'status' => $response->status(),
                    'body' => mb_substr((string) $response->body(), 0, 400),
                ]);

                return false;
            }

            return true;
        } catch (\Throwable $e) {
            Log::warning('WavixaWhatsApp send error', ['to' => $to, 'error' => $e->getMessage()]);

            return false;
        }
    }
}
