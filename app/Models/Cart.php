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
    ];

    public const TERMINAL_STATUSES = [
        self::STATUS_SUCCESS,
        self::STATUS_CANCELLED,
        self::STATUS_EXPIRED,
    ];

    protected $fillable = [
        'uid',
        'user_uid',
        'event_uid',
        'invoice',
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
        'review_reason',
        'midtrans_transaction_id',
        'midtrans_status',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'paid_at' => 'datetime',
        'reservation_released_at' => 'datetime',
        'payment_link_expires_at' => 'datetime',
        'gross_amount' => 'integer',
        'internet_fee' => 'integer',
        'pajak' => 'integer',
        'pajak_persen' => 'integer',
    ];


    public function hargaCarts()
    {
        return $this->hasMany(HargaCart::class, 'uid', 'uid');
    }

    public function users()
    {
        return $this->belongsTo(User::class, 'user_uid', 'uid');
    }

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_uid', 'uid');
    }

    public function transaction()
    {
        return $this->hasOne(Transaction::class, 'invoice', 'invoice');
    }

    public function cartVoucher()
    {
        return $this->hasOne(CartVoucher::class, 'uid', 'uid');
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
}
