<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class Certificate extends Model
{
    protected $fillable = [
        'user_id', 'course_id', 'certificate_number',
        'verification_token', 'issued_at', 'issued_by', 'meta',
    ];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Certificate $cert) {
            $cert->verification_token ??= hash('sha256', Str::uuid().microtime(true));
            $cert->certificate_number ??= 'WXA-'.now()->format('Y').'-'.str_pad((string) (static::max('id') + 1), 6, '0', STR_PAD_LEFT);
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    public function issuer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'issued_by');
    }
}
