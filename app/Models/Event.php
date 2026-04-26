<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uid',
        'user_uid',
        'event',
        'alamat',
        'tanggal',
        'status',
        'fee',
        'deskripsi',
        'map',
        'slug',
        'konfirmasi'
    ];

    public function harga()
    {
        return $this->hasOne(Harga::class, 'uid', 'uid')
            ->where('status', 'active')
            ->orderByRaw('CAST(harga AS UNSIGNED) ASC');
    }

    public function talents() // Ubah nama jadi plural (talents)
    {
        return $this->hasMany(Talent::class, 'uid', 'uid');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_uid', 'uid');
    }

    // Hapus method users() yang duplikat

    public function carts() // Ubah nama jadi plural (carts) dan ganti jadi hasMany
    {
        return $this->hasMany(Cart::class, 'event_uid', 'uid');
    }
    public function hargas()
    {
        return $this->hasMany(Harga::class, 'uid', 'uid');
    }
    // Hapus method harga_carts() dari sini. Relasinya agak aneh kalau Event langsung ke HargaCart tanpa lewat Cart.
}
