<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class EmailCampaign extends Model
{
    use HasFactory;

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_COMPLETED_WITH_FAILURES = 'completed_with_failures';
    public const STATUS_FAILED = 'failed';

    protected $fillable = [
        'subject',
        'content',
        'target_type',
        'event_uid',
        'total_recipients',
        'sent_count',
        'failed_count',
        'status',
        'created_by',
    ];

    protected $casts = [
        'total_recipients' => 'integer',
        'sent_count' => 'integer',
        'failed_count' => 'integer',
    ];

    public function recipients()
    {
        return $this->hasMany(EmailCampaignRecipient::class);
    }

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_uid', 'uid');
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by', 'uid');
    }
}
