<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CheckoutPaymentOtp extends Model
{
    use HasFactory;

    protected $fillable = [
        'cart_uid',
        'user_uid',
        'event_uid',
        'code_hash',
        'expires_at',
        'attempts',
        'sent_at',
        'verified_at',
        'consumed_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'sent_at' => 'datetime',
        'verified_at' => 'datetime',
        'consumed_at' => 'datetime',
    ];

    public function isExpired(): bool
    {
        return $this->expires_at === null || $this->expires_at->isPast();
    }

    public function isVerified(): bool
    {
        return $this->verified_at !== null;
    }

    public function isConsumed(): bool
    {
        return $this->consumed_at !== null;
    }

    public function canAttempt(): bool
    {
        return $this->attempts < 5;
    }

    public function matchesCode(string $plainCode): bool
    {
        return hash_equals($this->code_hash, hash('sha256', $plainCode));
    }
}
