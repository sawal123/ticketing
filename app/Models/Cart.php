<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cart extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable =['uid', 'user_uid', 'event_uid', 'invoice', 'status', 'konfirmasi'];
    public function users()
    {
        return $this->hasOne(User::class, 'uid', 'uid'); // 'uid' di model Event sesuai dengan kunci asing di model Harga
    }

    public function event(){
        return $this->hasOne(Event::class, 'uid', 'event_uid');
    }
}
