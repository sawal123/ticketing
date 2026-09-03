<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventRegistrationMember extends Model
{
    use HasFactory;

    protected $fillable = [
        'uid',
        'registration_uid',
        'is_captain',
        'sort_order',
        'answers',
    ];

    protected $casts = [
        'is_captain' => 'boolean',
        'sort_order' => 'integer',
        'answers' => 'array',
    ];

    public function registration(): BelongsTo
    {
        return $this->belongsTo(EventRegistration::class, 'registration_uid', 'uid');
    }
}
