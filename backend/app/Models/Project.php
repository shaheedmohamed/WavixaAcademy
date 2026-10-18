<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Project extends Model
{
    protected $fillable = [
        'course_id', 'module_id', 'after_lesson_id', 'title', 'description',
        'instructions', 'attachment_type', 'attachment_url',
        'deadline', 'requirements', 'sort_order', 'status',
    ];

    protected function casts(): array
    {
        return [
            'deadline' => 'datetime',
            'requirements' => 'array',
        ];
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'module_id');
    }

    public function afterLesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class, 'after_lesson_id');
    }

    public function submissions(): HasMany
    {
        return $this->hasMany(ProjectSubmission::class);
    }
}
