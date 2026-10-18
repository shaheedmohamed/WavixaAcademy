<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Event extends Model
{
    protected $fillable = [
        'course_id', 'title', 'slug', 'description', 'cover', 'type',
        'visibility', 'start_at', 'end_at', 'timezone', 'location',
        'online_url', 'capacity', 'price', 'status', 'speaker', 'recording_url',
    ];

    protected function casts(): array
    {
        return [
            'start_at' => 'datetime',
            'end_at' => 'datetime',
            'price' => 'decimal:2',
        ];
    }

    protected static function booted(): void
    {
        static::saving(function (Event $event) {
            if (! $event->slug) {
                $base = Str::slug($event->title) ?: 'event';
                $slug = $base;
                $i = 1;
                while (static::where('slug', $slug)->exists()) {
                    $slug = $base.'-'.++$i;
                }
                $event->slug = $slug;
            }
        });
    }

    public function scopePublished($query)
    {
        return $query->where('status', 'published');
    }

    public function scopeUpcoming($query)
    {
        return $query->where('start_at', '>', now())->orderBy('start_at');
    }

    public function isUpcoming(): bool
    {
        return $this->start_at && $this->start_at->isFuture();
    }

    public function hasCapacity(): bool
    {
        if (! $this->capacity) {
            return true;
        }

        return $this->registrations()->whereIn('status', ['registered', 'attended'])->count() < $this->capacity;
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function registrations(): HasMany
    {
        return $this->hasMany(EventRegistration::class);
    }
}
