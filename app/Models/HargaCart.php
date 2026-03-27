<?php

namespace App\Models;

use App\Models\Cart;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HargaCart extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = ['uid', 'orderBy', 'event_uid', 'quantity', 'harga_ticket', 'voucher', 'disc', 'kategori_harga'];

    public function cart()
    {
        // Berasumsi 'uid' di tabel ini adalah foreign key ke 'uid' milik Cart
        return $this->belongsTo(Cart::class, 'uid', 'uid');
    }

    public function event() // Tambahan relasi langsung ke Event jika diperlukan
    {
        return $this->belongsTo(Event::class, 'event_uid', 'uid');
    }
}
