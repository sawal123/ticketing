<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Fasilitas extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'icon'];

    public function events()
    {
        return $this->belongsToMany(Event::class, 'event_fasilitas', 'fasilitas_id', 'event_uid', 'id', 'uid');
    }
}
