<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Partner extends Model
{
    use HasFactory;
    protected $fillable = ['uid', 'user_uid', 'event_uid', 'referensi', 'name', 'email', 'hp', 'alamat', 'city', 'status'];

    public function event()
    {
        return $this->belongsTo(Event::class, 'event_uid', 'uid');
    }
}
