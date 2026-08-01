<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

class Voucher extends Model
{
    use HasFactory;

    protected $fillable = [
        'uid', 'user_uid', 'event_uid', 'code', 'unit', 'nominal', 'min_beli', 'max_disc', 'digunakan', 'limit', 'status',
    ];

    public function setCodeAttribute($value): void
    {
        $this->attributes['code'] = Str::upper(trim((string) $value));
    }

    public function cartVoucher()
    {
        return $this->hasMany(CartVoucher::class, 'code', 'code');
    }

    public function hargaCart()
    {
        return $this->hasMany(HargaCart::class, 'voucher', 'code');
    }

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_uid', 'uid');
    }
}
