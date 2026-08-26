<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
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
    public const STATUSES = [
        self::STATUS_DRAFT,
        self::STATUS_READY,
        self::STATUS_SENT_TO_PRIVY,
        self::STATUS_SIGNING,
        self::STATUS_COMPLETED,
        self::STATUS_REJECTED,
        self::STATUS_CANCELLED,
    ];

    public const SIGNED_REVIEW_PENDING = 'PENDING';
    public const SIGNED_REVIEW_VERIFIED = 'VERIFIED';
    public const SIGNED_REVIEW_REJECTED = 'REJECTED';
    public const SIGNED_REVIEW_STATUSES = [
        self::SIGNED_REVIEW_PENDING,
        self::SIGNED_REVIEW_VERIFIED,
        self::SIGNED_REVIEW_REJECTED,
    ];

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
        'signed_review_status',
        'signed_verified_by',
        'signed_verified_at',
        'signed_rejection_reason',
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
        'signed_verified_at' => 'datetime',
        'sent_to_privy_at' => 'datetime',
        'signed_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::updating(function (Agreement $agreement) {
            $agreement->ensurePersistedAgreementIsMutable();
        });

        static::deleting(function (Agreement $agreement) {
            $agreement->ensurePersistedAgreementCanBeDeleted();
        });
    }

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_uid', 'uid')->withTrashed();
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

    public static function createDraftForEvent(Event $event, string $actorUid): self
    {
        return static::query()->firstOrCreate(
            [
                'event_uid' => $event->uid,
                'type' => self::TYPE_MOU,
                'version' => 1,
            ],
            [
                'uid' => (string) Str::uuid(),
                'tenant_user_uid' => $event->user_uid,
                'status' => self::STATUS_DRAFT,
                'created_by' => $actorUid,
            ]
        );
    }

    protected function performInsert(Builder $query)
    {
        $this->ensureTenantMatchesEventOwner();

        return parent::performInsert($query);
    }

    protected function performUpdate(Builder $query)
    {
        $this->ensurePersistedAgreementIsMutable();
        $this->ensureTenantMatchesEventOwner();

        return parent::performUpdate($query);
    }

    protected function performDeleteOnModel()
    {
        $this->ensurePersistedAgreementCanBeDeleted();

        return parent::performDeleteOnModel();
    }

    private function ensurePersistedAgreementIsMutable(): void
    {
        if (! $this->exists) {
            return;
        }

        $persistedStatus = static::query()
            ->whereKey($this->getKey())
            ->value('status');

        if ($persistedStatus === self::STATUS_COMPLETED) {
            throw new LogicException('Completed agreement is immutable.');
        }
    }

    private function ensurePersistedAgreementCanBeDeleted(): void
    {
        if (! $this->exists) {
            return;
        }

        $persistedStatus = static::query()
            ->whereKey($this->getKey())
            ->value('status');

        if ($persistedStatus === self::STATUS_COMPLETED) {
            throw new LogicException('Completed agreement cannot be deleted.');
        }
    }

    private function ensureTenantMatchesEventOwner(): void
    {
        if (! $this->event_uid || ! $this->tenant_user_uid) {
            return;
        }

        $eventOwnerUid = Event::withTrashed()
            ->where('uid', $this->event_uid)
            ->value('user_uid');

        if ($eventOwnerUid !== $this->tenant_user_uid) {
            throw new LogicException('Agreement tenant must match the event owner.');
        }
    }
}
