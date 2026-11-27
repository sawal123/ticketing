<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cart extends Model
{
    use HasFactory;
    use SoftDeletes;

    public const STATUS_RESERVED = 'RESERVED';

    public const STATUS_PENDING = 'PENDING';

    public const STATUS_SUCCESS = 'SUCCESS';

    public const STATUS_CANCELLED = 'CANCELLED';

    public const STATUS_EXPIRED = 'EXPIRED';

    public const STATUS_PAYMENT_REVIEW = 'PAYMENT_REVIEW';

    public const STATUS_UNPAID = 'UNPAID';

    public const ACTIVE_RESERVATION_STATUSES = [
        self::STATUS_RESERVED,
        self::STATUS_PENDING,
        self::STATUS_UNPAID,
        'reserved',
        'pending',
        'unpaid',
    ];

    public const TERMINAL_STATUSES = [
        self::STATUS_SUCCESS,
        self::STATUS_CANCELLED,
        self::STATUS_EXPIRED,
    ];

    public const GENDER_MALE = 'pria';

    public const GENDER_FEMALE = 'wanita';

    public const ALLOWED_GENDERS = [
        self::GENDER_MALE,
        self::GENDER_FEMALE,
    ];

    protected $fillable = [
        'uid',
        'user_uid',
        'is_guest',
        'event_uid',
        'partner_uid',
        'partner_discount_amount',
        'partner_commission_type',
        'partner_commission_value',
        'partner_commission_amount',
        'invoice',
        'ticket_holder_name',
        'ticket_recipient_email',
        'ticket_holder_gender',
        'ticket_holder_phone',
        'guest_token_hash',
        'guest_session_key',
        'gate_token_hash',
        'gate_token_encrypted',
        'gate_manual_code_hash',
        'gate_manual_code_encrypted',
        'gate_token_issued_at',
        'scanned_at',
        'scanned_by',
        'scan_device_id',
        'gate_token_version',
        'status',
        'konfirmasi',
        'link',
        'payment_type',
        'internet_fee',
        'pajak',
        'pajak_persen',
        'expires_at',
        'paid_at',
        'reservation_released_at',
        'payment_link_expires_at',
        'gross_amount',
        'payment_gateway_id',
        'payment_fee_mode',
        'payment_fee_fixed',
        'payment_fee_percent',
        'review_reason',
        'midtrans_transaction_id',
        'midtrans_status',
    ];

    protected $casts = [
        'is_guest' => 'boolean',
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
        'reservation_released_at' => 'datetime',
        'payment_link_expires_at' => 'datetime',
        'gross_amount' => 'integer',
        'internet_fee' => 'integer',
        'pajak' => 'integer',
        'pajak_persen' => 'integer',
        'payment_gateway_id' => 'integer',
        'payment_fee_fixed' => 'decimal:2',
        'payment_fee_percent' => 'decimal:4',
        'gate_token_issued_at' => 'datetime',
        'scanned_at' => 'datetime',
        'gate_token_version' => 'integer',
    ];

    protected $hidden = [
        'guest_token_hash',
        'guest_session_key',
        'gate_token_hash',
        'gate_token_encrypted',
        'gate_manual_code_hash',
        'gate_manual_code_encrypted',
    ];

    public function hargaCarts()
    {
        return $this->hasMany(HargaCart::class, 'uid', 'uid');
    }

    public function users()
    {
        return $this->belongsTo(User::class, 'user_uid', 'uid');
    }

    public function cashBuyer()
    {
        return $this->hasOne(Cash::class, 'uid', 'uid');
    }

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_uid', 'uid');
    }

    public function partner()
    {
        return $this->belongsTo(Partner::class, 'partner_uid', 'uid');
    }

    public function transaction()
    {
        return $this->hasOne(Transaction::class, 'invoice', 'invoice');
    }

    public function cartVoucher()
    {
        return $this->hasOne(CartVoucher::class, 'uid', 'uid');
    }

    public function paymentGateway()
    {
        return $this->belongsTo(PaymentGateway::class);
    }

    public function registration()
    {
        return $this->hasOne(EventRegistration::class, 'cart_uid', 'uid');
    }

    public function isReservationExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function hasActivePaymentLink(): bool
    {
        return filled($this->link)
            && ($this->payment_link_expires_at === null || $this->payment_link_expires_at->isFuture());
    }

    public function recipientSnapshotLocked(): bool
    {
        return $this->hasActivePaymentLink()
            || ! in_array($this->status, self::ACTIVE_RESERVATION_STATUSES, true);
    }

    public function hasRecipientSnapshot(): bool
    {
        if (! filled($this->ticket_holder_name) || ! filled($this->ticket_recipient_email)) {
            return false;
        }

        if ($this->isGuest()) {
            return filled($this->ticket_holder_gender) && filled($this->ticket_holder_phone);
        }

        return true;
    }

    public function buyerPhone(): ?string
    {
        if (strtolower((string) $this->payment_type) === 'cash' && filled($this->cashBuyer?->nomor)) {
            return (string) $this->cashBuyer->nomor;
        }

        if (filled($this->ticket_holder_phone)) {
            return (string) $this->ticket_holder_phone;
        }

        $userPhone = trim((string) ($this->users?->nomor ?? ''));

        return ($userPhone !== '' && $userPhone !== '-') ? $userPhone : null;
    }

    public function isGuest(): bool
    {
        return (bool) $this->is_guest || blank($this->user_uid);
    }

    public function buyerName(): string
    {
        if (strtolower((string) $this->payment_type) === 'cash' && filled($this->cashBuyer?->name)) {
            return (string) $this->cashBuyer->name;
        }

        if (filled($this->ticket_holder_name)) {
            return (string) $this->ticket_holder_name;
        }

        return (string) ($this->users?->name ?? 'Guest');
    }

    public function buyerEmail(): ?string
    {
        if (strtolower((string) $this->payment_type) === 'cash' && filled($this->cashBuyer?->email)) {
            return (string) $this->cashBuyer->email;
        }

        if (filled($this->ticket_recipient_email)) {
            return (string) $this->ticket_recipient_email;
        }

        return $this->users?->email ? (string) $this->users->email : null;
    }

    public function buyerGender(): ?string
    {
        if (strtolower((string) $this->payment_type) === 'cash' && filled($this->cashBuyer?->gender)) {
            return (string) $this->cashBuyer->gender;
        }

        if (filled($this->ticket_holder_gender)) {
            return (string) $this->ticket_holder_gender;
        }

        return $this->users?->gender ? (string) $this->users->gender : null;
    }
}
