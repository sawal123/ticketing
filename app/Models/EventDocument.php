<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventDocument extends Model
{
    use HasFactory;

    public const TYPE_ORGANIZER_LETTER = 'ORGANIZER_LETTER';
    public const TYPE_RESPONSIBLE_IDENTITY = 'RESPONSIBLE_IDENTITY';

    protected $fillable = [
        'uid',
        'event_uid',
        'document_type',
        'document_number',
        'document_date',
        'original_name',
        'file_path',
        'mime_type',
        'status',
        'verified_by',
        'verified_at',
        'rejection_reason',
    ];

    protected $casts = [
        'document_date' => 'date',
        'verified_at' => 'datetime',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_uid', 'uid');
    }
}
