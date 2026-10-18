<?php

namespace App\Jobs;

use App\Services\WavixaWhatsApp;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

/**
 * Queued WhatsApp notification — never blocks the user-facing request,
 * retries transient failures, and no-ops when the API key isn't set.
 */
class SendWhatsAppMessageJob implements ShouldQueue
{
    use Queueable;

    public int $tries = 3;
    public int $backoff = 30;

    public function __construct(
        public string $to,
        public string $message,
        public ?string $fallbackTemplate = null,
    ) {}

    public function handle(WavixaWhatsApp $whatsapp): void
    {
        $sent = $whatsapp->send($this->to, $this->message, $this->fallbackTemplate);

        if (! $sent && $whatsapp->isConfigured()) {
            throw new \RuntimeException('WhatsApp send failed');
        }
    }
}
