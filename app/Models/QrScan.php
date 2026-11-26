<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class QrScan extends Model
{
    protected $fillable = [
        'event_uid',
        'ip_address',
        'user_agent',
    ];

    public function event(): BelongsTo
    {
        return $this->belongsTo(Event::class, 'event_uid', 'uid');
    }
}
