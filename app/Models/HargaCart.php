<?php

namespace App\Models;

use App\Models\Cart;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class HargaCart extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable=['uid','orderBy', 'event_uid', 'quantity', 'harga_ticket', 'voucher', 'disc', 'kategori_harga'];

    // public function carts()
    // {
    //     return $this->hasMany(Cart::class, 'uid'); // Ganti 'harga_cart_id' sesuai dengan kunci asing yang sesuai dalam tabel carts
    // }

    public function cart()
    {
        return $this->belongsTo(Cart::class, 'uid', 'uid');
    }
}
