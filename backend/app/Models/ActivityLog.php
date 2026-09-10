<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ActivityLog extends Model
{
    protected $fillable = [
        'user_id',
        'action',
        'entity_type',
        'entity_id',
        'metadata',
        'ip_address',
    ];

    protected $casts = [
        'metadata' => 'array',   // JSONB ↔ array PHP dua arah otomatis
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }
}
