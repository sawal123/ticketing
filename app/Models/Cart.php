<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Cart extends Model
{
    use HasFactory;
    use SoftDeletes;
    protected $fillable = ['uid', 'user_uid', 'event_uid', 'invoice', 'status', 'konfirmasi', 'link', 'payment_type', 'internet_fee', 'pajak', 'pajak_persen'];


    public function hargaCarts()
    {
        return $this->hasMany(HargaCart::class, 'uid', 'uid');
    }

    public function users()
    {
        return $this->belongsTo(User::class, 'user_uid', 'uid');
    }

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_uid', 'uid');
    }
}
