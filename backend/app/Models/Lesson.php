<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Support\Str;

class Lesson extends Model
{
    protected $fillable = [
        'module_id', 'title', 'slug', 'description', 'video_asset_id',
        'duration_seconds', 'sort_order', 'is_preview', 'is_published', 'status',
    ];

    protected function casts(): array
    {
        return [
            'is_preview' => 'boolean',
            'is_published' => 'boolean',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Lesson $lesson) {
            if (! $lesson->slug) {
                $lesson->slug = Str::slug($lesson->title) ?: 'lesson-'.$lesson->id.'-'.Str::random(4);
            }
        });
    }

    public function module(): BelongsTo
    {
        return $this->belongsTo(CourseModule::class, 'module_id');
    }

    public function course()
    {
        return $this->module?->course;
    }

    public function videoAsset(): BelongsTo
    {
        return $this->belongsTo(VideoAsset::class, 'video_asset_id');
    }

    public function resources(): HasMany
    {
        return $this->hasMany(LessonResource::class)->orderBy('sort_order');
    }

    public function quiz(): HasOne
    {
        return $this->hasOne(Quiz::class);
    }

    public function progressFor(int $userId): ?LessonProgress
    {
        return $this->progress()->where('user_id', $userId)->first();
    }

    public function progress(): HasMany
    {
        return $this->hasMany(LessonProgress::class);
    }
}
