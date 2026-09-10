<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VoucherReservation extends Model
{
    use HasFactory;

    public const STATUS_ACTIVE = 'ACTIVE';

    public const STATUS_COMMITTED = 'COMMITTED';

    public const STATUS_RELEASED = 'RELEASED';

    protected $fillable = [
        'voucher_id',
        'voucher_uid',
        'cart_uid',
        'invoice',
        'code',
        'status',
        'reserved_at',
        'released_at',
        'committed_at',
    ];

    protected $casts = [
        'reserved_at' => 'datetime',
        'released_at' => 'datetime',
        'committed_at' => 'datetime',
    ];

    public function voucher()
    {
        return $this->belongsTo(Voucher::class);
    }

    public function cart()
    {
        return $this->belongsTo(Cart::class, 'cart_uid', 'uid');
    }
}
