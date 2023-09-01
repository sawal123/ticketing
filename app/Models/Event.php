<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Event extends Model
{
    use HasFactory;
    protected $fillable = [
        'uid', 'user_uid', 'event', 'alamat', 'tanggal', 'status', 'deskripsi', 'map','slug'
    ];
    public function harga()
{
    return $this->hasOne(Harga::class, 'uid', 'uid'); // 'uid' di model Event sesuai dengan kunci asing di model Harga
}
}
