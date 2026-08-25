<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use LogicException;

class Agreement extends Model
{
    use HasFactory;

    public const TYPE_MOU = 'mou';

    public const STATUS_DRAFT = 'DRAFT';
    public const STATUS_READY = 'READY';
    public const STATUS_SENT_TO_PRIVY = 'SENT_TO_PRIVY';
    public const STATUS_SIGNING = 'SIGNING';
    public const STATUS_COMPLETED = 'COMPLETED';
    public const STATUS_REJECTED = 'REJECTED';
    public const STATUS_CANCELLED = 'CANCELLED';

    protected $fillable = [
        'uid',
        'event_uid',
        'tenant_user_uid',
        'type',
        'document_number',
        'version',
        'status',
        'template_version',
        'event_snapshot',
        'party_snapshot',
        'bank_snapshot',
        'document_snapshot',
        'commercial_snapshot',
        'privy_document_id',
        'privy_status',
        'privy_reference',
        'unsigned_pdf_path',
        'signed_pdf_path',
        'sent_to_privy_at',
        'signed_at',
        'completed_at',
        'created_by',
    ];

    protected $attributes = [
        'type' => self::TYPE_MOU,
        'version' => 1,
        'status' => self::STATUS_DRAFT,
    ];

    protected $casts = [
        'event_snapshot' => 'array',
        'party_snapshot' => 'array',
        'bank_snapshot' => 'array',
        'document_snapshot' => 'array',
        'commercial_snapshot' => 'array',
        'sent_to_privy_at' => 'datetime',
        'signed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (Agreement $agreement) {
            if ($agreement->getOriginal('status') === self::STATUS_COMPLETED) {
                throw new LogicException('Completed agreement is immutable.');
            }
        });

        static::deleting(function (Agreement $agreement) {
            if ($agreement->status === self::STATUS_COMPLETED) {
                throw new LogicException('Completed agreement cannot be deleted.');
            }
        });
    }

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_uid', 'uid');
    }

    public function tenant()
    {
        return $this->belongsTo(User::class, 'tenant_user_uid', 'uid');
    }

    public function isDraft(): bool
    {
        return $this->status === self::STATUS_DRAFT;
    }

    public function isReady(): bool
    {
        return $this->status === self::STATUS_READY;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }
}
