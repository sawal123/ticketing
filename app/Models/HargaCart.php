<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HargaCart extends Model
{
    use HasFactory;
    protected $fillable=['uid','orderBy', 'event_uid', 'quantity', 'harga_ticket', 'kategori_harga'];

    public function carts()
    {
        return $this->hasMany(Cart::class, 'uid'); // Ganti 'harga_cart_id' sesuai dengan kunci asing yang sesuai dalam tabel carts
    }
}
