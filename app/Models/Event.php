<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use HasFactory, SoftDeletes;
    protected $fillable = [
        'uid', 'user_uid', 'event', 'alamat', 'tanggal', 'status', 'fee','deskripsi', 'map', 'slug', 'konfirmasi'
    ];
    public function harga()
    {
        return $this->hasOne(Harga::class, 'uid', 'uid')->orderBy('harga', 'asc'); // 'uid' di model Event sesuai dengan kunci asing di model Harga
    }

    public function talent(){
        return $this->hasMany(Talent::class, 'uid', 'uid');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'user_uid', 'uid');
    }
    public function users()
    {
        return $this->belongsTo(User::class, 'user_uid', 'uid');
    }
    public function cart(){
        return $this->belongsTo(Cart::class, 'event_uid', 'uid');
    }
    public function harga_carts(){
        return $this->belongsTo(HargaCart::class, 'event_uid', 'uid');
    }


}
