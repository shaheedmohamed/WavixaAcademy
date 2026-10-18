<?php

namespace App\Jobs;

use App\Services\EnrollmentService;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Queue\Queueable;

class ExpireEnrollmentsJob implements ShouldQueue
{
    use Queueable;

    public function handle(EnrollmentService $enrollments): void
    {
        $enrollments->expireDueEnrollments();
    }
}
