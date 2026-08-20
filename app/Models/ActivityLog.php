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
        'audit_category',
        'action_key',
        'event_uid',
        'payment_gateway_id',
        'login_status',
        'description',
        'impact_level',
        'old_values',
        'new_values',
        'ip_address',
        'location',
        'user_agent',
        'device_id',
        'session_id',
    ];

    protected $casts = [
        'old_values' => 'array',
        'new_values' => 'array',
    ];

    public function user()
    {
        return $this->belongsTo(User::class, 'user_uid', 'uid');
    }

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_uid', 'uid')->withTrashed();
    }

    public function paymentGateway()
    {
        return $this->belongsTo(PaymentGateway::class);
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
