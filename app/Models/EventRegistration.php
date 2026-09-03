<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventRegistration extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'PENDING';

    public const STATUS_SUCCESS = 'SUCCESS';

    public const STATUS_CANCELLED = 'CANCELLED';

    public const STATUS_EXPIRED = 'EXPIRED';

    protected $fillable = [
        'uid',
        'cart_uid',
        'invoice',
        'event_uid',
        'user_uid',
        'registration_mode',
        'status',
        'team_name',
        'answers',
    ];

    protected $casts = [
        'answers' => 'array',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_uid', 'uid');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_uid', 'uid');
    }

    public function cart(): BelongsTo
    {
        return $this->belongsTo(Cart::class, 'cart_uid', 'uid');
    }

    public function members(): HasMany
    {
        return $this->hasMany(EventRegistrationMember::class, 'registration_uid', 'uid')
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}
