<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ActivityLog extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_uid',
        'activity',
        'login_status',
        'description',
        'impact_level',
        'ip_address',
        'location',
        'user_agent',
        'device_id',
        'session_id',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_uid', 'uid');
    }
}
