<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class EventRegistration extends Model
{
    use HasFactory;

    protected $fillable = [
        'uid',
        'event_uid',
        'user_uid',
        'registration_mode',
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

    public function members(): HasMany
    {
        return $this->hasMany(EventRegistrationMember::class, 'registration_uid', 'uid')
            ->orderBy('sort_order')
            ->orderBy('id');
    }
}
