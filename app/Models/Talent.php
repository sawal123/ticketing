<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Talent extends Model
{
    use HasFactory;
    protected $fillable = [
        'uid', 'talent', 'gambar',
    ];

    public function event(){
        return $this->belongsTo(Event::class);
    }
}
