<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class CartVoucher extends Model
{
    use HasFactory;
    protected $fillable = [
        'uid', 'uid_vouchers', 'user_uid', 'event_uid', 'code'
    ];
    public function voucher()
    {
        return $this->belongsTo(Voucher::class, 'code', 'code');
    }
    
}
