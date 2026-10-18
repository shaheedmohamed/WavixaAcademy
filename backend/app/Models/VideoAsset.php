<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasOne;

class VideoAsset extends Model
{
    public const STATUS_PENDING = 'pending';
    public const STATUS_IMPORTING = 'importing';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_READY = 'ready';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'lesson_id', 'provider', 'google_file_id', 'source_filename',
        'provider_asset_id', 'provider_upload_id', 'playback_id',
        'status', 'duration_seconds', 'file_size_bytes', 'error_message',
        'meta', 'imported_by',
    ];

    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function lesson(): BelongsTo
    {
        return $this->belongsTo(Lesson::class);
    }

    public function importer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'imported_by');
    }

    public function isReady(): bool
    {
        return $this->status === self::STATUS_READY && $this->playback_id;
    }
}
