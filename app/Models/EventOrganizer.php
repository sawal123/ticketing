<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EventOrganizer extends Model
{
    use HasFactory;

    protected $fillable = [
        'event_uid',
        'organizer_name',
        'responsible_name',
        'responsible_position',
        'phone',
        'email',
        'address',
    ];

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_uid', 'uid');
    }
}
