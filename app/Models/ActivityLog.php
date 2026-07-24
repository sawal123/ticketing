<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Log;
use Throwable;

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

    public static function safeCreate(array $attributes): ?self
    {
        try {
            return self::create($attributes);
        } catch (Throwable $e) {
            Log::warning('Activity log write failed', [
                'error' => $e->getMessage(),
                'user_uid' => $attributes['user_uid'] ?? null,
                'activity' => $attributes['activity'] ?? null,
                'ip_address' => $attributes['ip_address'] ?? null,
            ]);

            return null;
        }
    }
}
