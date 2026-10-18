<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class EventRegistration extends Model
{
    protected $fillable = [
        'event_id', 'user_id', 'status', 'registered_at',
        'ticket_code', 'qr_token', 'checked_in_at', 'checked_in_by',
    ];

    protected function casts(): array
    {
        return [
            'registered_at' => 'datetime',
            'checked_in_at' => 'datetime',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (EventRegistration $reg) {
            $reg->ticket_code ??= 'T-'.strtoupper(Str::random(10));
            $reg->qr_token ??= hash('sha256', Str::uuid().$reg->ticket_code);
            $reg->registered_at ??= now();
        });
    }

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function checkinAgent(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_in_by');
    }
}
