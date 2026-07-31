<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProfileEmailChangeOtp extends Model
{
    use HasFactory;

    public const PURPOSE = 'profile_email_change';

    protected $fillable = [
        'user_uid',
        'current_email',
        'new_email',
        'otp_hash',
        'purpose',
        'attempts',
        'expires_at',
        'used_at',
        'last_sent_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'used_at' => 'datetime',
        'last_sent_at' => 'datetime',
    ];

    public function scopeActive($query)
    {
        return $query
            ->where('purpose', self::PURPOSE)
            ->whereNull('used_at')
            ->where('expires_at', '>', now());
    }

    public function isUsable(): bool
    {
        return $this->used_at === null
            && $this->expires_at !== null
            && $this->expires_at->isFuture()
            && $this->attempts < 5;
    }

    public function verifyOtp(string $plainOtp): bool
    {
        return hash_equals($this->otp_hash, hash('sha256', $plainOtp));
    }
}
