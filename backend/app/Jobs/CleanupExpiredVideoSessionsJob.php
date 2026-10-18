<?php

namespace App\Jobs;

use App\Models\VideoSession;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class CleanupExpiredVideoSessionsJob implements ShouldQueue
{
    use Queueable;

    public function handle(): void
    {
        VideoSession::where('status', 'active')
            ->where('expires_at', '<', now())
            ->update(['status' => 'expired']);
    }
}
