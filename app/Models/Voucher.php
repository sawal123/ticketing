<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Voucher extends Model
{
    use HasFactory;
    protected $fillable = [
        'uid', 'user_uid', 'event_uid', 'code', 'unit', 'nominal', 'min_beli', 'max_disc','digunakan', 'limit', 'status'
    ];

    public function cartVoucher()
    {
        return $this->hasMany(CartVoucher::class, 'code', 'code');
    }
}
