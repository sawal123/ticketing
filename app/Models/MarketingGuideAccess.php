<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MarketingGuideAccess extends Model
{
    protected $fillable = [
        'token_hash',
        'recipient_name',
        'expires_at',
        'revoked_at',
        'last_accessed_at',
        'access_count',
        'created_by',
    ];

    protected $hidden = [
        'token_hash',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'revoked_at' => 'datetime',
        'last_accessed_at' => 'datetime',
        'access_count' => 'integer',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by', 'uid');
    }

    public function isRevoked(): bool
    {
        return $this->revoked_at !== null;
    }

    public function isExpired(): bool
    {
        return $this->expires_at === null || $this->expires_at->isPast();
    }

    public function isValid(): bool
    {
        return ! $this->isRevoked() && ! $this->isExpired();
    }
}
