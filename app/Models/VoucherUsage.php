<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class VoucherUsage extends Model
{
    use HasFactory;

    protected $fillable = [
        'voucher_id',
        'voucher_uid',
        'cart_uid',
        'invoice',
        'code',
        'discount_amount',
        'used_at',
    ];

    protected $casts = [
        'discount_amount' => 'integer',
        'used_at' => 'datetime',
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
