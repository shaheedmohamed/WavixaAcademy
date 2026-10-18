<?php

namespace App\Services;

use App\Models\Lesson;
use App\Models\LessonProgress;
use App\Models\User;

class ProgressService
{
    public function recordPosition(User $user, Lesson $lesson, int $positionSeconds, int $durationSeconds): LessonProgress
    {
        $threshold = (float) config('academy.completion_threshold', 0.9);

        $progress = LessonProgress::firstOrNew([
            'user_id' => $user->id,
            'lesson_id' => $lesson->id,
        ]);

        $progress->last_position = $positionSeconds;
        $progress->duration_seconds = max($progress->duration_seconds ?? 0, $durationSeconds);
        $progress->watched_seconds = max($progress->watched_seconds ?? 0, $positionSeconds);

        $duration = $progress->duration_seconds ?: $lesson->duration_seconds;
        if (! $progress->completed && $duration > 0 && $progress->watched_seconds >= $duration * $threshold) {
            $progress->completed = true;
            $progress->completed_at = now();
        }

        $progress->save();

        return $progress;
    }
}
